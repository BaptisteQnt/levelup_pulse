<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Support\Str;

class CnilPassword implements ValidationRule
{
    public const MIN_LENGTH = 12;

    private const SPECIAL_CHARACTERS = "!\"#$%&'()*+,-./:;<=>?@[\\]^_`{|}~€£¥§µ¤";

    public static function specialCharacters(): string
    {
        return self::SPECIAL_CHARACTERS;
    }

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_string($value)) {
            $fail(__('The :attribute must be a string.', ['attribute' => $attribute]));

            return;
        }

        if (Str::length($value) < self::MIN_LENGTH) {
            $fail(__('The :attribute must be at least :min characters.', [
                'attribute' => $attribute,
                'min' => self::MIN_LENGTH,
            ]));
        }

        if (! preg_match('/[A-Z]/u', $value)) {
            $fail(__('The :attribute must contain at least one uppercase letter.', ['attribute' => $attribute]));
        }

        if (! preg_match('/[a-z]/u', $value)) {
            $fail(__('The :attribute must contain at least one lowercase letter.', ['attribute' => $attribute]));
        }

        if (! preg_match('/\d/u', $value)) {
            $fail(__('The :attribute must contain at least one number.', ['attribute' => $attribute]));
        }

        $specialCharactersPattern = '/['.preg_quote(self::SPECIAL_CHARACTERS, '/').']/u';

        if (! preg_match($specialCharactersPattern, $value)) {
            $fail(__('The :attribute must contain at least one special character from the allowed list (:characters).', [
                'attribute' => $attribute,
                'characters' => self::SPECIAL_CHARACTERS,
            ]));
        }
    }
}
