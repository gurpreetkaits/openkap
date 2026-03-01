<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ApplyVideoEditsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'blur_regions' => 'nullable|array|max:10',
            'blur_regions.*.x' => 'required|numeric|min:0|max:100',
            'blur_regions.*.y' => 'required|numeric|min:0|max:100',
            'blur_regions.*.width' => 'required|numeric|min:1|max:100',
            'blur_regions.*.height' => 'required|numeric|min:1|max:100',
            'blur_regions.*.start_time' => 'nullable|numeric|min:0',
            'blur_regions.*.end_time' => 'nullable|numeric|min:0',

            'overlay_configs' => 'nullable|array|max:5',
            'overlay_configs.*.x' => 'required|numeric|min:0|max:100',
            'overlay_configs.*.y' => 'required|numeric|min:0|max:100',
            'overlay_configs.*.width' => 'required|numeric|min:1|max:100',
            'overlay_configs.*.height' => 'required|numeric|min:1|max:100',
            'overlay_configs.*.file_index' => 'required|integer|min:0',
            'overlay_configs.*.start_time' => 'nullable|numeric|min:0',
            'overlay_configs.*.end_time' => 'nullable|numeric|min:0',

            'overlay_files' => 'nullable|array|max:5',
            'overlay_files.*' => 'file|mimes:webm,mp4,mov|max:512000',

            'text_overlays' => 'nullable|array|max:10',
            'text_overlays.*.text' => 'required|string|max:200',
            'text_overlays.*.x' => 'required|numeric|min:0|max:100',
            'text_overlays.*.y' => 'required|numeric|min:0|max:100',
            'text_overlays.*.font_size' => 'required|integer|min:12|max:120',
            'text_overlays.*.font_color' => 'required|string|max:20',
            'text_overlays.*.background_color' => 'nullable|string|max:20',
            'text_overlays.*.start_time' => 'nullable|numeric|min:0',
            'text_overlays.*.end_time' => 'nullable|numeric|min:0',

            'trim_start' => 'nullable|numeric|min:0',
            'trim_end' => 'nullable|numeric|gt:trim_start',

            'merge_video_id' => 'nullable|integer|exists:videos,id',
        ];
    }
}
