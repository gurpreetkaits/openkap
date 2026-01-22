<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class BulkAddPlaylistVideosRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'video_ids' => ['required', 'array', 'min:1'],
            'video_ids.*' => ['required', 'integer', 'exists:videos,id'],
        ];
    }

    public function messages(): array
    {
        return [
            'video_ids.required' => 'Please select at least one video.',
            'video_ids.array' => 'Invalid video selection.',
            'video_ids.min' => 'Please select at least one video.',
            'video_ids.*.exists' => 'One or more selected videos do not exist.',
        ];
    }
}
