<?php

use App\Http\Controllers\Admin\DemoRequestController as AdminDemoRequestController;
use App\Http\Controllers\Admin\TutorReviewController;
use App\Http\Controllers\Auth\EmailVerificationController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\PasswordResetController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\Auth\TwoFactorController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\AuthPreviewController;
use App\Http\Controllers\ChildController;
use App\Http\Controllers\ClassBookingController;
use App\Http\Controllers\ContactController;
use App\Http\Controllers\DashboardPreviewController;
use App\Http\Controllers\DemoBookingController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\SeoController;
use App\Http\Controllers\SubjectController;
use App\Http\Controllers\TutorApplicationController;
use App\Http\Controllers\TutorController;
use App\Http\Controllers\TimetableController;
use App\Http\Controllers\TutorBookingController;
use App\Http\Controllers\TutorDemoController;
use Illuminate\Support\Facades\Route;

// Note: route bindings live in AppServiceProvider, because this file is not loaded once routes are cached.

Route::get('/', HomeController::class)->name('home');
Route::get('/tutors', [TutorController::class, 'index'])->name('tutors.index');
Route::get('/tutors/{tutor}', [TutorController::class, 'show'])->name('tutors.show');

Route::get('/tutors/{tutor}/demo', [DemoBookingController::class, 'create'])->name('tutors.demo');
Route::post('/tutors/{tutor}/demo', [DemoBookingController::class, 'store'])
    ->middleware(['auth', 'verified', 'throttle:10,1'])
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

// Dashboard previews with sample data (public, for the static site). Remove at launch, see docs/DEPLOY.md.
foreach (['student', 'parent', 'tutor', 'admin'] as $dashboard) {
    Route::get("/preview/{$dashboard}", [DashboardPreviewController::class, 'show'])
        ->defaults('dashboard', $dashboard)
        ->name("preview.{$dashboard}");
}

Route::get('/sitemap.xml', [SeoController::class, 'sitemap'])->name('sitemap');
Route::get('/robots.txt', [SeoController::class, 'robots'])->name('robots');

// Real dashboards: logged in, email verified, own role only (Step 1d).
Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'redirect'])->name('dashboard');
    Route::get('/dashboard/student', [DashboardController::class, 'student'])->middleware('role:student')->name('dashboard.student');
    Route::get('/dashboard/parent', [DashboardController::class, 'parent'])->middleware('role:parent')->name('dashboard.parent');
    Route::middleware('role:parent')->prefix('/dashboard/parent/children')->name('children.')->controller(ChildController::class)->group(function () {
        Route::get('/new', 'create')->name('create');
        Route::post('/', 'store')->middleware('throttle:20,1')->name('store');
        Route::get('/{child}/edit', 'edit')->name('edit');
        Route::put('/{child}', 'update')->middleware('throttle:20,1')->name('update');
        Route::delete('/{child}', 'destroy')->middleware('throttle:20,1')->name('destroy');
    });
    Route::post('/dashboard/demos/{demo}/cancel', [DemoBookingController::class, 'cancel'])
        ->middleware(['role:parent,student', 'throttle:20,1'])->name('demos.cancel');
    Route::get('/dashboard/demos/{demo}/join', [DemoBookingController::class, 'join'])
        ->middleware(['role:parent,student', 'throttle:30,1'])->name('demos.join');
    Route::middleware('role:parent,student,tutor')->controller(TimetableController::class)->group(function () {
        Route::get('/dashboard/classes', 'index')->name('classes.index');
        Route::get('/dashboard/classes/{session}/join', 'join')->middleware('throttle:30,1')->name('classes.join');
    });
    Route::middleware('role:parent,student')->controller(ClassBookingController::class)->group(function () {
        Route::get('/dashboard/demos/{demo}/book', 'create')->name('bookings.create');
        Route::post('/dashboard/demos/{demo}/book', 'store')->middleware('throttle:10,1')->name('bookings.store');
        Route::post('/dashboard/bookings/{booking}/cancel', 'cancel')->middleware('throttle:20,1')->name('bookings.cancel');
    });
    Route::get('/dashboard/tutor', [DashboardController::class, 'tutor'])->middleware('role:tutor')->name('dashboard.tutor');
    Route::middleware('role:tutor')->controller(TutorApplicationController::class)->group(function () {
        Route::get('/dashboard/tutor/application', 'edit')->name('tutor.application');
        Route::put('/dashboard/tutor/application', 'update')->middleware('throttle:20,1')->name('tutor.application.update');
    });
    Route::middleware('role:tutor')->prefix('/dashboard/tutor/demos')->name('tutor.demos')->controller(TutorDemoController::class)->group(function () {
        Route::get('/', 'index');
        Route::post('/{demo}/accept', 'accept')->middleware('throttle:30,1')->name('.accept');
        Route::post('/{demo}/decline', 'decline')->middleware('throttle:30,1')->name('.decline');
        Route::post('/{demo}/finish', 'finish')->middleware('throttle:30,1')->name('.finish');
    });
    Route::middleware('role:tutor')->prefix('/dashboard/tutor/bookings')->name('tutor.bookings')->controller(TutorBookingController::class)->group(function () {
        Route::get('/', 'index');
        Route::post('/{booking}/accept', 'accept')->middleware('throttle:30,1')->name('.accept');
        Route::post('/{booking}/decline', 'decline')->middleware('throttle:30,1')->name('.decline');
    });

    Route::middleware('role:admin')->group(function () {
        Route::get('/two-factor', [TwoFactorController::class, 'show'])->name('two-factor.show');
        Route::post('/two-factor', [TwoFactorController::class, 'store'])->middleware('throttle:10,1')->name('two-factor.store');
        Route::post('/two-factor/resend', [TwoFactorController::class, 'resend'])->middleware('throttle:3,1')->name('two-factor.resend');

        Route::get('/admin', [DashboardController::class, 'admin'])->middleware('two-factor')->name('dashboard.admin');

        Route::get('/admin/demos', [AdminDemoRequestController::class, 'index'])->middleware('two-factor')->name('admin.demos.index');

        Route::middleware('two-factor')->prefix('/admin/tutors')->name('admin.tutors.')->controller(TutorReviewController::class)->group(function () {
            Route::get('/', 'index')->name('index');
            Route::get('/{profile:id}', 'show')->name('show');
            Route::get('/{profile:id}/documents/{document}', 'document')->name('document');
            Route::post('/{profile:id}/review', 'review')->middleware('throttle:30,1')->name('review');
            Route::post('/{profile:id}/decision', 'decide')->middleware('throttle:30,1')->name('decide');
            Route::post('/{profile:id}/changes', 'changes')->middleware('throttle:30,1')->name('changes');
        });
    });
});
