<?php

use App\Http\Controllers\ActivityLogController;
use App\Http\Controllers\AdminUserController;
use App\Http\Controllers\ApprovalController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\BookingAvailabilityController;
use App\Http\Controllers\BookingController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\MasterController;
use App\Http\Controllers\SectionController;
use Illuminate\Support\Facades\Route;

Route::get('/', DashboardController::class)->name('home');
Route::get('/gwm-lantai8', DashboardController::class)->name('home.alias');

Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'create'])->name('login');
    Route::post('/login', [AuthController::class, 'store']);
    Route::get('/register', [AuthController::class, 'register'])->name('register');
    Route::post('/register', [AuthController::class, 'registerStore']);

    // Forgot password
    Route::get('/forgot-password', [AuthController::class, 'showForgotPassword'])->name('password.request');
    Route::post('/forgot-password', [AuthController::class, 'sendResetLinkEmail'])->name('password.email');
    Route::get('/reset-password/{token}', [AuthController::class, 'showResetPassword'])->name('password.reset');
    Route::post('/reset-password', [AuthController::class, 'resetPassword'])->name('password.update');
});

Route::post('/logout', [AuthController::class, 'destroy'])->middleware('auth')->name('logout');
Route::get('/availability', BookingAvailabilityController::class)->name('availability');

Route::middleware('auth')->group(function () {
    Route::get('/bookings', [BookingController::class, 'index'])->name('bookings.index');
    Route::get('/bookings/create', [BookingController::class, 'create'])->name('bookings.create');
    Route::get('/gwm-lantai8/peminjaman', [BookingController::class, 'create'])->name('bookings.create.alias');
    Route::post('/bookings', [BookingController::class, 'store'])->name('bookings.store');
    Route::get('/bookings/{booking}', [BookingController::class, 'show'])->name('bookings.show');
    Route::get('/bookings/{booking}/change', [BookingController::class, 'change'])->name('bookings.change');
    Route::post('/bookings/{booking}/cancel', [BookingController::class, 'cancel'])->name('bookings.cancel');

    // Admin Only: Kelola User, User Role, dan Master Data
    Route::middleware('role:Admin')->prefix('admin')->group(function () {
        Route::get('/users', [AdminUserController::class, 'index'])->name('admin.users.index');
        Route::post('/users', [AdminUserController::class, 'store'])->name('admin.users.store');
        Route::post('/users/{user}/roles', [AdminUserController::class, 'assignRole'])->name('admin.users.assignRole');
        Route::post('/user-roles/{userRole}/deactivate', [AdminUserController::class, 'deactivateRole'])->name('admin.users.deactivateRole');

        Route::get('master/{type}', [MasterController::class, 'index'])->name('master.index');
        Route::get('master/{type}/create', [MasterController::class, 'create'])->name('master.create');
        Route::post('master/{type}', [MasterController::class, 'store'])->name('master.store');
        Route::get('master/{type}/{id}/edit', [MasterController::class, 'edit'])->name('master.edit');
        Route::put('master/{type}/{id}', [MasterController::class, 'update'])->name('master.update');
        Route::delete('master/{type}/{id}', [MasterController::class, 'destroy'])->name('master.destroy');
    });

    // Staf_Lab, Kepala_Prodi, Kepala_Lab: CRUD Section (Jadwal)
    Route::middleware('role:Staf_Lab,Kepala_Prodi,Kepala_Lab')->group(function () {
        Route::resource('sections', SectionController::class)->except('show');
    });

    // Staf_Lab Only: Booking atas nama dosen
    Route::middleware('role:Staf_Lab')->group(function () {
        Route::get('/staff-bookings/create', [BookingController::class, 'staffCreate'])->name('staff-bookings.create');
        Route::post('/staff-bookings', [BookingController::class, 'staffStore'])->name('staff-bookings.store');
    });

    // Role internal: Log aktivitas
    Route::middleware('role:Staf_Lab,Kepala_Prodi,Kepala_Lab,Admin')->group(function () {
        Route::get('/logs', [ActivityLogController::class, 'index'])->name('logs.index');
    });

    // Approval: Kaprodi & Kalab
    Route::middleware('role:Kepala_Prodi,Kepala_Lab')->group(function () {
        Route::get('/approvals', [ApprovalController::class, 'index'])->name('approvals.index');
        Route::post('/approvals/booking/{booking}/kaprodi', [ApprovalController::class, 'decideKaprodi'])->name('approvals.decideKaprodi');
        Route::post('/approvals/detail/{detail}/kalab', [ApprovalController::class, 'decideKalabDetail'])->name('approvals.decideKalabDetail');
    });
});
