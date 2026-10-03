<?php

namespace App\Console\Commands;

use App\Models\Booking;
use App\Services\ActivityLogger;
use Illuminate\Console\Command;

class AutoRejectPendingBookings extends Command
{
    protected $signature = 'bookings:auto-reject';

    protected $description = 'Reject pending bookings whose earliest room slot is within two days.';

    public function handle(ActivityLogger $logger): int
    {
        Booking::query()->where('status', 'pending')->withMin('roomBookings', 'start_datetime')->get()
            ->filter(fn (Booking $booking) => $booking->room_bookings_min_start_datetime && now()->startOfDay()->gte(now()->parse($booking->room_bookings_min_start_datetime)->subDays(2)->startOfDay()))
            ->each(function (Booking $booking) use ($logger): void {
                $booking->update(['status' => 'rejected', 'notes' => 'Ditolak otomatis sistem karena belum diproses sampai H-2']);
                $logger->log(null, 'auto_reject', 'booking', $booking->id, $booking->notes);
            });

        return self::SUCCESS;
    }
}
