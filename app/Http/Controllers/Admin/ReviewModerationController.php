<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Review;
use App\Support\DashboardNav;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

/** Step 5c: admins read every review and hide (or show again) a wrong or abusive one. */
class ReviewModerationController extends Controller
{
    public const TABS = [
        'low' => 'Low ratings (1-2 stars)',
        'visible' => 'All visible',
        'hidden' => 'Hidden',
    ];

    public function index(Request $request)
    {
        $tab = array_key_exists($request->query('tab'), self::TABS) ? $request->query('tab') : 'low';

        return view('admin.reviews.index', [
            'tab' => $tab,
            'tabs' => collect(self::TABS)->map(fn ($label, $key) => ['label' => $label, 'count' => self::tab($key)->count()]),
            'reviews' => self::tab($tab)
                ->with(['user:id,name,email', 'tutorProfile:id,user_id,slug', 'tutorProfile.user:id,name'])
                ->latest()->paginate(25)->withQueryString(),
            'nav' => DashboardNav::for('admin'),
            'user' => ['name' => $request->user()->name, 'role' => $request->user()->role->label()],
        ]);
    }

    /** Hide or show a review; the tutor's rating is recalculated straight away. */
    public function toggle(Review $review)
    {
        $review->forceFill(['is_hidden' => ! $review->is_hidden])->save();
        $review->tutorProfile->refreshRating();

        return back()->with('notice', $review->is_hidden
            ? 'Review hidden. It no longer shows or counts in the rating.'
            : 'Review shown again.');
    }

    public static function tab(string $tab): Builder
    {
        $q = Review::query();

        return match ($tab) {
            'low' => $q->visible()->where('rating', '<=', 2),
            'visible' => $q->visible(),
            'hidden' => $q->where('is_hidden', true),
        };
    }
}
