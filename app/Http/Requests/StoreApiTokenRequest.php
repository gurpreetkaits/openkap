<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreApiTokenRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // Authenticated via the auth:sanctum middleware.
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'expires_in_days' => ['nullable', 'integer', 'min:1', 'max:3650'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'Please give this token a name so you can recognise it later.',
            'expires_in_days.min' => 'Expiry must be at least 1 day.',
            'expires_in_days.max' => 'Expiry cannot exceed 3650 days (10 years).',
        ];
    }
}
