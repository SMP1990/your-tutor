<?php

namespace App\Http\Controllers;

use App\Enums\ComplaintStatus;
use App\Models\ClassBooking;
use App\Models\ClassSession;
use App\Models\Complaint;
use App\Support\DashboardNav;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * Step 6b: a parent, student or tutor reports a problem with a booking or one class, and later reads
 * the admin's reply under "My reports". Only people who are part of the booking can report on it.
 */
class ComplaintController extends Controller
{
    public function index(Request $request)
    {
        return view('dashboard.complaints', [
            'complaints' => Complaint::where('user_id', $request->user()->id)
                ->with(['booking.tutorProfile.user:id,name', 'booking.subject:id,name', 'session'])
                ->latest('id')->paginate(20),
            ...$this->frame($request),
        ]);
    }

    public function createForBooking(Request $request, ClassBooking $booking)
    {
        $this->ensureParty($request, $booking);

        return $this->form($request, $booking, null);
    }

    public function createForSession(Request $request, ClassSession $session)
    {
        $booking = $session->booking;
        $this->ensureParty($request, $booking);

        return $this->form($request, $booking, $session);
    }

    public function storeForBooking(Request $request, ClassBooking $booking)
    {
        $this->ensureParty($request, $booking);

        return $this->save($request, $booking, null);
    }

    public function storeForSession(Request $request, ClassSession $session)
    {
        $booking = $session->booking;
        $this->ensureParty($request, $booking);

        return $this->save($request, $booking, $session);
    }

    private function save(Request $request, ClassBooking $booking, ?ClassSession $session)
    {
        $data = $request->validate([
            'category' => ['required', Rule::in(array_keys(Complaint::CATEGORIES))],
            'details' => ['required', 'string', 'min:'.Complaint::MIN_DETAILS, 'max:'.Complaint::MAX_DETAILS],
        ], [
            'category.required' => 'Choose what the problem is about.',
            'details.min' => 'Please write at least '.Complaint::MIN_DETAILS.' characters.',
        ]);

        $open = Complaint::where('user_id', $request->user()->id)->where('class_booking_id', $booking->id)
            ->where('status', '!=', ComplaintStatus::Closed)->count();
        abort_if($open >= Complaint::MAX_OPEN_PER_BOOKING, 409,
            'You already have '.Complaint::MAX_OPEN_PER_BOOKING.' open reports about this booking. Please wait for our reply.');

        (new Complaint)->forceFill([
            'user_id' => $request->user()->id,
            'class_booking_id' => $booking->id,
            'class_session_id' => $session?->id,
            'category' => $data['category'],
            'details' => trim($data['details']),
        ])->save();

        return redirect()->route('complaints.index')->with('notice', 'Thank you. We have your report and will reply here.');
    }

    private function form(Request $request, ClassBooking $booking, ?ClassSession $session)
    {
        return view('dashboard.complaint-form', [
            'booking' => $booking->load(['tutorProfile.user:id,name', 'user:id,name', 'subject:id,name']),
            'session' => $session,
            'action' => $session ? route('complaints.session.store', $session) : route('complaints.booking.store', $booking),
            'categories' => Complaint::CATEGORIES,
            'isTutor' => $request->user()->tutorProfile?->id === $booking->tutor_profile_id,
            ...$this->frame($request),
        ]);
    }

    /** The family that booked, or the tutor it was booked with. Anyone else gets a 404. */
    private function ensureParty(Request $request, ClassBooking $booking): void
    {
        $user = $request->user();
        $isFamily = $booking->user_id === $user->id;
        $isTutor = $user->tutorProfile !== null && $user->tutorProfile->id === $booking->tutor_profile_id;

        abort_unless($isFamily || $isTutor, 404);
    }

    private function frame(Request $request): array
    {
        return [
            'nav' => DashboardNav::for($request->user()->role->value),
            'user' => ['name' => $request->user()->name, 'role' => $request->user()->role->label()],
        ];
    }
}
