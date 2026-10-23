<?php

use App\Http\Controllers\HomeController;
use App\Http\Controllers\TutorController;
use Illuminate\Support\Facades\Route;

Route::get('/', HomeController::class)->name('home');
Route::get('/tutors', [TutorController::class, 'index'])->name('tutors.index');
Route::get('/tutors/{slug}', [TutorController::class, 'show'])->name('tutors.show');
