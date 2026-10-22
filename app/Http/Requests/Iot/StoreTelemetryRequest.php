<?php

namespace App\Http\Requests\Iot;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class StoreTelemetryRequest extends FormRequest
{
    private const ALLOWED_FIELDS = [
        'device_id',
        'temperature',
        'humidity',
        'sequence',
        'measured_at',
    ];

    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'device_id' => ['required', 'string', 'max:64', 'regex:/\A[A-Z0-9][A-Z0-9_-]*\z/'],
            'temperature' => [
                'required',
                'numeric',
                'decimal:0,2',
                'between:'.config('iot.telemetry.temperature_min').','.config('iot.telemetry.temperature_max'),
            ],
            'humidity' => [
                'required',
                'numeric',
                'decimal:0,2',
                'between:'.config('iot.telemetry.humidity_min').','.config('iot.telemetry.humidity_max'),
            ],
            'sequence' => ['required', 'integer', 'min:0', 'max:9223372036854775807'],
            'measured_at' => ['nullable', 'date_format:Y-m-d\TH:i:sP,Y-m-d\TH:i:s\Z'],
        ];
    }

    /**
     * @return array<int, callable>
     */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                $unexpectedFields = array_diff(array_keys($this->all()), self::ALLOWED_FIELDS);

                if ($unexpectedFields !== []) {
                    $validator->errors()->add(
                        'payload',
                        'Unexpected fields: '.implode(', ', $unexpectedFields).'.',
                    );
                }
            },
        ];
    }
}
