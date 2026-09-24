<?php

namespace App\Http\Requests;

use App\Managers\ExtensionErrorManager;
use Illuminate\Foundation\Http\FormRequest;

class StoreExtensionErrorsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'errors' => 'required|array|min:1|max:'.ExtensionErrorManager::MAX_BATCH,
            'errors.*.context' => 'required|string|max:32',
            'errors.*.code' => 'required|string|max:64',
            'errors.*.message' => 'required|string|max:2000',
            'errors.*.stack' => 'nullable|string|max:8000',
            'errors.*.session_id' => 'nullable|string|max:64',
            'errors.*.extension_version' => 'nullable|string|max:32',
            'errors.*.browser' => 'nullable|string|max:64',
            'errors.*.platform' => 'nullable|string|max:64',
            'errors.*.detail' => 'nullable|array',
            'errors.*.occurred_at' => 'nullable|string|max:40',
        ];
    }
}
