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
        if (trim($text) === '') {
            return $text;
        }

        $payload = [
            'text' => $text,
            'target_lang' => strtoupper($to),     // FR
            'preserve_formatting' => 1,
        ];
        if ($from) {
            $payload['source_lang'] = strtoupper($from);
        } // EN

        $res = Http::asForm()
            ->withHeaders(['Authorization' => 'DeepL-Auth-Key '.$this->apiKey])
            ->post($this->baseUrl, $payload)
            ->throw();

        return $res->json('translations.0.text') ?? $text;
    }

    public static function fromConfig(): self
    {
        $apiKey = config('services.deepl.key');
        $baseUrl = config('services.deepl.url', 'https://api-free.deepl.com/v2/translate');

        if (! is_string($apiKey) || trim($apiKey) === '') {
            throw new \RuntimeException('DEEPL_API_KEY is not configured.');
        }

        return new self(
            apiKey: trim($apiKey),
            baseUrl: is_string($baseUrl) && trim($baseUrl) !== ''
                ? trim($baseUrl)
                : 'https://api-free.deepl.com/v2/translate'
        );
    }
}
