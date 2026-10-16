<?php

namespace App\Services;

use App\Enums\WeekType;
use Carbon\Carbon;
use Carbon\CarbonInterface;
use InvalidArgumentException;

class PeriodWeekCalendar
{
    private const INDONESIAN_MONTHS = [
        1 => 'Januari',
        2 => 'Februari',
        3 => 'Maret',
        4 => 'April',
        5 => 'Mei',
        6 => 'Juni',
        7 => 'Juli',
        8 => 'Agustus',
        9 => 'September',
        10 => 'Oktober',
        11 => 'November',
        12 => 'Desember',
    ];

    /**
     * Menghitung kalender mingguan periode secara murni tanpa query database.
     *
     * @param string $startDate Y-m-d (wajib hari Senin)
     * @param string $endDate Y-m-d (akhir UAS)
     * @param string|null $utsStart Y-m-d
     * @param string|null $utsEnd Y-m-d
     * @param string|null $uasStart Y-m-d
     * @param string|null $uasEnd Y-m-d
     * @param array $holidays Array of ['start_date' => string, 'end_date' => string, 'note' => ?string]
     * @return array
     */
    public function buildCalendar(
        string $startDate,
        string $endDate,
        ?string $utsStart = null,
        ?string $utsEnd = null,
        ?string $uasStart = null,
        ?string $uasEnd = null,
        array $holidays = []
    ): array {
        $start = Carbon::parse($startDate)->startOfDay();
        $end = Carbon::parse($endDate)->startOfDay();

        if ($start->dayOfWeekIso !== 1) {
            throw new InvalidArgumentException("Period start_date must be a Monday (Senin). Given: {$startDate}");
        }

        if ($end->lt($start)) {
            throw new InvalidArgumentException("Period end_date cannot be earlier than start_date.");
        }

        $utsStartCarbon = $utsStart ? Carbon::parse($utsStart)->startOfDay() : null;
        $utsEndCarbon = $utsEnd ? Carbon::parse($utsEnd)->startOfDay() : null;
        $uasStartCarbon = $uasStart ? Carbon::parse($uasStart)->startOfDay() : null;
        $uasEndCarbon = $uasEnd ? Carbon::parse($uasEnd)->startOfDay() : null;

        $normalizedHolidays = [];
        foreach ($holidays as $h) {
            $hStart = Carbon::parse($h['start_date'])->startOfDay();
            $hEnd = Carbon::parse($h['end_date'])->startOfDay();
            $normalizedHolidays[] = [
                'start' => $hStart,
                'end' => $hEnd,
                'note' => $h['note'] ?? null,
            ];
        }

        $calendar = [];
        $weekIndex = 1;
        $meetingCounter = 0;

        for ($monday = $start->copy(); $monday->lte($end); $monday = $monday->copy()->addWeek()) {
            $friday = $monday->copy()->addDays(4);
            $saturday = $monday->copy()->addDays(5);

            // Cek 5 hari kerja (Senin s/d Jumat)
            $weekDays = [];
            $allDaysCoveredByHoliday = true;
            $holidayNotesInWeek = [];

            for ($i = 0; $i < 5; $i++) {
                $dayDate = $monday->copy()->addDays($i);
                $dayStr = $dayDate->toDateString();
                $matchingHolidayNote = $this->findHolidayNote($dayDate, $normalizedHolidays);

                if ($matchingHolidayNote === null) {
                    $allDaysCoveredByHoliday = false;
                } else {
                    $holidayNotesInWeek[] = $matchingHolidayNote;
                }

                $weekDays[] = [
                    'date' => $dayStr,
                    'day_of_week' => $i + 1, // 1: Senin ... 5: Jumat
                    'is_holiday' => $matchingHolidayNote !== null,
                    'holiday_note' => $matchingHolidayNote,
                ];
            }

            // Sabtu untuk peminjaman ruangan
            $saturdayStr = $saturday->toDateString();
            $saturdayHolidayNote = $this->findHolidayNote($saturday, $normalizedHolidays);
            $saturdayDay = [
                'date' => $saturdayStr,
                'day_of_week' => 6,
                'is_holiday' => $saturdayHolidayNote !== null,
                'holiday_note' => $saturdayHolidayNote,
            ];

            // 1. Cek rentang UTS
            $isUts = false;
            if ($utsStartCarbon && $utsEndCarbon) {
                $isUts = $this->datesOverlap($monday, $friday, $utsStartCarbon, $utsEndCarbon);
            }

            // 2. Cek rentang UAS
            $isUas = false;
            if ($uasStartCarbon && $uasEndCarbon) {
                $isUas = $this->datesOverlap($monday, $friday, $uasStartCarbon, $uasEndCarbon);
            }

            $dateRangeLabel = $this->formatDateRange($monday, $friday);

            if ($isUts) {
                $type = WeekType::UTS;
                $meetingNo = null;
                $label = "UTS ({$dateRangeLabel})";
                $title = 'UTS';
            } elseif ($isUas) {
                $type = WeekType::UAS;
                $meetingNo = null;
                $label = "UAS ({$dateRangeLabel})";
                $title = 'UAS';
            } elseif ($allDaysCoveredByHoliday) {
                // Seluruh Senin-Jumat tertutup libur -> minggu LIBUR (dilewati, tanpa nomor pertemuan)
                $type = WeekType::LIBUR;
                $meetingNo = null;
                $uniqueNotes = array_values(array_unique($holidayNotesInWeek));
                $holidayName = !empty($uniqueNotes) ? $uniqueNotes[0] : 'Libur';
                // Hindari "Libur Libur ..."
                if (stripos($holidayName, 'libur') === 0) {
                    $title = $holidayName;
                } else {
                    $title = "Libur {$holidayName}";
                }
                // Jika note panjang seperti "NATARU (Natal dan Tahun Baru)", potong ringkas jika perlu
                if (str_contains($holidayName, 'NATARU')) {
                    $title = 'Libur NATARU';
                }
                $label = "{$title} ({$dateRangeLabel})";
            } else {
                // Minggu KULIAH
                $type = WeekType::KULIAH;
                $meetingCounter++;
                $meetingNo = $meetingCounter;
                $title = "Minggu {$meetingNo}";
                $label = "Minggu {$meetingNo} ({$dateRangeLabel})";
            }

            $calendar[] = [
                'week_index' => $weekIndex,
                'meeting_no' => $meetingNo,
                'type' => $type,
                'title' => $title,
                'label' => $label,
                'start_date' => $monday->toDateString(),
                'end_date' => $friday->toDateString(),
                'saturday_date' => $saturdayStr,
                'date_range_label' => $dateRangeLabel,
                'days' => $weekDays,
                'saturday' => $saturdayDay,
            ];

            $weekIndex++;
        }

        return $calendar;
    }

