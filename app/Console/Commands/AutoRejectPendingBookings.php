<?php

namespace App\Console\Commands;

use App\Constants\BookingDetailStatus;
use App\Constants\BookingStatus;
use App\Models\Booking;
use App\Services\ActivityLogger;
use App\Services\NotificationService;
use Illuminate\Console\Command;

class AutoRejectPendingBookings extends Command
{
    protected $signature = 'bookings:auto-reject';

    protected $description = 'Reject pending bookings whose earliest room slot is within two days (H-2).';

    public function handle(ActivityLogger $logger, NotificationService $notifications): int
    {
        $reason = 'Ditolak otomatis sistem karena belum diproses sampai batas waktu H-2.';

        Booking::query()
            ->whereIn('status', [BookingStatus::PENDING_KAPRODI, BookingStatus::PENDING_KALAB])
            ->withMin('details', 'start_datetime')
            ->get()
            ->filter(function (Booking $booking) {
                if (! $booking->details_min_start_datetime) {
                    return false;
                }
                $earliestSlot = now()->parse($booking->details_min_start_datetime);
                // Batas H-2: jika now() >= earliestSlot - 2 hari
                return now()->gte($earliestSlot->copy()->subDays(2));
            })
            ->each(function (Booking $booking) use ($logger, $notifications, $reason): void {
                $booking->update([
                    'status' => BookingStatus::REJECTED,
                    'notes' => $reason,
                ]);

                $booking->details()
                    ->where('status', BookingDetailStatus::PENDING)
                    ->update([
                        'status' => BookingDetailStatus::REJECTED,
                        'notes' => $reason,
                    ]);

                $logger->log(null, 'auto_reject', 'booking', (string) $booking->id, $reason);

                $notifications->notifyFinalResult($booking);

                $this->info("Booking #{$booking->id} berhasil di-reject otomatis.");
            });

        return self::SUCCESS;
    }
}
