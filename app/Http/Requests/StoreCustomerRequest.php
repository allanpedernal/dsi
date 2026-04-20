<?php

namespace App\Http\Requests;

use App\Enums\Country;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

/**
 * Validates payload for creating a new customer. Auto-generates a `CUST-XXXXXX` code when omitted.
 */
class StoreCustomerRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('customers.create') ?? false;
    }

    /**
     * @return array<string, ValidationRule|array<int, mixed>|string>
     */
    public function rules(): array
    {
        return [
            'code' => ['nullable', 'string', 'max:20', Rule::unique('customers', 'code')],
            'first_name' => ['required', 'string', 'max:100'],
            'last_name' => ['required', 'string', 'max:100'],
            'email' => ['required', 'email', 'max:255', Rule::unique('customers', 'email')],
            'phone' => ['nullable', 'string', 'max:30'],
            'address' => ['nullable', 'string', 'max:255'],
            'city' => ['nullable', 'string', 'max:100'],
            'country' => ['nullable', Rule::in(Country::values())],
            'notes' => ['nullable', 'string'],
        ];
    }

    /** Fill in a default CUST- prefix code when the client does not supply one. */
    protected function prepareForValidation(): void
    {
        if (! $this->code) {
            $this->merge(['code' => 'CUST-'.strtoupper(Str::random(6))]);
        }
    }
}
