<?php

namespace App\Services;

use App\Contracts\IotTelemetryAiProvider;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class OpenAiIotTelemetryProvider implements IotTelemetryAiProvider
{
    public function analyze(array $currentMeasurement, array $recentMeasurements, int $userId): array
    {
        $apiKey = (string) config('iot.ai.api_key');

        if ($apiKey === '') {
            throw new RuntimeException('OPENAI_API_KEY is not configured.');
        }

        $payload = [
            'model' => $this->modelName(),
            'store' => false,
            'reasoning' => ['effort' => (string) config('iot.ai.reasoning_effort', 'low')],
            'instructions' => $this->instructions(),
            'input' => json_encode([
                'current_measurement' => $currentMeasurement,
                'recent_measurements_same_sensor' => $recentMeasurements,
                'safe_temperature_range_celsius' => [
                    'minimum' => (float) config('iot.display.safe_temperature_min', 5),
                    'maximum' => (float) config('iot.display.safe_temperature_max', 35),
                ],
            ], JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            'text' => [
                'format' => [
                    'type' => 'json_schema',
                    'name' => 'iot_room_safety_analysis',
                    'strict' => true,
                    'schema' => $this->schema(),
                ],
            ],
            'max_output_tokens' => (int) config('iot.ai.max_output_tokens', 1_000),
            'safety_identifier' => $this->safetyIdentifier($userId),
        ];

        $response = $this->client($apiKey)
            ->post('/v1/responses', $payload)
            ->throw()
            ->json();

        $decoded = $this->decodeOutput(is_array($response) ? $response : []);

        if ($decoded === null) {
            throw new RuntimeException('OpenAI did not return a usable IoT analysis.');
        }

        return $decoded;
    }

    public function modelName(): string
    {
        return (string) config('iot.ai.model', config('compatibility.openai.model'));
    }

    private function client(string $apiKey): PendingRequest
    {
        return Http::baseUrl('https://api.openai.com')
            ->withToken($apiKey)
            ->acceptJson()
            ->asJson()
            ->timeout((int) config('iot.ai.timeout_seconds', 60))
            ->connectTimeout(10);
    }

    private function instructions(): string
    {
        return <<<'PROMPT'
Vous analysez des relevés de température et d'humidité d'une pièce où une personne joue aux jeux vidéo.

Vous devez produire deux évaluations distinctes, en français :
1. Le risque prudent associé au fait de rester et jouer dans ces conditions ambiantes.
2. La plausibilité technique de la mesure à partir de la valeur actuelle et de l'historique du même capteur.

Règles impératives :
- Il s'agit d'une aide informative, jamais d'un diagnostic médical ni d'une certification du capteur.
- N'inventez ni symptôme, ni personne, ni durée d'exposition, ni qualité de calibration absente des données.
- Une valeur physiquement plausible n'est pas automatiquement une preuve de calibration.
- Utilisez "suspicious" pour une rupture brutale, une incohérence température/humidité manifeste ou une valeur aberrante par rapport à la série.
- Utilisez "unknown" lorsque l'historique est insuffisant ou que la fiabilité ne peut pas être établie.
- Utilisez "reliable" seulement lorsque la valeur est plausible et cohérente avec plusieurs relevés récents. Cela signifie "exploitable dans cette application", pas "capteur certifié".
- Les recommandations doivent être concrètes et prudentes : pause, hydratation, ventilation, éloignement d'une source de chaleur ou vérification avec un second thermomètre selon le cas.
- En cas de chaleur ou de froid potentiellement dangereux, recommandez de quitter la pièce et de suivre les consignes des autorités locales. Ne minimisez jamais une valeur dangereuse parce qu'elle semble suspecte.
- Répondez exclusivement selon le schéma demandé.
PROMPT;
    }

    /**
     * @param  array<string, mixed>  $response
     * @return array<string, mixed>|null
     */
    private function decodeOutput(array $response): ?array
    {
        foreach ($response['output'] ?? [] as $output) {
            if (($output['type'] ?? null) !== 'message') {
                continue;
            }

            foreach ($output['content'] ?? [] as $content) {
                if (($content['type'] ?? null) !== 'output_text' || ! is_string($content['text'] ?? null)) {
                    continue;
                }

                $decoded = json_decode($content['text'], true);

                if (is_array($decoded)) {
                    return $decoded;
                }
            }
        }

        return null;
    }

    /**
     * @return array<string, mixed>
     */
    private function schema(): array
    {
        return [
            'type' => 'object',
            'additionalProperties' => false,
            'properties' => [
                'reliability' => [
                    'type' => 'string',
                    'enum' => ['reliable', 'suspicious', 'unknown'],
                ],
                'risk_level' => [
                    'type' => 'string',
                    'enum' => ['low', 'moderate', 'high', 'critical', 'unknown'],
                ],
                'risk_summary' => ['type' => 'string'],
                'reliability_reason' => ['type' => 'string'],
                'recommendations' => [
                    'type' => 'array',
                    'items' => ['type' => 'string'],
                ],
                'disclaimer' => ['type' => 'string'],
            ],
            'required' => [
                'reliability',
                'risk_level',
                'risk_summary',
                'reliability_reason',
                'recommendations',
                'disclaimer',
            ],
        ];
    }

    private function safetyIdentifier(int $userId): string
    {
        return hash_hmac('sha256', 'iot:'.$userId, (string) config('app.key', config('app.name')));
    }
}
