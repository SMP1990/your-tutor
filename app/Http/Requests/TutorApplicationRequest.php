<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\File;

/**
 * Saving the tutor application. Everything may be left empty while it is a draft (completeness is
 * checked on submit), but whatever is filled in must be valid.
 */
class TutorApplicationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // TutorApplicationController checks role and status
    }

    protected function prepareForValidation(): void
    {
        $education = collect($this->input('education', []))
            ->map(fn ($row) => array_map(fn ($v) => is_string($v) ? trim($v) : $v, (array) $row))
            ->filter(fn ($row) => filled($row['degree'] ?? null) || filled($row['institute'] ?? null))
            ->values()->all();

        $this->merge(['education' => $education, 'rate' => preg_replace('/[^\d]/', '', (string) $this->input('rate'))]);
    }

    public function rules(): array
    {
        $c = config('tutors');
        $slots = array_keys($c['time_slots']);

        // A live (approved) profile must stay complete: these cannot be emptied.
        if ($this->user()->tutorProfile()->first()?->isLive()) {
            return array_merge_recursive($this->baseRules($c, $slots), [
                'headline' => ['required'], 'bio' => ['required', 'min:80'], 'city' => ['required'],
                'rate' => ['required'], 'languages' => ['required'], 'levels' => ['required'],
                'subjects' => ['required'], 'availability' => ['required'], 'education' => ['min:1'],
            ]);
        }

        return $this->baseRules($c, $slots);
    }

    private function baseRules(array $c, array $slots): array
    {
        return [
            'headline' => ['nullable', 'string', 'max:120'],
            'bio' => ['nullable', 'string', 'max:2000'],
            'city' => ['nullable', 'string', 'max:60'],
            'experience_years' => ['nullable', 'integer', 'min:0', 'max:60'],
            'rate' => ['nullable', 'integer', "min:{$c['rate_min']}", "max:{$c['rate_max']}"],
            'intro_video_url' => ['nullable', 'url:https', 'max:255'],

            'languages' => ['nullable', 'array'],
            'languages.*' => [Rule::in($c['languages'])],
            'levels' => ['nullable', 'array'],
            'levels.*' => [Rule::in($c['levels'])],
            'subjects' => ['nullable', 'array', "max:{$c['max_subjects']}"],
            'subjects.*' => ['integer', Rule::exists('subjects', 'id')->where('is_active', true)],

            'availability' => ['nullable', 'array'],
            'availability.*' => ['array'],
            'availability.*.*' => [Rule::in($slots)],

            'education' => ['array', "max:{$c['max_education']}"],
            'education.*.degree' => ['required', 'string', 'max:80'],
            'education.*.institute' => ['required', 'string', 'max:120'],
            'education.*.year' => ['nullable', 'integer', 'min:1960', 'max:'.(date('Y') + 6)],

            'photo' => ['nullable', File::image()->types(['jpg', 'jpeg', 'png', 'webp'])->max($c['photo_max_kb'])],
            'cnic_front' => ['nullable', File::types(['jpg', 'jpeg', 'png', 'pdf'])->max($c['document_max_kb'])],
            'cnic_back' => ['nullable', File::types(['jpg', 'jpeg', 'png', 'pdf'])->max($c['document_max_kb'])],
            'degree' => ['nullable', File::types(['jpg', 'jpeg', 'png', 'pdf'])->max($c['document_max_kb'])],
        ];
    }

    public function messages(): array
    {
        $c = config('tutors');

        return [
            'rate.min' => "The hourly rate must be at least PKR {$c['rate_min']}.",
            'rate.max' => "The hourly rate can be at most PKR ".number_format($c['rate_max']).'.',
            'education.*.degree.required' => 'Enter the degree for each education row.',
            'education.*.institute.required' => 'Enter the institute for each education row.',
            'intro_video_url.url' => 'Use a full https:// link (YouTube or Google Drive).',
        ];
    }

    /** Validated data in the shape of the TutorProfile columns. */
    public function profileData(): array
    {
        $v = $this->validated();
        $days = config('tutors.days');

        return [
            'headline' => $v['headline'] ?? null,
            'bio' => $v['bio'] ?? null,
            'city' => $v['city'] ?? null,
            'experience_years' => $v['experience_years'] ?? null,
            'hourly_rate_minor' => isset($v['rate']) && $v['rate'] !== '' ? (int) $v['rate'] * 100 : null,
            'intro_video_url' => $v['intro_video_url'] ?? null,
            'languages' => implode(', ', $v['languages'] ?? []) ?: null,
            'levels' => implode(' · ', $v['levels'] ?? []) ?: null,
            'availability' => collect($days)->mapWithKeys(fn ($d) => [$d => array_values($v['availability'][$d] ?? [])])->all(),
            'education' => $v['education'],
        ];
    }
}
