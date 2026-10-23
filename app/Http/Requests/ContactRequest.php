<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ContactRequest extends FormRequest
{
    public const TOPICS = ['Finding a tutor', 'Payments and refunds', 'Becoming a tutor', 'A problem with a class', 'Something else'];

    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge(['phone' => preg_replace('/[\s-]/', '', (string) $this->input('phone'))]);
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:80'],
            'phone' => ['nullable', 'regex:/^(\+92|0)3\d{9}$/', 'required_without:email'],
            'email' => ['nullable', 'email', 'max:120', 'required_without:phone'],
            'topic' => ['required', Rule::in(self::TOPICS)],
            'message' => ['required', 'string', 'min:10', 'max:2000'],
        ];
    }

    public function messages(): array
    {
        return [
            'phone.regex' => 'Enter a Pakistani mobile number, e.g. 0300 1234567.',
            'phone.required_without' => 'Give a phone number or an email so we can reply.',
            'email.required_without' => 'Give a phone number or an email so we can reply.',
        ];
    }
}
