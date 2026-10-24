<?php

namespace App\Http\Requests;

use App\Enums\Role;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/** A logged-in parent (for a child) or student (for themself) asks for a free demo (Step 3a). */
class BookDemoRequest extends FormRequest
{
    public const GRADES = ['Grade 1–5', 'Grade 6–8', 'Matric (9–10)', 'FSc / A Level', 'O Level', 'Entry tests'];

    public const TIMES = ['morning' => 'Morning', 'afternoon' => 'Afternoon', 'evening' => 'Evening'];

    public function authorize(): bool
    {
        return in_array($this->user()?->role, [Role::Parent, Role::Student], true);
    }

    protected function prepareForValidation(): void
    {
        $this->merge(['phone' => preg_replace('/[\s-]/', '', (string) $this->input('phone'))]);
    }

    public function rules(): array
    {
        $tutor = $this->route('tutor');
        $isParent = $this->user()->role === Role::Parent;

        return [
            // A parent books for one of their own children; a student books for themself.
            'child_id' => $isParent
                ? ['required', 'integer', Rule::exists('children', 'id')->where('parent_id', $this->user()->id)]
                : ['prohibited'],
            'phone' => ['required', 'regex:/^(\+92|0)3\d{9}$/'],
            'subject' => ['required', Rule::in($tutor['subject_slugs'] ?? [])],
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
            'child_id.required' => 'Choose which child the class is for.',
            'child_id.exists' => 'Choose which child the class is for.',
            'phone.regex' => 'Enter a Pakistani mobile number, e.g. 0300 1234567.',
            'contact_ok.accepted' => 'Please allow us to contact you about this demo.',
        ];
    }
}
