<?php

namespace App\Http\Controllers;

use App\Enums\BookingDetailStatus;
use App\Enums\WeekType;
use App\Models\BookingDetail;
use App\Models\Period;
use App\Models\Room;
use App\Models\ScheduleSession;
use App\Models\Setting;
use App\Models\StudyProgram;
use App\Services\BookingService;
use App\Services\PeriodWeekCalendar;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(
        Request $request,
        PeriodWeekCalendar $calendarService,
        BookingService $bookingService
    ): View {
        $allPeriods = Period::orderByDesc('start_date')->get();
        $selectedPeriodId = $request->query('period_id');

        $period = $selectedPeriodId
            ? $allPeriods->firstWhere('id', $selectedPeriodId)
            : ($allPeriods->firstWhere('is_active', true) ?: $allPeriods->first());

        $calendar = [];
        $selectedWeek = null;

        if ($period) {
            $holidays = $period->holidays()->get(['start_date', 'end_date', 'note'])->toArray();
            $calendar = $calendarService->buildCalendar(
                $period->start_date->toDateString(),
                $period->end_date->toDateString(),
                $period->uts_start?->toDateString(),
                $period->uts_end?->toDateString(),
                $period->uas_start?->toDateString(),
                $period->uas_end?->toDateString(),
                $holidays
            );

            $requestedWeekIndex = $request->query('week');
            if ($requestedWeekIndex !== null) {
                $selectedWeek = collect($calendar)->firstWhere('week_index', (int) $requestedWeekIndex);
            }

            if (! $selectedWeek) {
                $selectedWeek = $calendarService->findDefaultWeek($calendar, now()) ?? ($calendar[0] ?? null);
            }
        }

        $weekIndex = $selectedWeek['week_index'] ?? 1;

        // Hari yang dipilih: 1: Senin ... 6: Sabtu (Minggu ditolak/tidak ada)
        $defaultDay = 1;
        $today = now();
        if ($selectedWeek && $today->toDateString() >= $selectedWeek['start_date'] && $today->toDateString() <= $selectedWeek['saturday_date']) {
            $isoDay = $today->dayOfWeekIso;
            if ($isoDay >= 1 && $isoDay <= 6) {
                $defaultDay = $isoDay;
            }
        }

        $day = max(1, min(6, (int) $request->query('day', $defaultDay)));

        // Hitung tanggal konkret berdasarkan selectedWeek dan day
        $date = now();
        $selectedDayInfo = null;
        if ($selectedWeek) {
            if ($day === 6) {
                $date = Carbon::parse($selectedWeek['saturday_date']);
                $selectedDayInfo = $selectedWeek['saturday'];
            } else {
                $dayIndex = $day - 1;
                $date = Carbon::parse($selectedWeek['days'][$dayIndex]['date']);
                $selectedDayInfo = $selectedWeek['days'][$dayIndex];
            }
        }

        $selectedRoomId = $request->query('room_id');
        $allRooms = Room::where('is_active', true)->orderBy('id')->get();
        $rooms = $allRooms->when($selectedRoomId, fn ($q, $id) => $q->where('id', $id));

        // Tampilan jadwal kuliah:
        // Pada minggu UTS, UAS, dan LIBUR tampilan jadwal kuliah kosong!
        $sessions = collect();
        $isNonLectureWeek = $selectedWeek && $selectedWeek['type'] !== WeekType::KULIAH;

        if ($period && $selectedWeek && $selectedWeek['type'] === WeekType::KULIAH && $day >= 1 && $day <= 5) {
            $meetingNo = $selectedWeek['meeting_no'];

            $sessions = ScheduleSession::with(['schedule.course.studyProgram', 'schedule.lecturer', 'room', 'schedule.room'])
                ->whereHas('schedule', function ($q) use ($period, $day) {
                    $q->where('period_id', $period->id)
                      ->where('day_of_week', $day);
                })
                ->where('week_no', $meetingNo)
                ->where('is_active', true)
                ->get();
        }

        // Ambil data peminjaman (booking_detail) pada tanggal yang dipilih
        $bookings = BookingDetail::with(['booking.user', 'room'])
            ->where('booking_date', $date->toDateString())
            ->whereIn('status', [BookingDetailStatus::PENDING, BookingDetailStatus::APPROVED])
            ->get();

        // Tentukan apakah user merupakan role internal
        $user = $request->user();
        $isInternal = $user && $user->isInternal($period?->id);

        // Format nama display untuk peminjaman
        $bookings->each(function (BookingDetail $bookingDetail) use ($isInternal) {
            $rawName = $bookingDetail->booking->pic_name ?? $bookingDetail->booking->user?->name ?? 'Peminjam';
            if ($isInternal) {
                $bookingDetail->display_requester = $rawName;
            } else {
                $words = array_filter(explode(' ', trim($rawName)));
                $initials = array_map(fn ($w) => strtolower(mb_substr($w, 0, 1)), $words);
                $bookingDetail->display_requester = 'Peminjaman oleh ' . (implode('.', $initials) ?: 'pemohon');
            }
        });

        // Ambil jam operasional lab dari Setting
        $labOpen = Setting::get('lab_open_time', '07:00');
        $labClose = Setting::get('lab_close_time', '22:00');
        if ($day === 6) {
            $labOpen = Setting::get('lab_open_time_saturday', $labOpen);
            $labClose = Setting::get('lab_close_time_saturday', $labClose);
        }

        $openHour = (int) substr($labOpen, 0, 2);
        $openMin  = (int) substr($labOpen, 3, 2);
        $closeHour = (int) substr($labClose, 0, 2);
        $closeMin  = (int) substr($labClose, 3, 2);
        if ($closeHour <= $openHour && $closeMin <= $openMin) {
            $openHour = 7;
            $openMin = 0;
            $closeHour = 22;
            $closeMin = 0;
        }

        // Bangun time slot per 30 menit: "07:00 - 07:30", "07:30 - 08:00", ...
        $timeSlots = [];
        $curH = $openHour;
        $curM = $openMin;
        while ($curH < $closeHour || ($curH === $closeHour && $curM < $closeMin)) {
            $slotStart = sprintf('%02d:%02d', $curH, $curM);
            $curM += 30;
            if ($curM >= 60) {
                $curM -= 60;
                $curH++;
            }
            $slotEnd = sprintf('%02d:%02d', $curH, $curM);
            $timeSlots[] = [
                'start' => $slotStart,
                'end'   => $slotEnd,
                'label' => "{$slotStart} - {$slotEnd}",
            ];
        }

        // Ambil semua study program aktif untuk keterangan warna (legend)
        $studyPrograms = StudyProgram::where('is_active', true)->orderBy('id')->get();

        return view('welcome', compact(
            'allPeriods',
            'period',
            'calendar',
            'selectedWeek',
            'weekIndex',
            'day',
            'date',
            'selectedDayInfo',
            'allRooms',
            'rooms',
            'selectedRoomId',
            'sessions',
            'bookings',
            'isNonLectureWeek',
            'timeSlots',
            'isInternal',
            'studyPrograms'
        ));
    }
}
