<?php

namespace App\Services;

use App\Constants\BookingDetailStatus;
use App\Constants\ScheduleType;
use App\Models\BookingDetail;
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

    public function isAvailable(int $roomId, Carbon $start, Carbon $end, ?int $excludeBookingId = null): bool
    {
        if ($this->isExamPeriodWithoutSchedule($start, $roomId)) {
            return false;
        }

        // Check against approved booking details (status = APPROVED / 1)
        $bookingConflictQuery = BookingDetail::query()
            ->where('room_id', $roomId)
            ->where('status', BookingDetailStatus::APPROVED)
            ->where('start_datetime', '<', $end)
            ->where('end_datetime', '>', $start);

        if ($excludeBookingId !== null) {
            $bookingConflictQuery->where('booking_id', '!=', $excludeBookingId);
        }

        if ($bookingConflictQuery->exists()) {
            return false;
        }

        // Check against active regular sections
        $period = Period::whereDate('start_date', '<=', $start)->whereDate('end_date', '>=', $start)->first();
        if ($period) {
            $isExamDate = $this->isExamDate($period, $start);

            $sectionQuery = Section::where([
                'period_id' => $period->id,
                'room_id' => $roomId,
                'day_of_week' => $start->dayOfWeekIso,
            ])
            ->where('start_time', '<', $end->format('H:i:s'))
            ->where('end_time', '>', $start->format('H:i:s'));

            if ($isExamDate) {
                // If it is an exam date, sections that conflict are exam sections
                $sectionQuery->where('schedule_type', ScheduleType::EXAM);
            } else {
                $sectionQuery->where('schedule_type', ScheduleType::REGULAR);
            }

            if ($sectionQuery->exists()) {
                return false;
            }
        }

        return true;
    }

    public function isExamDate(?Period $period, Carbon $date): bool
    {
        if (! $period) {
            return false;
        }

        $inUts = $period->uts_start && $period->uts_end && $date->betweenIncluded($period->uts_start, $period->uts_end);
        $inUas = $period->uas_start && $period->uas_end && $date->betweenIncluded($period->uas_start, $period->uas_end);

        return (bool) ($inUts || $inUas);
    }

    public function isExamPeriodWithoutSchedule(Carbon $date, ?int $roomId = null): bool
    {
        $period = Period::whereDate('start_date', '<=', $date)->whereDate('end_date', '>=', $date)->first();
        if (! $period) {
            return false;
        }

        if ($this->isExamDate($period, $date)) {
            $query = Section::where('period_id', $period->id)
                ->where('schedule_type', ScheduleType::EXAM)
                ->where('day_of_week', $date->dayOfWeekIso);

            if ($roomId !== null) {
                $query->where('room_id', $roomId);
            }

            return ! $query->exists();
        }

        return false;
    }

    public function unavailableRanges(int $roomId, Carbon $date): Collection
    {
        $period = Period::whereDate('start_date', '<=', $date)->whereDate('end_date', '>=', $date)->first();
        $isExam = $period && $this->isExamDate($period, $date);

        $sections = collect();
        if ($period && ! $isExam) {
            $sections = Section::where([
                'period_id' => $period->id,
                'room_id' => $roomId,
                'day_of_week' => $date->dayOfWeekIso,
                'schedule_type' => ScheduleType::REGULAR,
            ])->get(['start_time', 'end_time']);
        } elseif ($period && $isExam) {
            $sections = Section::where([
                'period_id' => $period->id,
                'room_id' => $roomId,
                'day_of_week' => $date->dayOfWeekIso,
                'schedule_type' => ScheduleType::EXAM,
            ])->get(['start_time', 'end_time']);
        }

        $approvedBookings = BookingDetail::where('room_id', $roomId)
            ->whereDate('start_datetime', $date)
            ->where('status', BookingDetailStatus::APPROVED)
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
        $pendingBookings = BookingDetail::where('room_id', $roomId)
            ->whereDate('start_datetime', $date)
            ->where('status', BookingDetailStatus::PENDING)
            ->get();

        return $pendingBookings->map(fn ($b) => [
            'start' => $b->start_datetime->format('H:i'),
            'end' => $b->end_datetime->format('H:i'),
            'type' => 'pending_booking',
        ]);
    }

    public function pendingQueuePosition(BookingDetail $bookingDetail): int
    {
        $booking = $bookingDetail->booking;
        if (! $booking) {
            return 1;
        }

        // Jumlah detail berstatus 0 di ruangan yang sama dengan waktu tumpang tindih dan submitted_at lebih awal, + 1
        $earlierCount = BookingDetail::query()
            ->where('room_id', $bookingDetail->room_id)
            ->where('status', BookingDetailStatus::PENDING)
            ->where('id', '!=', $bookingDetail->id)
            ->where('start_datetime', '<', $bookingDetail->end_datetime)
            ->where('end_datetime', '>', $bookingDetail->start_datetime)
            ->whereHas('booking', function ($query) use ($booking) {
                $query->where(function ($sub) use ($booking) {
                    $sub->where('submitted_at', '<', $booking->submitted_at)
                        ->orWhere(function ($sub2) use ($booking) {
                            $sub2->where('submitted_at', '=', $booking->submitted_at)
                                ->where('id', '<', $booking->id);
                        });
                });
            })
            ->count();

        return $earlierCount + 1;
    }
}
