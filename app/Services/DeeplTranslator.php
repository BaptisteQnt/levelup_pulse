<?php
namespace App\Services;

use Illuminate\Support\Facades\Http;

class DeeplTranslator implements Translator
{
    public function __construct(
        private readonly string $apiKey,
        private readonly string $baseUrl = 'https://api-free.deepl.com/v2/translate'
    ) {}

    public function translate(string $text, string $to = 'fr', ?string $from = 'en'): string
    {
        if (trim($text) === '') return $text;

        $payload = [
            'text'         => $text,
            'target_lang'  => strtoupper($to),     // FR
            'preserve_formatting' => 1,
        ];
        if ($from) $payload['source_lang'] = strtoupper($from); // EN

        $res = Http::asForm()
            ->withHeaders(['Authorization' => 'DeepL-Auth-Key '.$this->apiKey])
            ->post($this->baseUrl, $payload)
            ->throw();

        return $res->json('translations.0.text') ?? $text;
    }

    public static function fromEnv(): self
    {
        return new self(
            apiKey: env('DEEPL_API_KEY', ''),
            baseUrl: env('DEEPL_API_URL', 'https://api-free.deepl.com/v2/translate')
        );
    }
}
