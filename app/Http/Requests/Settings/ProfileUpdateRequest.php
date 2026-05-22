<?php

namespace App\Http\Requests\Settings;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ProfileUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'username' => ['required', 'string', 'max:255', Rule::unique('users')->ignore($this->user()->id)],
            'email' => ['required', 'string', 'email', 'max:255', Rule::unique('users')->ignore($this->user()->id)],
            'phone' => ['nullable', 'string', 'max:20'],
            'address' => ['nullable', 'string', 'max:255'],
            'city' => ['nullable', 'string', 'max:255'],
            'cp' => ['nullable', 'string', 'max:10'],
            'country' => ['nullable', 'string', 'max:100'],
            'age' => ['required', 'integer', 'min:1', 'max:120'],
            'display_name_color' => [
                'nullable',
                Rule::prohibitedIf(fn () => ! $this->user()->subscribed('default')),
                'regex:/^#(?:[0-9a-fA-F]{3}){1,2}$/',
            ],
            'display_alias' => [
                'nullable',
                Rule::prohibitedIf(fn () => ! $this->user()->subscribed('default')),
                'string',
                'max:30',
            ],
            'profile_border_style' => [
                'nullable',
                Rule::prohibitedIf(fn () => ! $this->user()->subscribed('default')),
                Rule::in(['none', 'starlight', 'neon', 'ember']),
            ],
        ];
    }
}
