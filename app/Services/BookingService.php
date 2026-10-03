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

    public function startSlots(): array
    {
        return collect(range(0, 29))->map(fn ($i) => Carbon::createFromFormat('H:i', '07:00')->addMinutes($i * 30)->format('H:i'))->all();
    }

    public function endSlots(): array
    {
        return collect(range(1, 30))->map(fn ($i) => Carbon::createFromFormat('H:i', '07:00')->addMinutes($i * 30)->format('H:i'))->all();
    }

    public function isAvailable(int $roomId, Carbon $start, Carbon $end): bool
    {
        if ($this->isExamPeriodWithoutSchedule($start)) {
            return false;
        }

        if (BookingRoom::where('room_id', $roomId)
            ->whereHas('booking', fn ($q) => $q->where('status', 'approved'))
            ->where('start_datetime', '<', $end)
            ->where('end_datetime', '>', $start)
            ->exists()) {
            return false;
        }

        $period = Period::whereDate('start_date', '<=', $start)->whereDate('end_date', '>=', $start)->first();
        if ($period && Section::where([
            'period_id' => $period->id,
            'room_id' => $roomId,
            'day_of_week' => $start->dayOfWeekIso,
        ])->where(fn ($q) => $q->whereNull('class_type')->orWhere('class_type', '!=', 'exam'))
          ->where('start_time', '<', $end->format('H:i:s'))
          ->where('end_time', '>', $start->format('H:i:s'))->exists()) {
            return false;
        }

        return true;
    }

    public function isExamPeriodWithoutSchedule(Carbon $date): bool
    {
        $period = Period::whereDate('start_date', '<=', $date)->whereDate('end_date', '>=', $date)->first();
        if (! $period) {
            return false;
        }

        $inUts = $period->uts_start && $period->uts_end && $date->betweenIncluded($period->uts_start, $period->uts_end);
        $inUas = $period->uas_start && $period->uas_end && $date->betweenIncluded($period->uas_start, $period->uas_end);

        if ($inUts || $inUas) {
            $hasExamSections = Section::where('period_id', $period->id)
                ->where('class_type', 'exam')
                ->where('day_of_week', $date->dayOfWeekIso)
                ->exists();

            return ! $hasExamSections;
        }

        return false;
    }

    public function unavailableRanges(int $roomId, Carbon $date): Collection
    {
        $period = Period::whereDate('start_date', '<=', $date)->whereDate('end_date', '>=', $date)->first();
        $isExam = false;
        if ($period) {
            $inUts = $period->uts_start && $period->uts_end && $date->betweenIncluded($period->uts_start, $period->uts_end);
            $inUas = $period->uas_start && $period->uas_end && $date->betweenIncluded($period->uas_start, $period->uas_end);
            $isExam = $inUts || $inUas;
        }

        $sections = collect();
        if ($period && ! $isExam) {
            $sections = Section::where([
                'period_id' => $period->id,
                'room_id' => $roomId,
                'day_of_week' => $date->dayOfWeekIso,
            ])->where(fn ($q) => $q->whereNull('class_type')->orWhere('class_type', '!=', 'exam'))
              ->get(['start_time', 'end_time']);
        } elseif ($period && $isExam) {
            $sections = Section::where([
                'period_id' => $period->id,
                'room_id' => $roomId,
                'day_of_week' => $date->dayOfWeekIso,
                'class_type' => 'exam',
            ])->get(['start_time', 'end_time']);
        }

        $approvedBookings = BookingRoom::where('room_id', $roomId)
            ->whereDate('start_datetime', $date)
            ->whereHas('booking', fn ($q) => $q->where('status', 'approved'))
            ->get();

        return $sections->map(fn ($s) => [
            'start' => substr($s->start_time, 0, 5),
            'end' => substr($s->end_time, 0, 5),
            'type' => 'section',
        ])->merge($approvedBookings->map(fn ($b) => [
            'start' => $b->start_datetime->format('H:i'),
            'end' => $b->end_datetime->format('H:i'),
            'type' => 'approved_booking',
        ]));
    }

    public function pendingRanges(int $roomId, Carbon $date): Collection
    {
        $pendingBookings = BookingRoom::where('room_id', $roomId)
            ->whereDate('start_datetime', $date)
            ->whereHas('booking', fn ($q) => $q->where('status', 'pending'))
            ->get();

        return $pendingBookings->map(fn ($b) => [
            'start' => $b->start_datetime->format('H:i'),
            'end' => $b->end_datetime->format('H:i'),
            'type' => 'pending_booking',
        ]);
    }

    public function pendingQueuePosition(BookingRoom $bookingRoom): int
    {
        return BookingRoom::query()
            ->where('room_id', $bookingRoom->room_id)
            ->where('start_datetime', '<', $bookingRoom->end_datetime)
            ->where('end_datetime', '>', $bookingRoom->start_datetime)
            ->whereHas('booking', function ($query) use ($bookingRoom) {
                $query->where('status', 'pending')
                    ->where(function ($sub) use ($bookingRoom) {
                        $sub->where('submitted_at', '<', $bookingRoom->booking->submitted_at)
                            ->orWhere(function ($sub2) use ($bookingRoom) {
                                $sub2->where('submitted_at', '=', $bookingRoom->booking->submitted_at)
                                    ->where('id', '<=', $bookingRoom->booking->id);
                            });
                    });
            })
            ->count();
    }
}
