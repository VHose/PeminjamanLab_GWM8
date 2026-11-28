<?php

namespace App\Http\Controllers;

use App\Enums\BookingDetailStatus;
use App\Enums\WeekType;
use App\Models\BookingDetail;
use App\Models\Period;
use App\Models\Room;
use App\Models\ScheduleSession;
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
        $allPeriods = Period::with('holidays')->orderByDesc('start_date')->get();
        $selectedPeriodId = $request->query('period_id');

        $defaultPeriod = $allPeriods->firstWhere('is_active', true) ?: $allPeriods->first();
        $period = $selectedPeriodId
            ? ($allPeriods->firstWhere('id', $selectedPeriodId) ?: $defaultPeriod)
            : $defaultPeriod;

        $today = now();
        $calendarsByPeriod = [];
        $kuliahWeekByMeeting = [];
        $defaultWeekByPeriod = [];

        foreach ($allPeriods as $p) {
            $pHolidays = $p->holidays->map(fn ($h) => [
                'start_date' => $h->start_date->toDateString(),
                'end_date'   => $h->end_date->toDateString(),
                'note'       => $h->note,
            ])->all();

            $pCal = $calendarService->buildCalendar(
                $p->start_date->toDateString(),
                $p->end_date->toDateString(),
                $p->uts_start?->toDateString(),
                $p->uts_end?->toDateString(),
                $p->uas_start?->toDateString(),
                $p->uas_end?->toDateString(),
                $pHolidays
            );

            $calendarsByPeriod[$p->id] = $pCal;
            $defWeek = $calendarService->findDefaultWeek($pCal, $today) ?? ($pCal[0] ?? null);
            $defaultWeekByPeriod[$p->id] = $defWeek['week_index'] ?? 1;

            $kuliahWeekByMeeting[$p->id] = [];
            foreach ($pCal as $w) {
                if ($w['type'] === WeekType::KULIAH && ! empty($w['meeting_no'])) {
                    $kuliahWeekByMeeting[$p->id][(int) $w['meeting_no']] = $w;
                }
            }
        }

        $calendar = $period ? ($calendarsByPeriod[$period->id] ?? []) : [];
        $selectedWeek = null;

        if ($period && ! empty($calendar)) {
            $requestedWeekIndex = $request->query('week');
            if ($requestedWeekIndex !== null) {
                $selectedWeek = collect($calendar)->firstWhere('week_index', (int) $requestedWeekIndex);
            }

            if (! $selectedWeek) {
                $defIdx = $defaultWeekByPeriod[$period->id] ?? 1;
                $selectedWeek = collect($calendar)->firstWhere('week_index', $defIdx) ?? ($calendar[0] ?? null);
            }
        }

        $weekIndex = $selectedWeek['week_index'] ?? 1;

        // Hari yang dipilih: 1: Senin ... 6: Sabtu (Minggu ditolak/tidak ada)
        $defaultDay = 1;
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

        // Hak akses sesi nonaktif (libur) dan toggle
        $user = $request->user();
        $canSeeInactiveSessions = false;
        $canToggleSession = false;

        if ($user) {
            if ($user->hasRole('Admin', 'Kepala_Lab', 'Staf_Lab')) {
                $canSeeInactiveSessions = true;
                $canToggleSession = true;
            } elseif ($user->hasRole('Kepala_Prodi', 'Dosen')) {
                $canSeeInactiveSessions = true;
            }
        }

        // Tentukan apakah user merupakan role internal
        $isInternal = $user && $user->isInternal($period?->id);

        // Ambil seluruh sesi jadwal kuliah dari seluruh periode dalam 1 query dan deduplikasi kelas gabungan (Gabung)
        $allRawSessions = collect();
        if ($allPeriods->isNotEmpty()) {
            $sessionsQuery = ScheduleSession::with(['schedule.course.studyProgram', 'schedule.lecturer', 'room', 'schedule.room'])
                ->whereHas('schedule', function ($q) use ($allPeriods) {
                    $q->whereIn('period_id', $allPeriods->pluck('id'));
                });

            if (! $canSeeInactiveSessions) {
                $sessionsQuery->where('is_active', true);
            }

            $allRawSessions = $sessionsQuery->get();
        }

        $dedupedSessions = $allRawSessions
            ->groupBy(function (ScheduleSession $s) {
                $sc = $s->schedule;
                if (! $sc) {
                    return 'single_' . $s->schedule_id . '_' . $s->week_no;
                }
                return "{$sc->period_id}|{$sc->day_of_week}|{$sc->room_id}|{$sc->start_time}|{$sc->end_time}|{$s->week_no}";
            })
            ->map(function ($group) use ($user, $isInternal) {
                $sorted = $group->sortBy([
                    fn (ScheduleSession $s) => $s->is_active ? 0 : 1,
                    fn (ScheduleSession $s) => stripos($s->schedule?->course?->name ?? '', 'Gabung') !== false ? 1 : 0,
                    fn (ScheduleSession $s) => $s->schedule_id,
                ])->values();

                /** @var ScheduleSession $primary */
                $primary = $sorted->first();
                $siblingSchedules = $sorted->pluck('schedule')->filter()->values();

                $primary->can_manage_group = $user
                    ? $user->canManageSchedule($primary->schedule, $siblingSchedules)
                    : false;
                $primary->combined_schedule_ids = $sorted->pluck('schedule_id')->map(fn ($id) => (int) $id)->values()->all();
                $primary->combined_search_text = $sorted->map(function (ScheduleSession $x) use ($isInternal) {
                    $cName = $x->schedule?->course?->name ?? '';
                    $cId = $x->schedule?->course_id ?? '';
                    $cls = $x->schedule?->class_code ?? '';
                    $lec = ($isInternal && $x->schedule?->lecturer) ? $x->schedule->lecturer->name : '';
                    return trim("{$cName} {$cId} {$cls} {$lec}");
                })->join(' ');

                return $primary;
            })
            ->values();

        // Ambil seluruh data peminjaman (PENDING & APPROVED) sekali saja untuk front-end & tanggal awal
        $allBookings = BookingDetail::with(['booking.user', 'room'])
            ->whereIn('status', [BookingDetailStatus::PENDING, BookingDetailStatus::APPROVED])
            ->get();

        $allBookings->each(function (BookingDetail $bookingDetail) use ($isInternal) {
            $rawName = $bookingDetail->booking->pic_name ?? $bookingDetail->booking->user?->name ?? 'Peminjam';
            if ($isInternal) {
                $bookingDetail->display_requester = $rawName;
            } else {
                $words = array_filter(explode(' ', trim($rawName)));
                $initials = array_map(fn ($w) => strtolower(mb_substr($w, 0, 1)), $words);
                $bookingDetail->display_requester = 'Peminjaman oleh ' . (implode('.', $initials) ?: 'pemohon');
            }
        });

        // Jam operasional lab: Senin - Sabtu (07:00 - 22:00) per 30 menit
        $timeSlots = [];
        $curH = 7;
        $curM = 0;
        while ($curH < 22) {
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

        // Ambil study program aktif yang relevan dengan jadwal lab untuk keterangan warna (legend)
        $studyPrograms = StudyProgram::where('is_active', true)
            ->where(function ($q) {
                $q->whereIn('id', ['72', '73', '79'])
                  ->orWhereHas('courses');
            })
            ->orderBy('id')
            ->get();

        $pendingVisitorCount = ($user && $user->hasRole('Kepala_Lab', 'Staf_Lab', 'Admin'))
            ? \App\Models\User::where('status', 'pending')->count()
            : 0;

        $defaultPeriodObj = $defaultPeriod;
        $defaultCal = $defaultPeriodObj ? ($calendarsByPeriod[$defaultPeriodObj->id] ?? []) : [];
        $defaultWeekIdx = $defaultPeriodObj ? ($defaultWeekByPeriod[$defaultPeriodObj->id] ?? 1) : 1;
        $defaultWeekObj = collect($defaultCal)->firstWhere('week_index', $defaultWeekIdx) ?? ($defaultCal[0] ?? null);
        $defaultHomeDay = 1;
        if ($defaultWeekObj && $today->toDateString() >= $defaultWeekObj['start_date'] && $today->toDateString() <= $defaultWeekObj['saturday_date']) {
            $iso = $today->dayOfWeekIso;
            if ($iso >= 1 && $iso <= 6) {
                $defaultHomeDay = $iso;
            }
        }

        $frontendPeriods = $allPeriods->map(function (Period $p) use ($calendarsByPeriod, $defaultWeekByPeriod) {
            $pCal = collect($calendarsByPeriod[$p->id] ?? [])->map(function (array $w) {
                return [
                    'week_index'    => $w['week_index'],
                    'label'         => $w['label'],
                    'type'          => $w['type'] instanceof WeekType ? $w['type']->value : (string) $w['type'],
                    'start_date'    => $w['start_date'],
                    'saturday_date' => $w['saturday_date'],
                    'days'          => array_map(fn ($d) => [
                        'date'         => $d['date'],
                        'is_holiday'   => (bool) ($d['is_holiday'] ?? false),
                        'holiday_note' => $d['holiday_note'] ?? null,
                    ], $w['days']),
                    'saturday'      => [
                        'date'         => $w['saturday']['date'],
                        'is_holiday'   => (bool) ($w['saturday']['is_holiday'] ?? false),
                        'holiday_note' => $w['saturday']['holiday_note'] ?? null,
                    ],
                ];
            })->values()->all();

            return [
                'id'                 => $p->id,
                'name'               => $p->name,
                'is_active'          => (bool) $p->is_active,
                'start_date'         => $p->start_date->toDateString(),
                'end_date'           => $p->end_date->toDateString(),
                'default_week_index' => $defaultWeekByPeriod[$p->id] ?? 1,
                'calendar'           => $pCal,
            ];
        })->values()->all();

        $frontendSessions = $dedupedSessions->map(function (ScheduleSession $s) use ($kuliahWeekByMeeting, $isInternal) {
            $pId = (int) ($s->schedule?->period_id ?? 0);
            $dayIdx = max(0, ((int) ($s->schedule?->day_of_week ?? 1)) - 1);
            $defaultDateStr = $kuliahWeekByMeeting[$pId][(int) $s->week_no]['days'][$dayIdx]['date']
                ?? $s->session_date->toDateString();
            $curDateStr = $s->session_date->toDateString();

            return [
                'schedule_id'     => (int) $s->schedule_id,
                'combined_ids'    => $s->combined_schedule_ids ?? [(int) $s->schedule_id],
                'week_no'         => (int) $s->week_no,
                'period_id'       => $pId,
                'date'            => $curDateStr,
                'default_date'    => $defaultDateStr,
                'is_active'       => (bool) $s->is_active,
                'room_id'         => $s->effectiveRoomId(),
                'override_room'   => $s->room_id ?? '',
                'start'           => substr($s->effectiveStartTime(), 0, 5),
                'end'             => substr($s->effectiveEndTime(), 0, 5),
                'override_start'  => $s->start_time ? substr($s->start_time, 0, 5) : '',
                'override_end'    => $s->end_time ? substr($s->end_time, 0, 5) : '',
                'parent_room'     => $s->schedule?->room ? $s->schedule->room->labelWithCapacity() : '-',
                'parent_start'    => substr($s->schedule?->start_time ?? '', 0, 5),
                'parent_end'      => substr($s->schedule?->end_time ?? '', 0, 5),
                'course_name'     => $s->schedule?->course?->name ?? 'Kuliah',
                'class_code'      => $s->schedule?->class_code ?? '',
                'color_hex'       => $s->schedule?->course?->studyProgram?->color_hex ?? '#E2E3E5',
                'lecturer_name'   => ($isInternal && $s->schedule?->lecturer) ? $s->schedule->lecturer->name : '',
                'note'            => $s->note ?? '',
                'can_manage'      => (bool) ($s->can_manage_group ?? false),
                'toggle_url'      => route('admin.schedule-sessions.toggle', [$s->schedule_id, $s->week_no]),
                'update_url'      => route('admin.schedule-sessions.update', [$s->schedule_id, $s->week_no]),
                'search_text'     => mb_strtolower(($s->combined_search_text ?? '') . ' ' . ($s->note ?? '')),
            ];
        })->values()->all();

        $frontendBookings = $allBookings->map(function (BookingDetail $b) use ($isInternal) {
            $purposeShort = $isInternal ? \Illuminate\Support\Str::limit($b->booking?->purpose ?? '', 25) : '';
            $isPending = $b->status === BookingDetailStatus::PENDING;
            return [
                'key'               => 'bk_' . $b->booking_id . '_' . $b->room_id . '_' . $b->start_time,
                'date'              => $b->booking_date->toDateString(),
                'room_id'           => $b->room_id,
                'start'             => substr($b->start_time, 0, 5),
                'end'               => substr($b->end_time, 0, 5),
                'is_pending'        => $isPending,
                'display_requester' => $b->display_requester,
                'purpose_short'     => $purposeShort,
                'search_text'       => mb_strtolower($b->display_requester . ' ' . ($isInternal ? ($b->booking?->purpose ?? '') : '') . ' ' . ($isPending ? 'pending' : 'disetujui')),
            ];
        })->values()->all();

        $frontendRooms = $allRooms->map(fn (Room $r) => [
            'id'    => $r->id,
            'name'  => $r->name,
            'label' => $r->labelWithCapacity(),
        ])->values()->all();

        $minBookingDatetime = now()->addDays(2)->format('Y-m-d H:i');

        $initialVisibleRooms = $rooms->map(fn (Room $r) => [
            'id'    => $r->id,
            'name'  => $r->name,
            'label' => $r->labelWithCapacity(),
        ])->values()->all();

        $targetDateStr = $date->toDateString();
        $initialDaySessions = ($period && $selectedWeek && $day >= 1 && $day <= 6)
            ? array_values(array_filter($frontendSessions, fn (array $s) => (int) $s['period_id'] === (int) $period->id && $s['date'] === $targetDateStr))
            : [];
        $initialDayBookings = array_values(array_filter($frontendBookings, fn (array $b) => $b['date'] === $targetDateStr));

        $initialGridRows = $this->buildGridRows(
            $initialVisibleRooms,
            $timeSlots,
            $initialDaySessions,
            $initialDayBookings,
            $targetDateStr,
            $date->isSunday(),
            $minBookingDatetime
        );

        $scheduleFrontendPayload = base64_encode(json_encode([
            'is_internal'          => (bool) $isInternal,
            'can_see_inactive'     => (bool) $canSeeInactiveSessions,
            'min_booking_datetime' => $minBookingDatetime,
            'default_state'        => [
                'period_id' => $defaultPeriodObj ? (string) $defaultPeriodObj->id : '',
                'week'      => (int) $defaultWeekIdx,
                'day'       => (int) $defaultHomeDay,
                'room_id'   => '',
            ],
            'time_slots'           => $timeSlots,
            'rooms'                => $frontendRooms,
            'periods'              => $frontendPeriods,
            'sessions'             => $frontendSessions,
            'bookings'             => $frontendBookings,
        ]));

        return view('welcome', compact(
            'allPeriods',
            'period',
            'calendar',
            'weekIndex',
            'day',
            'date',
            'selectedDayInfo',
            'allRooms',
            'rooms',
            'selectedRoomId',
            'isInternal',
            'studyPrograms',
            'canSeeInactiveSessions',
            'pendingVisitorCount',
            'initialGridRows',
            'scheduleFrontendPayload'
        ));
    }

    private function buildGridRows(
        array $visibleRooms,
        array $timeSlots,
        array $daySessions,
        array $dayBookings,
        string $targetDateStr,
        bool $isSunday,
        string $minBookingDatetime
    ): array {
        $totalSlots = count($timeSlots);
        $gridOccupants = [];

        foreach ($visibleRooms as $room) {
            $rId = $room['id'];
            $gridOccupants[$rId] = [];
            foreach ($timeSlots as $slotIdx => $slot) {
                $slotStart = $slot['start'];
                $activeSess = null;
                $inactiveSess = null;
                foreach ($daySessions as $s) {
                    if ($s['room_id'] === $rId && $s['start'] <= $slotStart && $s['end'] > $slotStart) {
                        if ($s['is_active'] && ! $activeSess) {
                            $activeSess = $s;
                        } elseif (! $s['is_active'] && ! $inactiveSess) {
                            $inactiveSess = $s;
                        }
                    }
                }
                $bk = null;
                foreach ($dayBookings as $b) {
                    if ($b['room_id'] === $rId && $b['start'] <= $slotStart && $b['end'] > $slotStart) {
                        $bk = $b;
                        break;
                    }
                }

                if ($activeSess) {
                    $gridOccupants[$rId][$slotIdx] = ['type' => 'active_session', 'key' => 'sess_' . $activeSess['schedule_id'] . '_' . $activeSess['week_no'], 'item' => $activeSess, 'inactive' => null];
                } elseif ($bk) {
                    $gridOccupants[$rId][$slotIdx] = ['type' => 'booking', 'key' => $bk['key'], 'item' => $bk, 'inactive' => $inactiveSess];
                } elseif ($inactiveSess) {
                    $gridOccupants[$rId][$slotIdx] = ['type' => 'inactive_session', 'key' => 'inactive_' . $inactiveSess['schedule_id'] . '_' . $inactiveSess['week_no'], 'item' => $inactiveSess, 'inactive' => null];
                } else {
                    $gridOccupants[$rId][$slotIdx] = ['type' => 'empty', 'key' => null, 'item' => null, 'inactive' => null];
                }
            }
        }

        $cellMeta = [];
        foreach ($visibleRooms as $room) {
            $rId = $room['id'];
            $cellMeta[$rId] = [];
            $slotIdx = 0;
            while ($slotIdx < $totalSlots) {
                $occ = $gridOccupants[$rId][$slotIdx];
                if ($occ['type'] === 'empty') {
                    $cellMeta[$rId][$slotIdx] = ['render' => true, 'rowspan' => 1, 'data' => $occ];
                    $slotIdx++;
                } else {
                    $span = 1;
                    while (($slotIdx + $span) < $totalSlots && $gridOccupants[$rId][$slotIdx + $span]['key'] === $occ['key']) {
                        $span++;
                    }
                    $cellMeta[$rId][$slotIdx] = ['render' => true, 'rowspan' => $span, 'data' => $occ];
                    for ($s = 1; $s < $span; $s++) {
                        $cellMeta[$rId][$slotIdx + $s] = ['render' => false, 'rowspan' => 0, 'data' => null];
                    }
                    $slotIdx += $span;
                }
            }
        }

        $rows = [];
        foreach ($timeSlots as $slotIdx => $slot) {
            $isBookable = ! $isSunday && ("{$targetDateStr} {$slot['start']}" >= $minBookingDatetime);
            $cells = [];
            foreach ($visibleRooms as $room) {
                $meta = $cellMeta[$room['id']][$slotIdx] ?? null;
                if (! $meta || ! $meta['render']) {
                    $cells[] = ['render' => false];
                    continue;
                }
                $occ = $meta['data'];
                $cells[] = [
                    'render'     => true,
                    'rowspan'    => $meta['rowspan'],
                    'type'       => $occ['type'],
                    'item'       => $occ['item'],
                    'inactive'   => $occ['inactive'],
                    'bookable'   => $isBookable,
                    'room_id'    => $room['id'],
                    'room_label' => $room['label'],
                    'date'       => $targetDateStr,
                ];
            }
            $rows[] = [
                'slot_idx' => $slotIdx,
                'label'    => $slot['label'],
                'cells'    => $cells,
            ];
        }

        return $rows;
    }
}
