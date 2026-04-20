<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Validates payload for renaming an existing Spatie permission.
 */
class UpdatePermissionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('permissions.update') ?? false;
    }

    /**
     * @return array<string, ValidationRule|array<int, mixed>|string>
     */
    public function rules(): array
    {
        $id = $this->route('permission')?->id ?? $this->route('permission');

        return [
            'name' => ['required', 'string', 'max:125', 'regex:/^[a-z0-9_.\-]+$/', Rule::unique('permissions', 'name')->ignore($id)],
            'guard_name' => ['nullable', 'string', 'max:50'],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'name.regex' => 'Name may only contain lowercase letters, numbers, dots, underscores and hyphens (e.g. products.view).',
        ];
    }
}
