<?php

use App\Http\Controllers\Auth\EmailVerificationController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\PasswordResetController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\AuthPreviewController;
use App\Http\Controllers\ContactController;
use App\Http\Controllers\DashboardPreviewController;
use App\Http\Controllers\DemoBookingController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\SeoController;
use App\Http\Controllers\SubjectController;
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

Route::get('/subjects', [SubjectController::class, 'index'])->name('subjects.index');
Route::get('/subjects/{slug}', [SubjectController::class, 'show'])->name('subjects.show');

// Accounts: register, email verification (Step 1b), log in / out, password reset (Step 1c).
Route::middleware('guest')->group(function () {
    Route::get('/register', [RegisterController::class, 'create'])->name('register');
    Route::post('/register', [RegisterController::class, 'store'])->middleware('throttle:10,1')->name('register.store');

    Route::get('/login', [LoginController::class, 'create'])->name('login');
    Route::post('/login', [LoginController::class, 'store'])->middleware('throttle:20,1')->name('login.attempt');

    Route::get('/forgot-password', [PasswordResetController::class, 'create'])->name('password.request');
    Route::post('/forgot-password', [PasswordResetController::class, 'store'])->middleware('throttle:5,1')->name('password.email');
    Route::get('/reset-password/{token}', [PasswordResetController::class, 'edit'])->name('password.reset');
    Route::post('/reset-password', [PasswordResetController::class, 'update'])->middleware('throttle:5,1')->name('password.update');
});
Route::post('/logout', [LoginController::class, 'destroy'])->middleware('auth')->name('logout');
Route::get('/email/verify', [EmailVerificationController::class, 'notice'])->middleware('auth')->name('verification.notice');
Route::get('/email/verify/{id}/{hash}', [EmailVerificationController::class, 'verify'])
    ->middleware(['signed', 'throttle:6,1'])->name('verification.verify');
Route::post('/email/verification-notification', [EmailVerificationController::class, 'resend'])
    ->middleware(['auth', 'throttle:3,1'])->name('verification.send');

// Phone code page: design only until an SMS/WhatsApp OTP provider is added (see AuthPreviewController).
Route::get('/verify-phone', [AuthPreviewController::class, 'verifyPhone'])->name('verify-phone');
Route::post('/verify-phone', [AuthPreviewController::class, 'notYet'])->middleware('throttle:10,1')->name('verify-phone.check');

// Dashboards: design only with sample data, see DashboardPreviewController.
foreach (['student', 'parent', 'tutor', 'admin'] as $dashboard) {
    Route::get("/{$dashboard}", [DashboardPreviewController::class, 'show'])
        ->defaults('dashboard', $dashboard)
        ->name("dashboard.{$dashboard}");
}

Route::get('/sitemap.xml', [SeoController::class, 'sitemap'])->name('sitemap');
Route::get('/robots.txt', [SeoController::class, 'robots'])->name('robots');
