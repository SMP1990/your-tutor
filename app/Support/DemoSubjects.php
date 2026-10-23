<?php

namespace App\Support;

/**
 * Dummy subjects used until the database step (same keys as the future Subject model).
 * 'match' lists tutor subjects counted for this page; 'match_levels' matches tutor levels instead.
 */
class DemoSubjects
{
    public static function all(): array
    {
        return self::SUBJECTS;
    }

    public static function find(string $slug): ?array
    {
        foreach (self::SUBJECTS as $subject) {
            if ($subject['slug'] === $slug) {
                return $subject;
            }
        }

        return null;
    }

    public static function tutorsFor(array $subject): array
    {
        return array_values(array_filter(DemoTutors::all(), function ($tutor) use ($subject) {
            if (array_intersect($tutor['subjects'], $subject['match'] ?? [$subject['name']])) {
                return true;
            }
            foreach ($subject['match_levels'] ?? [] as $level) {
                if (str_contains($tutor['levels'], $level)) {
                    return true;
                }
            }

            return false;
        }));
    }

    private const SUBJECTS = [
        ['slug' => 'mathematics', 'name' => 'Mathematics', 'levels' => 'Grade 1 – A Level',
         'intro' => 'From times tables to calculus. Tutors explain each method step by step and set practice that matches your syllabus and exam board.',
         'topics' => ['Arithmetic and fractions', 'Algebra and equations', 'Geometry and trigonometry', 'Calculus (FSc, A Level)', 'Statistics and probability', 'Past paper practice']],
        ['slug' => 'physics', 'name' => 'Physics', 'levels' => 'Matric · FSc · O/A Level',
         'intro' => 'Concepts made clear and numericals made simple. Practice with past papers for Punjab and Federal boards and Cambridge exams.',
         'topics' => ['Motion and forces', 'Work, energy and power', 'Waves and optics', 'Electricity and magnetism', 'Modern physics', 'Numericals and past papers']],
        ['slug' => 'chemistry', 'name' => 'Chemistry', 'levels' => 'Matric · FSc · O/A Level',
         'intro' => 'Understand reactions instead of memorising them. Tutors cover theory, numericals and practical questions.',
         'topics' => ['Atomic structure and bonding', 'Stoichiometry', 'Organic chemistry', 'Chemical equilibrium', 'Electrochemistry', 'Practical-based questions']],
        ['slug' => 'biology', 'name' => 'Biology', 'levels' => 'Matric · FSc · MDCAT',
         'intro' => 'Clear diagrams, key terms and MCQ practice, with a strong focus on FSc and MDCAT preparation.',
         'topics' => ['Cell biology', 'Human physiology', 'Genetics and evolution', 'Plant biology', 'Ecology', 'MDCAT-style MCQs']],
        ['slug' => 'english', 'name' => 'English', 'levels' => 'All grades · IELTS',
         'intro' => 'Grammar, reading, writing and speaking. Help for school English, O Level English and IELTS preparation.',
         'topics' => ['Grammar and vocabulary', 'Reading comprehension', 'Essay and letter writing', 'Spoken English', 'O Level English Language', 'IELTS preparation']],
        ['slug' => 'urdu', 'name' => 'Urdu', 'levels' => 'All grades',
         'intro' => 'Reading, writing and grammar for school Urdu, plus Urdu for children living overseas.',
         'topics' => ['Reading and pronunciation', 'Grammar (qawaid)', 'Essay (mazmoon) writing', 'Poetry and prose', 'Board exam preparation', 'Urdu for overseas children']],
        ['slug' => 'computer-science', 'name' => 'Computer Science', 'levels' => 'Matric · O/A Level',
         'intro' => 'Theory and programming for school and Cambridge Computer Science, taught with small hands-on projects.',
         'topics' => ['Programming basics', 'Algorithms and pseudocode', 'Data representation', 'Networks and the internet', 'Databases and SQL', 'Paper 2 practice'],
         'match' => ['Computer Science', 'Python']],
        ['slug' => 'quran-islamiat', 'name' => 'Quran & Islamiat', 'levels' => 'All ages',
         'intro' => 'Quran reading with tajweed and Islamiat for school and O Level, taught by qualified teachers.',
         'topics' => ['Noorani Qaida', 'Quran reading with tajweed', 'Hifz support', 'Islamiat for school', 'O Level Islamiat', 'Duas and basic fiqh']],
        ['slug' => 'accounting', 'name' => 'Accounting', 'levels' => 'ICS · I.Com · A Level',
         'intro' => 'Double entry to final accounts. Clear working and exam-style practice for I.Com and A Level.',
         'topics' => ['Double entry and ledgers', 'Trial balance', 'Final accounts', 'Partnership accounts', 'Ratio analysis', 'Past papers']],
        ['slug' => 'coding-for-kids', 'name' => 'Coding for Kids', 'levels' => 'Ages 7–14',
         'intro' => 'Fun, project-based coding for children. Games, animations and simple apps that build real problem-solving skills.',
         'topics' => ['Scratch games', 'Logic and sequences', 'Python for beginners', 'Web pages with HTML', 'Simple apps', 'Mini projects'],
         'match' => ['Python']],
        ['slug' => 'entry-tests', 'name' => 'Entry Tests', 'levels' => 'MDCAT · ECAT · NTS',
         'intro' => 'Structured preparation for medical and engineering entry tests, with timed MCQ practice and mock papers.',
         'topics' => ['MDCAT Biology and Chemistry', 'ECAT Physics and Maths', 'English and logical reasoning', 'Timed MCQ practice', 'Full mock tests', 'Exam strategy'],
         'match' => [], 'match_levels' => ['MDCAT', 'ECAT']],
        ['slug' => 'economics', 'name' => 'Economics', 'levels' => 'O/A Level',
         'intro' => 'Micro and macro economics explained with real examples, diagrams and essay technique for Cambridge exams.',
         'topics' => ['Demand and supply', 'Market structures', 'National income', 'Inflation and unemployment', 'International trade', 'Essay technique']],
    ];
}
