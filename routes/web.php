<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\HolidayController;
use App\Http\Controllers\LandingController;
use App\Http\Controllers\LocationController;
use App\Http\Controllers\MobileController;
use App\Http\Controllers\VerificationController;
use App\Http\Middleware\EnsureLoggedIn;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/login');

Route::get('/register', [AuthController::class, 'showRegister'])->name('register');
Route::post('/register', [AuthController::class, 'register'])
    ->middleware('throttle:5,60')
    ->name('register.store');

// Dropdown data for the register page (no throttle, answers are cached).
Route::get('/locations/states', [LocationController::class, 'states'])->name('locations.states');
Route::get('/locations/cities', [LocationController::class, 'cities'])->name('locations.cities');
Route::get('/locations/zips', [LocationController::class, 'zips'])->name('locations.zips');

Route::get('/check-email', [VerificationController::class, 'checkEmail'])->name('check-email');
Route::post('/email/resend', [VerificationController::class, 'resend'])->name('email.resend');
Route::get('/verify-email', [VerificationController::class, 'verify'])->name('email.verify');

Route::get('/verify-mobile', [MobileController::class, 'show'])->name('verify-mobile');
Route::post('/verify-mobile', [MobileController::class, 'verify'])->name('verify-mobile.check');
Route::post('/mobile/resend', [MobileController::class, 'resend'])->name('mobile.resend');

Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
Route::post('/login', [AuthController::class, 'login'])->name('login.store');
Route::get('/unlock', [AuthController::class, 'unlock'])->name('unlock');
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

Route::get('/landing', [LandingController::class, 'index'])
    ->middleware(EnsureLoggedIn::class)
    ->name('landing');

// Holiday data for the landing page modal (JSON, logged-in users only, no throttle).
Route::get('/holidays/{year}', [HolidayController::class, 'show'])
    ->whereNumber('year')
    ->middleware(EnsureLoggedIn::class)
    ->name('holidays.show');