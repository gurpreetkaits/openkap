<?php

namespace App\Http\Requests;

use App\Repositories\WorkspaceRepository;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class UpdateWorkspaceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:1000'],
            'slug' => ['sometimes', 'string', 'max:255', 'regex:/^[a-z0-9-]+$/'],
            'logo_url' => ['nullable', 'url', 'max:500'],
        ];
    }

    /**
     * Configure the validator instance.
     */
    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            $name = $this->input('name');
            $user = $this->user();
            $workspaceSlug = $this->route('slug');

            if ($name && $user && $workspaceSlug) {
                $repository = app(WorkspaceRepository::class);
                $workspace = $repository->findBySlug($workspaceSlug);

                if ($workspace && ! $repository->isNameAvailableForUser($name, $user, $workspace->id)) {
                    $validator->errors()->add('name', 'You already have a workspace with this name.');
                }
            }
        });
    }

    public function messages(): array
    {
        return [
            'slug.regex' => 'The URL slug may only contain lowercase letters, numbers, and hyphens.',
            'name.max' => 'Workspace name cannot exceed 255 characters.',
            'description.max' => 'Description cannot exceed 1000 characters.',
        ];
    }
}
