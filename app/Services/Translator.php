<?php
namespace App\Services;

interface Translator {
    public function translate(string $text, string $to = 'fr', ?string $from = 'en'): string;
}