    /**
     * Format rentang tanggal label Senin sampai Jumat dalam bahasa Indonesia.
     * Contoh:
     * - Satu bulan: "14 - 18 September 2026"
     * - Lintas bulan: "30 November - 4 Desember 2026"
     * - Lintas tahun: "28 Desember 2026 - 1 Januari 2027"
     */
    public function formatDateRange(CarbonInterface $start, CarbonInterface $end): string
    {
        $startDay = $start->day;
        $endDay = $end->day;
        $startMonth = self::INDONESIAN_MONTHS[$start->month] ?? '';
        $endMonth = self::INDONESIAN_MONTHS[$end->month] ?? '';
        $startYear = $start->year;
        $endYear = $end->year;

        if ($startYear !== $endYear) {
            return "{$startDay} {$startMonth} {$startYear} - {$endDay} {$endMonth} {$endYear}";
        }

        if ($start->month !== $end->month) {
            return "{$startDay} {$startMonth} - {$endDay} {$endMonth} {$startYear}";
        }

        return "{$startDay} - {$endDay} {$endMonth} {$endYear}";
    }

    /**
     * Mencari minggu default dari kalender.
     * Minggu default = minggu yang memuat hari ini; jika di luar periode, pakai minggu KULIAH terdekat.
     */
    public function findDefaultWeek(array $calendar, ?CarbonInterface $today = null): ?array
    {
        if (empty($calendar)) {
            return null;
        }

        $now = $today ? $today->copy()->startOfDay() : Carbon::now()->startOfDay();
        $nowStr = $now->toDateString();

        // 1. Cari minggu yang memuat hari ini (Senin sampai Sabtu)
        foreach ($calendar as $week) {
            if ($nowStr >= $week['start_date'] && $nowStr <= $week['saturday_date']) {
                return $week;
            }
        }

        // 2. Jika hari ini di luar periode, cari minggu KULIAH terdekat
        $kuliahWeeks = array_values(array_filter($calendar, fn ($w) => $w['type'] === WeekType::KULIAH));
        if (empty($kuliahWeeks)) {
            return $calendar[0];
        }

        $firstKuliah = $kuliahWeeks[0];
        $lastKuliah = $kuliahWeeks[count($kuliahWeeks) - 1];

        if ($nowStr < $firstKuliah['start_date']) {
            return $firstKuliah;
        }

        if ($nowStr > $lastKuliah['end_date']) {
            return $lastKuliah;
        }

        // Jika berada di antara minggu (misal saat libur panjang / UTS / UAS di tengah periode),
        // cari minggu kuliah dengan jarak minimum dari hari ini
        $closestWeek = null;
        $minDiffDays = PHP_INT_MAX;

        foreach ($kuliahWeeks as $w) {
            $wStart = Carbon::parse($w['start_date']);
            $diff = abs($now->diffInDays($wStart, false));
            if ($diff < $minDiffDays) {
                $minDiffDays = $diff;
                $closestWeek = $w;
            }
        }

        return $closestWeek ?? $firstKuliah;
    }

    /**
     * Memeriksa apakah rentang [start1, end1] beririsan dengan [start2, end2].
     */
    private function datesOverlap(
        CarbonInterface $start1,
        CarbonInterface $end1,
        CarbonInterface $start2,
        CarbonInterface $end2
    ): bool {
        return $start1->lte($end2) && $end1->gte($start2);
    }

    /**
     * Mencari apakah tanggal tertentu berada di dalam salah satu holiday.
     */
    private function findHolidayNote(CarbonInterface $date, array $normalizedHolidays): ?string
    {
        foreach ($normalizedHolidays as $h) {
            if ($date->betweenIncluded($h['start'], $h['end'])) {
                return $h['note'] ?? 'Libur';
            }
        }

        return null;
    }
}
