<?php

namespace App\Services;

use App\Enums\WeekType;
use App\Models\Period;
use App\Models\Schedule;
use App\Models\ScheduleSession;
use Illuminate\Support\Collection;

class ScheduleSessionGenerator
{
    public function __construct(
        private readonly PeriodWeekCalendar $calendarService
    ) {}

    /**
     * Generate atau regenerate baris schedule_session untuk sebuah periode.
     * Aturan:
     * 1. Hanya minggu KULIAH yang dapat baris, UTS/UAS/LIBUR dikosongkan.
     * 2. Regenerate TIDAK boleh menimpa baris yang sudah ada/diubah manual (is_active, override ruang/jam, note),
     *    hanya menambah baris yang belum ada.
     * 3. Hari libur sebagian di minggu kuliah menghasilkan sesi dengan is_active = false dan note libur.
     *
     * @return array [created => int, skipped => int]
     */
    public function generateForPeriod(Period $period): array
    {
        $holidays = $period->holidays()->get(['start_date', 'end_date', 'note'])->toArray();

        $calendar = $this->calendarService->buildCalendar(
            $period->start_date->toDateString(),
            $period->end_date->toDateString(),
            $period->uts_start?->toDateString(),
            $period->uts_end?->toDateString(),
            $period->uas_start?->toDateString(),
            $period->uas_end?->toDateString(),
            $holidays
        );

        // Filter hanya minggu KULIAH
        $kuliahWeeks = array_filter($calendar, fn ($w) => $w['type'] === WeekType::KULIAH);

        // Ambil semua jadwal untuk periode ini
        $schedules = Schedule::where('period_id', $period->id)->get();

        // Ambil existing sessions untuk periode ini agar cepat dicari
        $scheduleIds = $schedules->pluck('id')->all();
        $existingSessions = ScheduleSession::whereIn('schedule_id', $scheduleIds)
            ->get()
            ->keyBy(fn ($s) => "{$s->schedule_id}_{$s->week_no}");

        $createdCount = 0;
        $skippedCount = 0;
        $batch = [];

        foreach ($schedules as $schedule) {
            foreach ($kuliahWeeks as $week) {
                $meetingNo = $week['meeting_no'];

                $key = "{$schedule->id}_{$meetingNo}";

                // Jika baris sesi sudah ada, jangan pernah ditimpa (pertahankan override manual)
                if ($existingSessions->has($key)) {
                    $skippedCount++;
                    continue;
                }

                // Cari hari yang sesuai dengan day_of_week (1: Senin ... 5: Jumat)
                $dayIndex = $schedule->day_of_week - 1;
                $dayInfo = $week['days'][$dayIndex] ?? null;

                if (! $dayInfo) {
                    continue;
                }

                $sessionDate = $dayInfo['date'];
                $isHoliday = $dayInfo['is_holiday'];
                $note = $dayInfo['holiday_note'];

                $batch[] = [
                    'schedule_id' => $schedule->id,
                    'week_no' => $meetingNo,
                    'session_date' => $sessionDate,
                    'is_active' => ! $isHoliday,
                    'room_id' => null,
                    'start_time' => null,
                    'end_time' => null,
                    'note' => $note,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];

                $createdCount++;

                if (count($batch) >= 500) {
                    ScheduleSession::insert($batch);
                    $batch = [];
                }
            }
        }

        if (! empty($batch)) {
            ScheduleSession::insert($batch);
        }

        return [
            'created' => $createdCount,
            'skipped' => $skippedCount,
        ];
    }
}
