<?php

namespace App\Support;

use App\Models\TutorProfile;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

/** Find a Tutor: turns the GET filters into a query over approved tutors. */
class TutorSearch
{
    public const SORTS = [
        'recommended' => 'Recommended',
        'rating' => 'Highest rated',
        'price-asc' => 'Price: low to high',
        'price-desc' => 'Price: high to low',
        'newest' => 'Newest',
    ];

    public const RATINGS = ['4.5' => '4.5 and above', '4.0' => '4.0 and above', '3.5' => '3.5 and above'];

    public const PER_PAGE = 12;

    /** @return array{q: string, subjects: string[], level: ?string, min: ?int, max: ?int, rating: ?string, sort: string} */
    public static function filters(Request $request): array
    {
        $levels = config('tutors.levels');
        $int = fn ($v) => ($v = (int) preg_replace('/\D/', '', (string) $v)) > 0 ? $v : null;

        return [
            'q' => mb_substr(trim((string) $request->query('q')), 0, 80),
            'subjects' => array_values(array_filter((array) $request->query('subject', []), 'is_string')),
            'level' => in_array($request->query('level'), $levels, true) ? $request->query('level') : null,
            'min' => $int($request->query('min')),
            'max' => $int($request->query('max')),
            'rating' => array_key_exists((string) $request->query('rating'), self::RATINGS) ? (string) $request->query('rating') : null,
            'sort' => array_key_exists((string) $request->query('sort'), self::SORTS) ? (string) $request->query('sort') : 'recommended',
        ];
    }

    public static function run(array $f): LengthAwarePaginator
    {
        $query = TutorProfile::approved()->with(['user:id,name', 'subjects:id,name,slug']);

        if ($f['q'] !== '') {
            $like = '%'.str_replace(['%', '_'], ['\%', '\_'], $f['q']).'%';
            $query->where(fn (Builder $w) => $w->where('headline', 'like', $like)->orWhere('bio', 'like', $like)
                ->orWhereHas('user', fn (Builder $u) => $u->where('name', 'like', $like))
                ->orWhereHas('subjects', fn (Builder $s) => $s->where('name', 'like', $like)));
        }
        if ($f['subjects']) {
            $query->whereHas('subjects', fn (Builder $s) => $s->whereIn('slug', $f['subjects']));
        }
        if ($f['level']) {
            $query->where('levels', 'like', '%'.$f['level'].'%');
        }
        if ($f['min']) {
            $query->where('hourly_rate_minor', '>=', $f['min'] * 100);
        }
        if ($f['max']) {
            $query->where('hourly_rate_minor', '<=', $f['max'] * 100);
        }
        if ($f['rating']) {
            $query->where('rating_avg', '>=', (float) $f['rating']);
        }

        match ($f['sort']) {
            'rating' => $query->orderByDesc('rating_avg')->orderByDesc('reviews_count'),
            'price-asc' => $query->orderBy('hourly_rate_minor'),
            'price-desc' => $query->orderByDesc('hourly_rate_minor'),
            'newest' => $query->orderByDesc('approved_at'),
            default => $query->orderByDesc('reviews_count')->orderByDesc('rating_avg'),
        };

        return $query->orderBy('id')->paginate(self::PER_PAGE)->withQueryString()
            ->through(fn (TutorProfile $p) => $p->toPublicArray());
    }
}
