<?php

use App\Http\Controllers\ActivityLogController;
use App\Http\Controllers\AdminUserController;
use App\Http\Controllers\ApprovalController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\BookingAvailabilityController;
use App\Http\Controllers\BookingController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\MasterController;
use App\Http\Controllers\PasswordChangeController;
use App\Http\Controllers\PeriodController;
use App\Http\Controllers\PeriodHolidayController;
use App\Http\Controllers\PeriodRoleController;
use App\Http\Controllers\ScheduleSessionController;
use App\Http\Controllers\SectionController;
use Illuminate\Support\Facades\Route;

// Halaman Beranda & Jadwal
Route::get('/', DashboardController::class)->name('home');
Route::get('/gwm-lantai8', DashboardController::class)->name('home.alias');
Route::get('/availability', BookingAvailabilityController::class)->name('availability');

// Autentikasi Pengguna
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

// Halaman yang membutuhkan login
Route::middleware('auth')->group(function () {
    // Alur Ganti Password (Wajib ganti password awal / Sukarela)
    Route::get('/change-password', [PasswordChangeController::class, 'edit'])->name('password.change');
    Route::post('/change-password', [PasswordChangeController::class, 'update'])->name('password.change.update');

    // Peminjaman Ruangan
    Route::get('/bookings', [BookingController::class, 'index'])->name('bookings.index');
    Route::get('/bookings/create', [BookingController::class, 'create'])->name('bookings.create');
    Route::get('/gwm-lantai8/peminjaman', [BookingController::class, 'create'])->name('bookings.create.alias');
    Route::post('/bookings', [BookingController::class, 'store'])->name('bookings.store');
    Route::get('/bookings/{booking}', [BookingController::class, 'show'])->name('bookings.show');
    Route::get('/bookings/{booking}/attachments/{attachment}', [BookingController::class, 'showAttachment'])->name('bookings.attachments.show');
    Route::get('/bookings/{booking}/change', [BookingController::class, 'change'])->name('bookings.change');
    Route::post('/bookings/{booking}/cancel', [BookingController::class, 'cancel'])->name('bookings.cancel');

    // Staf_Lab: Booking atas nama dosen
    Route::middleware('role:Staf_Lab')->group(function () {
        Route::get('/staff-bookings/create', [BookingController::class, 'staffCreate'])->name('staff-bookings.create');
        Route::post('/staff-bookings', [BookingController::class, 'staffStore'])->name('staff-bookings.store');
    });

    // Staf_Lab, Kepala_Prodi, Kepala_Lab: CRUD Section
    Route::middleware('role:Staf_Lab,Kepala_Prodi,Kepala_Lab')->group(function () {
        Route::resource('sections', SectionController::class)->except('show');
    });

    // Log Aktivitas
    Route::middleware('role:Staf_Lab,Kepala_Prodi,Kepala_Lab,Admin')->group(function () {
        Route::get('/logs', [ActivityLogController::class, 'index'])->name('logs.index');
    });

    // Persetujuan: Kaprodi & Kalab
    Route::middleware('role:Kepala_Prodi,Kepala_Lab')->group(function () {
        Route::get('/approvals', [ApprovalController::class, 'index'])->name('approvals.index');
        Route::post('/approvals/booking/{booking}/kaprodi', [ApprovalController::class, 'decideKaprodi'])->name('approvals.decideKaprodi');
        Route::post('/approvals/detail/{detail}/kalab', [ApprovalController::class, 'decideKalabDetail'])->name('approvals.decideKalabDetail');
    });

    // Kepala Lab & Admin: Kelola Peran Staf per Periode Semester
    Route::middleware('role:Kepala_Lab,Admin')->prefix('admin')->group(function () {
        Route::get('/period-roles', [PeriodRoleController::class, 'index'])->name('admin.period-roles.index');
        Route::post('/period-roles', [PeriodRoleController::class, 'store'])->name('admin.period-roles.store');
        Route::post('/period-roles/copy', [PeriodRoleController::class, 'copy'])->name('admin.period-roles.copy');
        Route::post('/period-roles/upload', [PeriodRoleController::class, 'upload'])->name('admin.period-roles.upload');
        Route::delete('/period-roles/{periodId}/{userId}/{roleId}', [PeriodRoleController::class, 'destroy'])->name('admin.period-roles.destroy');
    });

    // Admin Only: Fitur Pengaturan Lengkap & Master Data
    Route::middleware('role:Admin')->prefix('admin')->group(function () {
        // Kelola Pengguna
        Route::get('/users', [AdminUserController::class, 'index'])->name('admin.users.index');
        Route::post('/users', [AdminUserController::class, 'store'])->name('admin.users.store');
        Route::post('/users/{user}/reset-password', [AdminUserController::class, 'resetPassword'])->name('admin.users.resetPassword');
        Route::post('/users/{user}/toggle', [AdminUserController::class, 'toggleActive'])->name('admin.users.toggle');

        // Periode Semester
        Route::get('/periods', [PeriodController::class, 'index'])->name('admin.periods.index');
        Route::get('/settings', fn () => redirect()->route('admin.periods.index'))->name('admin.settings.index');
        Route::get('/periods/create', [PeriodController::class, 'create'])->name('admin.periods.create');
        Route::post('/periods', [PeriodController::class, 'store'])->name('admin.periods.store');
        Route::get('/periods/{period}/edit', [PeriodController::class, 'edit'])->name('admin.periods.edit');
        Route::put('/periods/{period}', [PeriodController::class, 'update'])->name('admin.periods.update');
        Route::post('/periods/{period}/toggle', [PeriodController::class, 'toggleActive'])->name('admin.periods.toggle');
        Route::delete('/periods/{period}', [PeriodController::class, 'destroy'])->name('admin.periods.destroy');
        Route::post('/periods/{period}/regenerate', [PeriodController::class, 'regenerateSessions'])->name('admin.periods.regenerate');

        // Toggle Aktif/Libur Sesi Pertemuan Kuliah Langsung dari Tabel Jadwal
        Route::post('/schedule-sessions/{scheduleId}/{weekNo}/toggle', [ScheduleSessionController::class, 'toggle'])->name('admin.schedule-sessions.toggle');
        Route::patch('/schedule-sessions/{scheduleId}/{weekNo}', [ScheduleSessionController::class, 'update'])->name('admin.schedule-sessions.update');

        // Hari Libur Periode
        Route::get('/periods/{period}/holidays', [PeriodHolidayController::class, 'index'])->name('admin.holidays.index');
        Route::post('/periods/{period}/holidays', [PeriodHolidayController::class, 'store'])->name('admin.holidays.store');
        Route::delete('/periods/{period}/holidays/{startDate}', [PeriodHolidayController::class, 'destroy'])->name('admin.holidays.destroy');

        // Master Data CRUD
        Route::get('master/{type}', [MasterController::class, 'index'])->name('master.index');
        Route::get('master/{type}/create', [MasterController::class, 'create'])->name('master.create');
        Route::post('master/{type}', [MasterController::class, 'store'])->name('master.store');
        Route::get('master/{type}/{id}/edit', [MasterController::class, 'edit'])->name('master.edit');
        Route::put('master/{type}/{id}', [MasterController::class, 'update'])->name('master.update');
        Route::delete('master/{type}/{id}', [MasterController::class, 'destroy'])->name('master.destroy');
        Route::post('master/{type}/{id}/toggle', [MasterController::class, 'toggle'])->name('master.toggle');
    });
});
