<?php

use App\Http\Controllers\Admin\ImpersonationController;
use App\Http\Controllers\Admin\VendorDocumentController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\BookingController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\LocationController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\VenueController;
use Illuminate\Foundation\Auth\EmailVerificationRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

// Public
Route::get('/', HomeController::class)->name('home');
Route::get('/venues', [VenueController::class, 'index'])->name('venues.index');
Route::get('/venues/{venue}', [VenueController::class, 'show'])->name('venues.show');
Route::get('/book/{service}', [BookingController::class, 'build'])->name('booking.build');

// Visitor location (browser geolocation or a chosen district) used for "near you" sorting
Route::post('/location', [LocationController::class, 'store'])->middleware('throttle:30,1')->name('location.store');
Route::delete('/location', [LocationController::class, 'destroy'])->middleware('throttle:30,1')->name('location.destroy');

// Auth
Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:10,1');
    Route::get('/register', [AuthController::class, 'showRegister'])->name('register');
    Route::post('/register', [AuthController::class, 'register'])->middleware('throttle:5,1');
    Route::get('/register/vendor', [AuthController::class, 'showVendorRegister'])->name('register.vendor');
    Route::post('/register/vendor', [AuthController::class, 'registerVendor'])->middleware('throttle:5,1');
    Route::get('/auth/google/redirect', [AuthController::class, 'redirectToGoogle'])->name('auth.google.redirect');
    Route::get('/auth/google/callback', [AuthController::class, 'handleGoogleCallback'])->name('auth.google.callback');
});
Route::post('/logout', [AuthController::class, 'logout'])->middleware('auth')->name('logout');

// Email verification is required for password-based customer and vendor registrations.
Route::middleware('auth')->group(function () {
    Route::get('/email/verify', function (Request $request) {
        if ($request->user()->hasVerifiedEmail()) {
            return redirect()->intended($request->user()->isCustomer() ? '/' : $request->user()->dashboardUrl());
        }

        return view('auth.verify-email');
    })->name('verification.notice');

    Route::get('/email/verify/{id}/{hash}', function (EmailVerificationRequest $request) {
        $request->fulfill();

        return redirect()->intended($request->user()->isCustomer() ? '/' : $request->user()->dashboardUrl())
            ->with('message', 'Your email address has been verified.');
    })->middleware(['signed', 'throttle:6,1'])->name('verification.verify');

    Route::post('/email/verification-notification', function (Request $request) {
        if ($request->user()->hasVerifiedEmail()) {
            return redirect()->intended($request->user()->isCustomer() ? '/' : $request->user()->dashboardUrl());
        }

        $request->user()->sendEmailVerificationNotification();

        return back()->with('message', 'A new verification link has been sent to your email address.');
    })->middleware('throttle:6,1')->name('verification.send');
});

// Staff "sign in as vendor" starts from the Filament vendor list; this ends it
Route::post('/impersonate/stop', [ImpersonationController::class, 'stop'])->middleware(['auth', 'verified'])->name('impersonate.stop');

// Customer (a verified user can book; the payment step is the modal inside the booking builder)
Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/my-bookings', [BookingController::class, 'index'])->name('bookings.index');
    Route::get('/my-bookings/{booking}', [BookingController::class, 'show'])->name('bookings.show');
    Route::post('/my-bookings/{booking}/cancel', [BookingController::class, 'cancel'])->name('bookings.cancel');
    Route::post('/my-bookings/{booking}/proof', [BookingController::class, 'uploadProof'])->middleware('throttle:10,1')->name('bookings.proof');
    Route::get('/my-bookings/{booking}/proof/{payment}', [BookingController::class, 'proof'])->name('bookings.proof.show');
    Route::get('/my-bookings/{booking}/identity/{side}', [BookingController::class, 'identityDocument'])->name('bookings.identity.show');

    Route::get('/notifications', [NotificationController::class, 'index'])->name('notifications.index');
    Route::post('/notifications/read-all', [NotificationController::class, 'markAllRead'])->name('notifications.read-all');
});

// Vendor panel (Filament) lives at /vendor — see App\Providers\Filament\VendorPanelProvider.
// Venues, services, bookings, the schedule and check-in scanning are all managed there now.

// Admin panel (Filament) lives at /admin — see App\Providers\Filament\AdminPanelProvider.
// Staff roles: moderators & marketing can manage venues, catalogue and users;
// only super admins moderate vendors and change settings.
Route::middleware(['auth', 'verified', 'role:SuperAdministrator,Moderator,MarketingManager'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/vendors/{vendorProfile}/documents/{type}', VendorDocumentController::class)->name('vendors.document');
});
