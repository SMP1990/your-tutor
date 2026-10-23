<?php

namespace App\Http\Controllers;

use App\Http\Requests\BookDemoRequest;

class DemoBookingController extends Controller
{
    public function create(array $tutor)
    {
        return view('tutors.demo', [
            'tutor' => $tutor,
            'grades' => BookDemoRequest::GRADES,
            'times' => BookDemoRequest::TIMES,
        ]);
    }

    /** Validates only. Saving and notifying the tutor come with the database step. */
    public function store(BookDemoRequest $request, array $tutor)
    {
        return redirect()
            ->route('tutors.demo.sent', $tutor['slug'])
            ->with('demo_name', $request->validated('name'));
    }

    public function sent(array $tutor)
    {
        return view('tutors.demo-sent', ['tutor' => $tutor]);
    }
}
