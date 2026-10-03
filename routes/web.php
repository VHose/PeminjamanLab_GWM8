<?php

use App\Http\Controllers\ApprovalController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\BookingAvailabilityController;
use App\Http\Controllers\BookingController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\MasterController;
use App\Http\Controllers\SectionController;
use Illuminate\Support\Facades\Route;

Route::get('/', DashboardController::class)->name('home');
Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'create'])->name('login');
    Route::post('/login', [AuthController::class, 'store']);
    Route::get('/register', [AuthController::class, 'register'])->name('register');
    Route::post('/register', [AuthController::class, 'registerStore']);
});
Route::post('/logout', [AuthController::class, 'destroy'])->middleware('auth')->name('logout');
Route::get('/availability', BookingAvailabilityController::class)->middleware('auth')->name('availability');
Route::middleware('auth')->group(function () {
    Route::get('/bookings', [BookingController::class, 'index'])->name('bookings.index');
    Route::get('/bookings/create', [BookingController::class, 'create'])->name('bookings.create');
    Route::post('/bookings', [BookingController::class, 'store'])->name('bookings.store');
    Route::get('/bookings/{booking}/change', [BookingController::class, 'change'])->name('bookings.change');
    Route::post('/bookings/{booking}/cancel', [BookingController::class, 'cancel'])->name('bookings.cancel');
    Route::middleware('role:Staf_Lab,Kepala_Prodi,Kepala_Lab')->group(function () {
        Route::get('master/{type}', [MasterController::class, 'index'])->name('master.index');
        Route::get('master/{type}/create', [MasterController::class, 'create'])->name('master.create');
        Route::post('master/{type}', [MasterController::class, 'store'])->name('master.store');
        Route::get('master/{type}/{id}/edit', [MasterController::class, 'edit'])->name('master.edit');
        Route::put('master/{type}/{id}', [MasterController::class, 'update'])->name('master.update');
        Route::delete('master/{type}/{id}', [MasterController::class, 'destroy'])->name('master.destroy');
        Route::resource('sections', SectionController::class)->except('show');
        Route::get('/staff-bookings/create', [BookingController::class, 'staffCreate'])->name('staff-bookings.create');
        Route::post('/staff-bookings', [BookingController::class, 'staffStore'])->name('staff-bookings.store');
    });
    Route::middleware('role:Kepala_Prodi,Kepala_Lab')->group(function () {
        Route::get('/approvals', [ApprovalController::class, 'index'])->name('approvals.index');
        Route::post('/approvals/{booking}', [ApprovalController::class, 'decide'])->name('approvals.decide');
    });
});
