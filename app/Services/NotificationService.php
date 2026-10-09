<?php

namespace App\Services;

use App\Models\Booking;
use App\Models\User;
use App\Notifications\BookingApprovedByKaprodiNotification;
use App\Notifications\BookingFinalResultNotification;
use App\Notifications\NewBookingSubmittedNotification;
use App\Notifications\RescheduleBookingSubmittedNotification;
use Illuminate\Support\Facades\Log;

class NotificationService
{
    /**
     * Kirim notifikasi pengajuan baru ke semua Kepala_Prodi yang aktif.
     */
    public function notifyNewBooking(Booking $booking): void
    {
        try {
            $today = now()->toDateString();
            $kaprodis = User::whereHas('roles', function ($q) use ($today) {
                $q->where('name', 'Kepala_Prodi')
                  ->where('start_date', '<=', $today)
                  ->where(function ($sub) use ($today) {
                      $sub->whereNull('end_date')->orWhere('end_date', '>=', $today);
                  });
            })->get();

            foreach ($kaprodis as $kaprodi) {
                $kaprodi->notify(new NewBookingSubmittedNotification($booking));
            }
        } catch (\Throwable $e) {
            Log::error('Gagal mengirim email pengajuan baru ke Kaprodi: ' . $e->getMessage(), ['booking_id' => $booking->id]);
        }
    }

    /**
     * Kirim notifikasi Kaprodi setuju ke semua Kepala_Lab yang aktif.
     */
    public function notifyKaprodiApproved(Booking $booking): void
    {
        try {
            $today = now()->toDateString();
            $kalabs = User::whereHas('roles', function ($q) use ($today) {
                $q->where('name', 'Kepala_Lab')
                  ->where('start_date', '<=', $today)
                  ->where(function ($sub) use ($today) {
                      $sub->whereNull('end_date')->orWhere('end_date', '>=', $today);
                  });
            })->get();

            foreach ($kalabs as $kalab) {
                $kalab->notify(new BookingApprovedByKaprodiNotification($booking));
            }
        } catch (\Throwable $e) {
            Log::error('Gagal mengirim email persetujuan Kaprodi ke Kalab: ' . $e->getMessage(), ['booking_id' => $booking->id]);
        }
    }

    /**
     * Kirim notifikasi permohonan ubah jadwal langsung ke Kepala_Lab yang aktif.
     */
    public function notifyRescheduleSubmitted(Booking $booking): void
    {
        try {
            $today = now()->toDateString();
            $kalabs = User::whereHas('roles', function ($q) use ($today) {
                $q->where('name', 'Kepala_Lab')
                  ->where('start_date', '<=', $today)
                  ->where(function ($sub) use ($today) {
                      $sub->whereNull('end_date')->orWhere('end_date', '>=', $today);
                  });
            })->get();

            foreach ($kalabs as $kalab) {
                $kalab->notify(new RescheduleBookingSubmittedNotification($booking));
            }
        } catch (\Throwable $e) {
            Log::error('Gagal mengirim email ubah jadwal ke Kalab: ' . $e->getMessage(), ['booking_id' => $booking->id]);
        }
    }

    /**
     * Kirim notifikasi hasil akhir (disetujui, ditolak, auto-reject) ke peminjam.
     */
    public function notifyFinalResult(Booking $booking): void
    {
        try {
            $requester = $booking->requester;
            if ($requester && $requester->email) {
                $requester->notify(new BookingFinalResultNotification($booking));
            }
        } catch (\Throwable $e) {
            Log::error('Gagal mengirim email hasil akhir ke peminjam: ' . $e->getMessage(), ['booking_id' => $booking->id]);
        }
    }
}
