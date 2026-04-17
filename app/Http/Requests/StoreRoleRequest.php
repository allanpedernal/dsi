<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreRoleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('roles.create') ?? false;
    }

    /** @return array<string, array<int, mixed>|string> */
    public function rules(): array
    {
        return [
            'name' => [
                'required', 'string', 'max:50',
                'regex:/^[a-z0-9_-]+$/',
                Rule::unique('roles', 'name'),
            ],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'name.regex' => 'Role name must be lowercase and may only contain letters, numbers, underscores and hyphens.',
        ];
    }
}
