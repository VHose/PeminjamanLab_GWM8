<?php

namespace App\Http\Controllers;

use App\Models\BookingRoom;
use App\Models\Period;
use App\Models\Room;
use App\Models\Section;
use App\Services\BookingService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(Request $request, BookingService $bookingService): View
    {
        $allPeriods = Period::where('active', true)->orderBy('start_date')->get();
        $selectedPeriodId = $request->input('period_id');
        $period = $allPeriods->firstWhere('id', $selectedPeriodId) ?: $allPeriods->first();

        $indonesianMonths = [
            1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April',
            5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus',
            9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember',
        ];

        $weeks = [];
        if ($period) {
            $startDate = Carbon::parse($period->start_date);
            $endDate = Carbon::parse($period->end_date);
            $w = 1;
            while (true) {
                $wStart = $startDate->copy()->addWeeks($w - 1);
                $wEnd = $wStart->copy()->addDays(5);
                if ($wStart->gt($endDate)) {
                    break;
                }
                $startDay = $wStart->day;
                $endDay = $wEnd->day;
                $startMonth = $indonesianMonths[$wStart->month];
                $endMonth = $indonesianMonths[$wEnd->month];
                $startYear = $wStart->year;
                $endYear = $wEnd->year;

                if ($wStart->month === $wEnd->month && $startYear === $endYear) {
                    $label = "Minggu {$w} {$startDay}-{$endDay} {$startMonth} {$startYear}";
                } elseif ($startYear === $endYear) {
                    $label = "Minggu {$w} {$startDay} {$startMonth} - {$endDay} {$endMonth} {$startYear}";
                } else {
                    $label = "Minggu {$w} {$startDay} {$startMonth} {$startYear} - {$endDay} {$endMonth} {$endYear}";
                }

                $inUtsWeek = $period->uts_start && $period->uts_end && ($wStart->betweenIncluded($period->uts_start, $period->uts_end) || $wEnd->betweenIncluded($period->uts_start, $period->uts_end));
                $inUasWeek = $period->uas_start && $period->uas_end && ($wStart->betweenIncluded($period->uas_start, $period->uas_end) || $wEnd->betweenIncluded($period->uas_start, $period->uas_end));

                if ($inUtsWeek) {
                    $label .= ' (UTS)';
                } elseif ($inUasWeek) {
                    $label .= ' (UAS)';
                }

                $weeks[$w] = $label;
                $w++;
            }
        }

        $maxWeek = max(1, count($weeks));
        $week = max(1, min($maxWeek, (int) $request->input('week', 1)));
        $day = max(1, min(6, (int) $request->input('day', 1)));
        $selectedRoomId = $request->input('room_id');

        $date = $period ? Carbon::parse($period->start_date)->addWeeks($week - 1)->addDays($day - 1) : now();

        $allRooms = Room::where('active', true)->orderBy('code')->get();
        $rooms = $allRooms->when($selectedRoomId, fn ($q, $id) => $q->where('id', $id));

        $isExamPeriodWithoutSchedule = $bookingService->isExamPeriodWithoutSchedule($date);

        $sections = collect();
        if ($period) {
            $inUts = $period->uts_start && $period->uts_end && $date->betweenIncluded($period->uts_start, $period->uts_end);
            $inUas = $period->uas_start && $period->uas_end && $date->betweenIncluded($period->uas_start, $period->uas_end);

            if ($inUts || $inUas) {
                $sections = Section::with(['course.studyProgram', 'lecturer'])
                    ->where(['period_id' => $period->id, 'day_of_week' => $day, 'class_type' => 'exam'])
                    ->get();
            } else {
                $sections = Section::with(['course.studyProgram', 'lecturer'])
                    ->where(['period_id' => $period->id, 'day_of_week' => $day])
                    ->where(fn ($q) => $q->whereNull('class_type')->orWhere('class_type', '!=', 'exam'))
                    ->get();
            }
        }

        $bookings = BookingRoom::with(['booking', 'room'])
            ->whereDate('start_datetime', $date)
            ->whereHas('booking', fn ($q) => $q->whereIn('status', ['pending', 'approved']))
            ->get();

        $bookings->each(function (BookingRoom $bookingRoom) use ($bookingService) {
            $bookingRoom->queue_position = $bookingRoom->booking->status === 'pending'
                ? $bookingService->pendingQueuePosition($bookingRoom)
                : null;
        });

        $timeSlots = [];
        for ($hour = 7; $hour <= 21; $hour++) {
            $timeSlots[] = sprintf('%02d:00', $hour);
            $timeSlots[] = sprintf('%02d:30', $hour);
        }

        return view('welcome', compact(
            'allPeriods',
            'period',
            'week',
            'weeks',
            'day',
            'date',
            'allRooms',
            'rooms',
            'selectedRoomId',
            'sections',
            'bookings',
            'isExamPeriodWithoutSchedule',
            'timeSlots',
        ));
    }
}
