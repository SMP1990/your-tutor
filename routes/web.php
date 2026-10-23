<?php

use App\Http\Controllers\ContactController;
use App\Http\Controllers\DemoBookingController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\TutorController;
use App\Support\DemoTutors;
use Illuminate\Support\Facades\Route;

// Dummy tutors until the database step; unknown slugs give a 404.
Route::bind('tutor', fn (string $slug) => DemoTutors::find($slug) ?? abort(404));

Route::get('/', HomeController::class)->name('home');
Route::get('/tutors', [TutorController::class, 'index'])->name('tutors.index');
Route::get('/tutors/{tutor}', [TutorController::class, 'show'])->name('tutors.show');

Route::get('/tutors/{tutor}/demo', [DemoBookingController::class, 'create'])->name('tutors.demo');
Route::post('/tutors/{tutor}/demo', [DemoBookingController::class, 'store'])
    ->middleware('throttle:10,1')
    ->name('tutors.demo.store');
Route::get('/tutors/{tutor}/demo/sent', [DemoBookingController::class, 'sent'])->name('tutors.demo.sent');

Route::view('/how-it-works', 'pages.how-it-works')->name('how-it-works');
Route::view('/pricing', 'pages.pricing')->name('pricing');
Route::view('/become-a-tutor', 'pages.become-a-tutor')->name('become-a-tutor');
Route::view('/about', 'pages.about')->name('about');
Route::view('/faqs', 'pages.faqs')->name('faqs');
Route::view('/terms', 'pages.terms')->name('terms');
Route::view('/privacy', 'pages.privacy')->name('privacy');
Route::view('/refund-policy', 'pages.refund-policy')->name('refund-policy');

Route::get('/contact', [ContactController::class, 'create'])->name('contact');
Route::post('/contact', [ContactController::class, 'store'])->middleware('throttle:5,1')->name('contact.store');
Route::get('/contact/sent', [ContactController::class, 'sent'])->name('contact.sent');
