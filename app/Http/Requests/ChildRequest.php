<?php

namespace App\Http\Requests;

use App\Support\Grades;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/** Child details. Only first name and grade: we keep as little as possible about children. */
class ChildRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // checked by ChildPolicy in the controller
    }

    protected function prepareForValidation(): void
    {
        $this->merge(['name' => trim((string) $this->input('name'))]);
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:40'],
            'grade' => ['required', Rule::in(Grades::ALL)],
        ];
    }

    public function attributes(): array
    {
        return ['name' => "child's first name"];
    }
}
