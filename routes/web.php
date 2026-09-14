<?php

use App\Http\Controllers\Admin;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\BookingController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\LocationController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\Vendor;
use App\Http\Controllers\VenueController;
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
});
Route::post('/logout', [AuthController::class, 'logout'])->middleware('auth')->name('logout');

// Customer (any signed-in user can book; the payment step is the modal inside the booking builder)
Route::middleware('auth')->group(function () {
    Route::get('/my-bookings', [BookingController::class, 'index'])->name('bookings.index');
    Route::get('/my-bookings/{booking}', [BookingController::class, 'show'])->name('bookings.show');
    Route::post('/my-bookings/{booking}/cancel', [BookingController::class, 'cancel'])->name('bookings.cancel');
    Route::post('/my-bookings/{booking}/proof', [BookingController::class, 'uploadProof'])->middleware('throttle:10,1')->name('bookings.proof');
    Route::get('/my-bookings/{booking}/proof/{payment}', [BookingController::class, 'proof'])->name('bookings.proof.show');

    Route::get('/notifications', [NotificationController::class, 'index'])->name('notifications.index');
    Route::post('/notifications/read-all', [NotificationController::class, 'markAllRead'])->name('notifications.read-all');
});

// Vendor
Route::middleware(['auth', 'role:Vendor,SuperAdministrator'])->prefix('vendor')->name('vendor.')->group(function () {
    Route::get('/', [Vendor\DashboardController::class, 'index'])->name('dashboard');
    Route::get('/events', [Vendor\DashboardController::class, 'events'])->name('events');

    Route::resource('venues', Vendor\VenueController::class)->except(['show']);
    Route::resource('venues.services', Vendor\ServiceController::class)->except(['show']);

    Route::get('/bookings', [Vendor\BookingController::class, 'index'])->name('bookings.index');
    Route::get('/bookings/{booking}', [Vendor\BookingController::class, 'show'])->name('bookings.show');
    Route::post('/bookings/{booking}/confirm', [Vendor\BookingController::class, 'confirm'])->name('bookings.confirm');
    Route::post('/bookings/{booking}/paid', [Vendor\BookingController::class, 'markPaid'])->name('bookings.paid');
    Route::post('/bookings/{booking}/cancel', [Vendor\BookingController::class, 'cancel'])->name('bookings.cancel');
    Route::post('/bookings/{booking}/complete', [Vendor\BookingController::class, 'complete'])->name('bookings.complete');
});

// Admin (moderators & marketing can look around; only super admins moderate vendors and change settings)
Route::middleware(['auth', 'role:SuperAdministrator,Moderator,MarketingManager'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/', [Admin\DashboardController::class, 'index'])->name('dashboard');
    Route::resource('users', Admin\UserController::class)->only(['index', 'edit', 'update', 'destroy']);
    Route::get('/venues', [Admin\VenueController::class, 'index'])->name('venues.index');
    Route::post('/venues/{venue}/approval', [Admin\VenueController::class, 'toggleApproval'])->name('venues.approval');
    Route::post('/venues/{venue}/featured', [Admin\VenueController::class, 'toggleFeatured'])->name('venues.featured');
    Route::delete('/venues/{venue}', [Admin\VenueController::class, 'destroy'])->name('venues.destroy');
    Route::resource('activity-types', Admin\ActivityTypeController::class)->except(['show']);
    Route::resource('games', Admin\GameController::class)->except(['show']);

    Route::get('/vendors', [Admin\VendorController::class, 'index'])->name('vendors.index');
    Route::get('/vendors/{vendorProfile}', [Admin\VendorController::class, 'show'])->name('vendors.show');
    Route::get('/vendors/{vendorProfile}/documents/{type}', [Admin\VendorController::class, 'document'])->name('vendors.document');

    Route::middleware('role:SuperAdministrator')->group(function () {
        Route::post('/vendors/{vendorProfile}/status', [Admin\VendorController::class, 'updateStatus'])->name('vendors.status');
        Route::get('/settings', [Admin\SettingController::class, 'edit'])->name('settings.edit');
        Route::put('/settings', [Admin\SettingController::class, 'update'])->name('settings.update');
    });
});
