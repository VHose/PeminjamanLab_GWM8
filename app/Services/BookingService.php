<?php

namespace App\Services;

use App\Enums\BookingDetailStatus;
use App\Models\BookingDetail;
use App\Models\Period;
use App\Models\Schedule;
use App\Models\ScheduleSession;
use App\Models\Setting;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class BookingService
{
    /**
     * Membulatkan jam selesai ke atas ke blok menit terdekat (misal :00 atau :30).
     * Contoh: 08:40 -> 09:00, 14:10 -> 14:30 pada blok 30 menit.
     */
    public static function roundUpToBlock(string $timeStr, int $blockMinutes = 30): string
    {
        [$hour, $minute] = explode(':', substr($timeStr, 0, 5));
        $totalMinutes = (int) $hour * 60 + (int) $minute;
        $remainder = $totalMinutes % $blockMinutes;

        if ($remainder > 0) {
            $totalMinutes += ($blockMinutes - $remainder);
        }

        $newHour = (int) floor($totalMinutes / 60);
        $newMinute = $totalMinutes % 60;

        return sprintf('%02d:%02d', min(23, $newHour), $newMinute);
    }

    public function timeSlots(): array
    {
        $block = (int) Setting::get('booking_block_minutes', 30);
        $labOpen = Setting::get('lab_open_time', '07:00');
        $labClose = Setting::get('lab_close_time', '21:00');

        $slots = [];
        $start = Carbon::createFromFormat('H:i', substr($labOpen, 0, 5));
        $end = Carbon::createFromFormat('H:i', substr($labClose, 0, 5));

        for ($curr = $start->copy(); $curr->lt($end); $curr->addMinutes($block)) {
            $slots[] = $curr->format('H:i');
        }

        return $slots;
    }

    public function startSlots(): array
    {
        return $this->timeSlots();
    }

    public function endSlots(): array
    {
        $block = (int) Setting::get('booking_block_minutes', 30);
        $labOpen = Setting::get('lab_open_time', '07:00');
        $labClose = Setting::get('lab_close_time', '21:00');

        $slots = [];
        $start = Carbon::createFromFormat('H:i', substr($labOpen, 0, 5))->addMinutes($block);
        $end = Carbon::createFromFormat('H:i', substr($labClose, 0, 5));

        for ($curr = $start->copy(); $curr->lte($end); $curr->addMinutes($block)) {
            $slots[] = $curr->format('H:i');
        }

        return $slots;
    }

    public function isAvailable(string|int $roomId, Carbon $start, Carbon $end, ?int $excludeBookingId = null): bool
    {
        $dateStr = $start->toDateString();
        $startTimeStr = $start->format('H:i:s');
        $endTimeStr = $end->format('H:i:s');

        // Hari Minggu ditolak
        if ($start->isSunday()) {
            return false;
        }

        // Cek konflik dengan booking_detail yang disetujui (APPROVED)
        $bookingConflictQuery = BookingDetail::query()
            ->where('room_id', (string) $roomId)
            ->where('booking_date', $dateStr)
            ->where('status', BookingDetailStatus::APPROVED)
            ->where('start_time', '<', $endTimeStr)
            ->where('end_time', '>', $startTimeStr);

        if ($excludeBookingId !== null) {
            $bookingConflictQuery->where('booking_id', '!=', $excludeBookingId);
        }

        if ($bookingConflictQuery->exists()) {
            return false;
        }

        // Cek bentrok dengan jadwal kuliah aktif (schedule_session & schedule)
        // Kuliah hanya hari 1..5 (Senin..Jumat)
        $dayOfWeek = $start->dayOfWeekIso;
        if ($dayOfWeek >= 1 && $dayOfWeek <= 5) {
            $blockMinutes = (int) Setting::get('booking_block_minutes', 30);

            // Cek dari schedule_session yang aktif di tanggal ini
            $activeSessions = ScheduleSession::with('schedule')
                ->where('session_date', $dateStr)
                ->where('is_active', true)
                ->where(function ($q) use ($roomId) {
                    $q->where('room_id', (string) $roomId)
                      ->orWhere(function ($sub) use ($roomId) {
                          $sub->whereNull('room_id')
                              ->whereHas('schedule', fn ($sc) => $sc->where('room_id', (string) $roomId));
                      });
                })
                ->get();

            foreach ($activeSessions as $session) {
                $sessionStart = $session->effectiveStartTime();
                $sessionRawEnd = $session->effectiveEndTime();
                // Blokir diperpanjang dengan pembulatan ke atas ke kelipatan blok menit (:00 / :30)
                $sessionBlockedEnd = self::roundUpToBlock($sessionRawEnd, $blockMinutes) . ':00';

                // Irisan waktu
                if ($sessionStart < $endTimeStr && $sessionBlockedEnd > $startTimeStr) {
                    return false;
                }
            }
        }

        return true;
    }

    public function unavailableRanges(string|int $roomId, Carbon $date): Collection
    {
        $dateStr = $date->toDateString();
        $ranges = collect();

        if ($date->isSunday()) {
            return $ranges->push(['start' => '00:00', 'end' => '23:59', 'type' => 'holiday']);
        }

        $blockMinutes = (int) Setting::get('booking_block_minutes', 30);

        // 1. Dari jadwal kuliah
        if ($date->dayOfWeekIso <= 5) {
            $activeSessions = ScheduleSession::with('schedule')
                ->where('session_date', $dateStr)
                ->where('is_active', true)
                ->where(function ($q) use ($roomId) {
                    $q->where('room_id', (string) $roomId)
                      ->orWhere(function ($sub) use ($roomId) {
                          $sub->whereNull('room_id')
                              ->whereHas('schedule', fn ($sc) => $sc->where('room_id', (string) $roomId));
                      });
                })
                ->get();

            foreach ($activeSessions as $session) {
                $sessionStart = substr($session->effectiveStartTime(), 0, 5);
                $sessionBlockedEnd = self::roundUpToBlock($session->effectiveEndTime(), $blockMinutes);

                $ranges->push([
                    'start' => $sessionStart,
                    'end' => $sessionBlockedEnd,
                    'type' => 'section',
                ]);
            }
        }

        // 2. Dari booking yang disetujui
        $approvedBookings = BookingDetail::where('room_id', (string) $roomId)
            ->where('booking_date', $dateStr)
            ->where('status', BookingDetailStatus::APPROVED)
            ->get();

        foreach ($approvedBookings as $b) {
            $ranges->push([
                'start' => substr($b->start_time, 0, 5),
                'end' => substr($b->end_time, 0, 5),
                'type' => 'approved_booking',
            ]);
        }

        return $ranges;
    }

    public function pendingRanges(string|int $roomId, Carbon $date): Collection
    {
        $pendingBookings = BookingDetail::where('room_id', (string) $roomId)
            ->where('booking_date', $date->toDateString())
            ->where('status', BookingDetailStatus::PENDING)
            ->get();

        return $pendingBookings->map(fn ($b) => [
            'start' => substr($b->start_time, 0, 5),
            'end' => substr($b->end_time, 0, 5),
            'type' => 'pending_booking',
        ]);
    }

    public function pendingQueuePosition(BookingDetail $bookingDetail): int
    {
        $earlierCount = BookingDetail::query()
            ->where('room_id', $bookingDetail->room_id)
            ->where('booking_date', $bookingDetail->booking_date->toDateString())
            ->where('status', BookingDetailStatus::PENDING)
            ->where('start_time', '<', $bookingDetail->end_time)
            ->where('end_time', '>', $bookingDetail->start_time)
            ->where(function ($q) use ($bookingDetail) {
                $q->where('booking_id', '<', $bookingDetail->booking_id);
            })
            ->count();

        return $earlierCount + 1;
    }
}
