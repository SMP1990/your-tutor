<?php

namespace App\Http\Requests;

use App\Enums\Role;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class RegisterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'email' => mb_strtolower(trim((string) $this->input('email'))),
            'phone' => preg_replace('/[\s-]/', '', (string) $this->input('phone')),
        ]);
    }

    public function rules(): array
    {
        return [
            // Only parent, student or tutor; admins are never created from this form.
            'role' => ['required', Rule::in(array_map(fn (Role $r) => $r->value, Role::selfRegistration()))],
            'name' => ['required', 'string', 'max:80'],
            'phone' => ['required', 'regex:/^(\+92|0)3\d{9}$/'],
            'email' => ['required', 'email', 'max:120', 'unique:users,email'],
            'password' => ['required', 'string', 'max:72', Password::min(8)->letters()->numbers()],
            'whatsapp_ok' => ['nullable', 'boolean'],
            'terms' => ['accepted'],
        ];
    }

    public function messages(): array
    {
        return [
            'phone.regex' => 'Enter a Pakistani mobile number, e.g. 0300 1234567.',
            'email.unique' => 'An account with this email already exists. Try logging in.',
            'terms.accepted' => 'Please agree to the Terms and Privacy Policy.',
        ];
    }
}
