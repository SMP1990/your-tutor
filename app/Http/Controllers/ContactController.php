<?php

namespace App\Http\Controllers;

use App\Http\Requests\ContactRequest;
use App\Models\ContactMessage;

class ContactController extends Controller
{
    public function create()
    {
        return view('pages.contact', ['topics' => ContactRequest::TOPICS]);
    }

    /** Saves the message for the admin inbox (Step 6c). No email is sent yet. */
    public function store(ContactRequest $request)
    {
        $data = $request->validated();

        // The hidden "website" field is for bots: people never see it. Pretend it worked, save nothing.
        if (! $request->filled('website')) {
            (new ContactMessage)->forceFill([
                'user_id' => $request->user()?->id,
                'name' => trim($data['name']),
                'phone' => $data['phone'] ?: null,
                'email' => filled($data['email'] ?? null) ? mb_strtolower(trim($data['email'])) : null,
                'topic' => $data['topic'],
                'message' => trim($data['message']),
            ])->save();
        }

        return redirect()->route('contact.sent')->with('contact_name', $data['name']);
    }

    public function sent()
    {
        return view('pages.contact-sent');
    }
}
