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
            'text_overlays.*.font_color' => ['required', 'string', 'max:20', 'regex:/^[a-zA-Z0-9#@.()]+$/'],
            'text_overlays.*.background_color' => ['nullable', 'string', 'max:20', 'regex:/^[a-zA-Z0-9#@.()]+$/'],
            'text_overlays.*.start_time' => 'nullable|numeric|min:0',
            'text_overlays.*.end_time' => 'nullable|numeric|min:0',

            'trim_start' => 'nullable|numeric|min:0',
            'trim_end' => 'nullable|numeric|gt:trim_start',

            'style_settings' => 'nullable|array',
            'style_settings.background_type' => 'nullable|string|in:none,solid,gradient,image',
            'style_settings.background_color' => 'nullable|string|max:20',
            'style_settings.gradient_from' => 'nullable|string|max:20',
            'style_settings.gradient_to' => 'nullable|string|max:20',
            'style_settings.gradient_direction' => 'nullable|string|in:b,br,r',
            'style_settings.background_image_url' => 'nullable|string|max:500',
            'style_settings.padding' => 'nullable|integer|min:0|max:200',
            'style_settings.roundness' => 'nullable|integer|min:0|max:50',
            'style_settings.shadow' => 'nullable|boolean',
            'style_settings.camera_enabled' => 'nullable|boolean',
            'style_settings.camera_position' => 'nullable|string|in:top-left,top-right,bottom-left,bottom-right',
            'style_settings.camera_size' => 'nullable|integer|min:5|max:60',
            'style_settings.camera_shape' => 'nullable|string|in:portrait,circle,square',
            'style_settings.camera_roundness' => 'nullable|integer|min:0|max:100',

            'merge_video_ids' => 'nullable|array|max:10',
            'merge_video_ids.*' => 'integer|exists:videos,id',
            'main_video_position' => 'nullable|integer|min:0',
        ];
    }
}
