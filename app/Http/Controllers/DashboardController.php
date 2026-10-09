<?php

namespace App\Http\Controllers;

use App\Constants\BookingDetailStatus;
use App\Constants\ScheduleType;
use App\Models\BookingDetail;
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
        $allPeriods = Period::where('is_active', true)->orderBy('start_date')->get();
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
                    ->where(['period_id' => $period->id, 'day_of_week' => $day, 'schedule_type' => ScheduleType::EXAM])
                    ->get();
            } else {
                $sections = Section::with(['course.studyProgram', 'lecturer'])
                    ->where(['period_id' => $period->id, 'day_of_week' => $day, 'schedule_type' => ScheduleType::REGULAR])
                    ->get();
            }
        }

        // Ambil hanya booking_detail berstatus 0 (Pending) atau 1 (Approved)
        $bookings = BookingDetail::with(['booking', 'room'])
            ->whereDate('start_datetime', $date)
            ->whereIn('status', [BookingDetailStatus::PENDING, BookingDetailStatus::APPROVED])
            ->get();

        $bookings->each(function (BookingDetail $bookingDetail) use ($bookingService) {
            $bookingDetail->queue_position = $bookingDetail->status === BookingDetailStatus::PENDING
                ? $bookingService->pendingQueuePosition($bookingDetail)
                : null;
        });

        // Tentukan apakah user merupakan role internal (Staf_Lab, Kepala_Prodi, Kepala_Lab, Admin)
        $user = $request->user();
        $isInternal = $user && $user->hasActiveRole('Staf_Lab', 'Kepala_Prodi', 'Kepala_Lab', 'Admin');

        // Format nama display untuk calendar (tanpa NIK, dan inisial untuk non-internal)
        $bookings->each(function (BookingDetail $bookingDetail) use ($isInternal) {
            $rawName = $bookingDetail->booking->requester_name;
            if ($isInternal) {
                $bookingDetail->display_requester = $rawName;
            } else {
                // Inisial: huruf pertama tiap kata, huruf kecil, dipisah titik
                $words = array_filter(explode(' ', trim($rawName)));
                $initials = array_map(fn ($w) => strtolower(mb_substr($w, 0, 1)), $words);
                $bookingDetail->display_requester = 'Peminjaman oleh ' . implode('.', $initials);
            }
        });

        $sections->each(function (Section $section) use ($isInternal) {
            if ($isInternal) {
                $section->display_lecturer = $section->lecturer?->name;
            } else {
                // Visitor & Dosen: hanya nama mata kuliah saja (dosen disembunyikan)
                $section->display_lecturer = null;
            }
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
            'isInternal',
        ));
    }
}
