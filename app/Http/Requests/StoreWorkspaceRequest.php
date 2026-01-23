<?php

namespace App\Http\Requests;

use App\Repositories\WorkspaceRepository;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class StoreWorkspaceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:1000'],
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

            if ($name && $user) {
                $repository = app(WorkspaceRepository::class);
                if (! $repository->isNameAvailableForUser($name, $user)) {
                    $validator->errors()->add('name', 'You already have a workspace with this name.');
                }
            }
        });
    }

    public function messages(): array
    {
        return [
            'name.required' => 'Workspace name is required.',
            'name.max' => 'Workspace name cannot exceed 255 characters.',
            'description.max' => 'Description cannot exceed 1000 characters.',
        ];
    }
}
