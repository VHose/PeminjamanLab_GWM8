<?php

namespace App\Services;

use App\Models\Booking;
use App\Models\Period;
use App\Models\User;
use App\Notifications\BookingApprovedByKaprodiNotification;
use App\Notifications\BookingFinalResultNotification;
use App\Notifications\BookingSubmittedRequesterNotification;
use App\Notifications\NewBookingSubmittedNotification;
use App\Notifications\RescheduleBookingSubmittedNotification;
use Illuminate\Support\Facades\Log;

class NotificationService
{
    private function runAsync(callable $callback): void
    {
        if (app()->runningUnitTests()) {
            $callback();
            return;
        }

        try {
            $callback();
        } finally {
            if (config('queue.default') === 'database') {
                if (PHP_OS_FAMILY === 'Windows') {
                    $php = PHP_BINARY;
                    $artisan = base_path('artisan');
                    pclose(popen("start \"\" /B \"{$php}\" \"{$artisan}\" queue:work database --stop-when-empty > NUL 2>&1", 'r'));
                } else {
                    exec('php artisan queue:work database --stop-when-empty > /dev/null 2>&1 &');
                }
            }
        }
    }

    public function notifyNewBooking(Booking $booking): void
    {
        $this->runAsync(function () use ($booking) {
            try {
                $activePeriodId = Period::where('is_active', true)->value('id');
                $kaprodis = User::whereHas('roles', function ($q) use ($activePeriodId) {
                    $q->where('name', 'Kepala_Prodi');
                    if ($activePeriodId) {
                        $q->where('user_role.period_id', $activePeriodId);
                    }
                })->get();

                foreach ($kaprodis as $kaprodi) {
                    $kaprodi->notify(new NewBookingSubmittedNotification($booking));
                }

                $requester = $booking->requester;
                if ($requester && $requester->email) {
                    $requester->notify(new BookingSubmittedRequesterNotification($booking));
                }
            } catch (\Throwable $e) {
                Log::error('Gagal mengirim email pengajuan baru: ' . $e->getMessage(), ['booking_id' => $booking->id]);
            }
        });
    }

    public function notifyKaprodiApproved(Booking $booking): void
    {
        $this->runAsync(function () use ($booking) {
            try {
                $activePeriodId = Period::where('is_active', true)->value('id');
                $kalabs = User::whereHas('roles', function ($q) use ($activePeriodId) {
                    $q->where('name', 'Kepala_Lab');
                    if ($activePeriodId) {
                        $q->where('user_role.period_id', $activePeriodId);
                    }
                })->get();

                foreach ($kalabs as $kalab) {
                    $kalab->notify(new BookingApprovedByKaprodiNotification($booking));
                }
            } catch (\Throwable $e) {
                Log::error('Gagal mengirim email persetujuan Kaprodi ke Kalab: ' . $e->getMessage(), ['booking_id' => $booking->id]);
            }
        });
    }

    public function notifyRescheduleSubmitted(Booking $booking): void
    {
        $this->runAsync(function () use ($booking) {
            try {
                $activePeriodId = Period::where('is_active', true)->value('id');
                $kalabs = User::whereHas('roles', function ($q) use ($activePeriodId) {
                    $q->where('name', 'Kepala_Lab');
                    if ($activePeriodId) {
                        $q->where('user_role.period_id', $activePeriodId);
                    }
                })->get();

                foreach ($kalabs as $kalab) {
                    $kalab->notify(new RescheduleBookingSubmittedNotification($booking));
                }

                $requester = $booking->requester;
                if ($requester && $requester->email) {
                    $requester->notify(new BookingSubmittedRequesterNotification($booking));
                }
            } catch (\Throwable $e) {
                Log::error('Gagal mengirim email ubah jadwal: ' . $e->getMessage(), ['booking_id' => $booking->id]);
            }
        });
    }

    public function notifyFinalResult(Booking $booking): void
    {
        $this->runAsync(function () use ($booking) {
            try {
                $requester = $booking->requester;
                if ($requester && $requester->email) {
                    $requester->notify(new BookingFinalResultNotification($booking));
                }
            } catch (\Throwable $e) {
                Log::error('Gagal mengirim email hasil akhir ke peminjam: ' . $e->getMessage(), ['booking_id' => $booking->id]);
            }
        });
    }
}
