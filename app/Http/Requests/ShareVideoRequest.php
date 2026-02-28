<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ShareVideoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'target_id' => 'required|string|max:255',
            'target_name' => 'nullable|string|max:255',
            'message' => 'nullable|string|max:1000',
        ];
    }
}
