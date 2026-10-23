<?php

namespace App\Http\Controllers;

use App\Http\Requests\ContactRequest;

class ContactController extends Controller
{
    public function create()
    {
        return view('pages.contact', ['topics' => ContactRequest::TOPICS]);
    }

    /** Validates only. Saving and emailing the message come with the database step. */
    public function store(ContactRequest $request)
    {
        return redirect()->route('contact.sent')->with('contact_name', $request->validated('name'));
    }

    public function sent()
    {
        return view('pages.contact-sent');
    }
}
