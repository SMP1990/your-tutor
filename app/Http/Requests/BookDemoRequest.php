<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class BookDemoRequest extends FormRequest
{
    public const GRADES = ['Grade 1–5', 'Grade 6–8', 'Matric (9–10)', 'FSc / A Level', 'O Level', 'Entry tests'];

    public const TIMES = ['morning' => 'Morning', 'afternoon' => 'Afternoon', 'evening' => 'Evening'];

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
        $tutor = $this->route('tutor');

        return [
            'for' => ['required', Rule::in(['child', 'self'])],
            'name' => ['required', 'string', 'max:80'],
            'student_name' => ['required_if:for,child', 'nullable', 'string', 'max:80'],
            'phone' => ['required', 'regex:/^(\+92|0)3\d{9}$/'],
            'email' => ['nullable', 'email', 'max:120'],
            'subject' => ['required', Rule::in($tutor['subjects'] ?? [])],
            'grade' => ['required', Rule::in(self::GRADES)],
            'day' => ['required', Rule::in(array_keys(array_filter($tutor['availability'] ?? [])))],
            'time' => ['required', Rule::in(array_keys(self::TIMES))],
            'notes' => ['nullable', 'string', 'max:500'],
            'contact_ok' => ['accepted'],
        ];
    }

    public function messages(): array
    {
        return [
            'phone.regex' => 'Enter a Pakistani mobile number, e.g. 0300 1234567.',
            'student_name.required_if' => 'Enter the student\'s name.',
            'contact_ok.accepted' => 'Please allow us to contact you about this demo.',
        ];
    }
}
