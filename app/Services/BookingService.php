<?php

namespace App\Services;

use App\Models\BookingRoom;
use App\Models\Period;
use App\Models\Section;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class BookingService
{
    public function timeSlots(): array
    {
        return collect(range(0, 30))->map(fn ($i) => Carbon::createFromFormat('H:i', '07:00')->addMinutes($i * 30)->format('H:i'))->all();
    }

    public function isAvailable(int $roomId, Carbon $start, Carbon $end): bool
    {
        if (BookingRoom::where('room_id', $roomId)->whereHas('booking', fn ($q) => $q->whereIn('status', ['pending', 'approved']))->where('start_datetime', '<', $end)->where('end_datetime', '>', $start)->exists()) {
            return false;
        } $period = Period::whereDate('start_date', '<=', $start)->whereDate('end_date', '>=', $start)->first();

        return ! $period || ! Section::where(['period_id' => $period->id, 'room_id' => $roomId, 'day_of_week' => $start->dayOfWeekIso])->where('start_time', '<', $end->format('H:i:s'))->where('end_time', '>', $start->format('H:i:s'))->exists();
    }

    public function unavailableRanges(int $roomId, Carbon $date): Collection
    {
        $period = Period::whereDate('start_date', '<=', $date)->whereDate('end_date', '>=', $date)->first();
        $sections = $period ? Section::where(['period_id' => $period->id, 'room_id' => $roomId, 'day_of_week' => $date->dayOfWeekIso])->get(['start_time', 'end_time']) : collect();
        $bookings = BookingRoom::where('room_id', $roomId)->whereDate('start_datetime', $date)->whereHas('booking', fn ($q) => $q->whereIn('status', ['pending', 'approved']))->get();

        return $sections->map(fn ($s) => ['start' => substr($s->start_time, 0, 5), 'end' => substr($s->end_time, 0, 5)])->merge($bookings->map(fn ($b) => ['start' => $b->start_datetime->format('H:i'), 'end' => $b->end_datetime->format('H:i')]));
    }
}
