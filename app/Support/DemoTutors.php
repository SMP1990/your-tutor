<?php

namespace App\Support;

/**
 * Dummy tutors used until the database step. Keep the array keys the same as the
 * future Tutor model attributes so views do not change when real data arrives.
 */
class DemoTutors
{
    public static function all(): array
    {
        return array_map([self::class, 'withDefaults'], self::TUTORS);
    }

    public static function find(string $slug): ?array
    {
        foreach (self::all() as $tutor) {
            if ($tutor['slug'] === $slug) {
                return $tutor;
            }
        }

        return null;
    }

    /** @param string[] $slugs */
    public static function only(array $slugs): array
    {
        return array_values(array_filter(self::all(), fn ($t) => in_array($t['slug'], $slugs, true)));
    }

    /** Tutors sharing a subject with the given one. */
    public static function similarTo(array $tutor, int $limit = 3): array
    {
        $similar = array_filter(self::all(), fn ($t) => $t['slug'] !== $tutor['slug']
            && array_intersect($t['subjects'], $tutor['subjects']));

        return array_slice(array_values($similar), 0, $limit);
    }

    private static function withDefaults(array $tutor): array
    {
        $firstName = strtolower(strtok($tutor['name'], ' '));

        return $tutor + [
            'photo' => null,
            'photo_url' => asset("images/avatars/{$firstName}.svg"),
            'city' => 'Lahore',
            'experience_years' => 5,
            'students_taught' => 60,
            'about' => [
                $tutor['bio'],
                'Classes are one-to-one and online. Every class ends with a short summary for parents and practice work for the week.',
            ],
            'qualifications' => [
                ['title' => 'Master\'s degree in '.$tutor['subjects'][0], 'place' => 'University of the Punjab', 'year' => '2017'],
            ],
            'availability' => [
                'Mon' => ['evening'], 'Tue' => ['evening'], 'Wed' => ['afternoon', 'evening'],
                'Thu' => ['evening'], 'Fri' => [], 'Sat' => ['morning', 'afternoon'], 'Sun' => ['morning'],
            ],
            'rating_breakdown' => [5 => 86, 4 => 10, 3 => 3, 2 => 1, 1 => 0],
            'reviews' => [
                ['quote' => 'Very patient and clear. My daughter\'s marks went up within two months.', 'name' => 'Rabia S.', 'role' => 'Parent · '.$tutor['subjects'][0], 'initials' => 'RS', 'rating' => 5],
                ['quote' => 'Explains every step and gives good practice questions before tests.', 'name' => 'Ali H.', 'role' => 'Student · '.$tutor['subjects'][0], 'initials' => 'AH', 'rating' => 5],
                ['quote' => 'Always on time and sends notes after class. Highly recommended.', 'name' => 'Farah K.', 'role' => 'Parent · '.($tutor['subjects'][1] ?? $tutor['subjects'][0]), 'initials' => 'FK', 'rating' => 4],
            ],
        ];
    }

    private const TUTORS = [
        [
            'slug' => 'ayesha-r', 'name' => 'Ayesha R.', 'subjects' => ['Physics', 'Mathematics'], 'levels' => 'FSc · O Level',
            'rating' => '4.9', 'reviews_count' => '38', 'rate' => 'PKR 1,800', 'languages' => 'Urdu, English',
            'headline' => 'MSc Physics tutor for FSc and O Level',
            'bio' => 'MSc Physics. Makes numericals simple with step-by-step practice and past papers.',
            'experience_years' => 7, 'students_taught' => 120,
            'qualifications' => [
                ['title' => 'MSc Physics', 'place' => 'University of the Punjab', 'year' => '2016'],
                ['title' => 'B.Ed', 'place' => 'Allama Iqbal Open University', 'year' => '2018'],
            ],
        ],
        [
            'slug' => 'hamza-s', 'name' => 'Hamza S.', 'subjects' => ['Computer Science', 'Python'], 'levels' => 'Matric · A Level',
            'rating' => '4.8', 'reviews_count' => '21', 'rate' => 'PKR 2,200', 'languages' => 'English, Urdu',
            'headline' => 'Software engineer teaching coding and Computer Science',
            'bio' => 'Software engineer teaching coding through small projects students build themselves.',
            'city' => 'Karachi',
        ],
        [
            'slug' => 'mehwish-t', 'name' => 'Mehwish T.', 'subjects' => ['Biology', 'Chemistry'], 'levels' => 'FSc · MDCAT',
            'rating' => '5.0', 'reviews_count' => '14', 'rate' => 'PKR 2,000', 'languages' => 'Urdu, English, Punjabi',
            'headline' => 'Medical student for FSc Biology and MDCAT',
            'bio' => 'Medical student focused on concept clarity and MDCAT-style MCQ practice.',
            'experience_years' => 3,
        ],
        [
            'slug' => 'usman-f', 'name' => 'Usman F.', 'subjects' => ['Physics', 'Chemistry'], 'levels' => 'FSc · A Level',
            'rating' => '4.8', 'reviews_count' => '52', 'rate' => 'PKR 2,500', 'languages' => 'English, Urdu',
            'headline' => 'A Level Physics and Chemistry with exam technique',
            'bio' => 'Engineer and A Level examiner-style marking. Focus on exam technique and timing.',
            'city' => 'Islamabad', 'experience_years' => 9,
        ],
        [
            'slug' => 'sana-n', 'name' => 'Sana N.', 'subjects' => ['Physics', 'Biology'], 'levels' => 'FSc · MDCAT',
            'rating' => '4.9', 'reviews_count' => '27', 'rate' => 'PKR 1,500', 'languages' => 'Urdu, English',
            'headline' => 'MBBS student for FSc and MDCAT preparation',
            'bio' => 'MBBS student. Short concept videos plus weekly MCQ tests for medical aspirants.',
            'experience_years' => 3,
        ],
        [
            'slug' => 'bilal-k', 'name' => 'Bilal K.', 'subjects' => ['Physics', 'Mathematics'], 'levels' => 'Matric · FSc',
            'rating' => '4.7', 'reviews_count' => '19', 'rate' => 'PKR 1,200', 'languages' => 'Urdu, Punjabi',
            'headline' => 'Punjab Board Physics and Maths, ten years of teaching',
            'bio' => 'Ten years teaching Punjab Board. Clear notes and chapter-wise practice tests.',
            'city' => 'Faisalabad', 'experience_years' => 10,
        ],
        [
            'slug' => 'zainab-h', 'name' => 'Zainab H.', 'subjects' => ['Physics'], 'levels' => 'O Level · A Level',
            'rating' => '5.0', 'reviews_count' => '11', 'rate' => 'PKR 3,000', 'languages' => 'English',
            'headline' => 'Cambridge-trained O and A Level Physics teacher',
            'bio' => 'Cambridge-trained teacher. Practical-based learning with lab simulations.',
            'city' => 'Karachi', 'experience_years' => 8,
        ],
        [
            'slug' => 'omar-a', 'name' => 'Omar A.', 'subjects' => ['Physics', 'Computer Science'], 'levels' => 'FSc · ECAT',
            'rating' => '4.6', 'reviews_count' => '33', 'rate' => 'PKR 1,600', 'languages' => 'Urdu, English',
            'headline' => 'ECAT preparation with timed mock papers',
            'bio' => 'ECAT preparation with shortcut methods and full-length timed mock papers.',
            'city' => 'Peshawar', 'experience_years' => 6,
        ],
    ];
}
