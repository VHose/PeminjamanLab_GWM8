<?php

namespace Tests\Unit;

use App\Enums\WeekType;
use App\Services\PeriodWeekCalendar;
use Carbon\Carbon;
use PHPUnit\Framework\TestCase;

class PeriodWeekCalendarTest extends TestCase
{
    private PeriodWeekCalendar $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new PeriodWeekCalendar();
    }

    public function test_ganjil_2026_2027_calculation(): void
    {
        $startDate = '2026-09-14';
        $endDate = '2027-01-29';
        $utsStart = '2026-11-02';
        $utsEnd = '2026-11-13';
        $uasStart = '2027-01-18';
        $uasEnd = '2027-01-29';

        $holidays = [
            [
                'start_date' => '2026-12-21',
                'end_date' => '2027-01-03',
                'note' => 'NATARU (Natal dan Tahun Baru)',
            ],
            [
                'start_date' => '2027-01-04',
                'end_date' => '2027-01-04',
                'note' => 'Libur (merah di kalender akademik)',
            ],
            [
                'start_date' => '2027-01-05',
                'end_date' => '2027-01-05',
                'note' => "Isra' Mi'raj Nabi Muhammad SAW",
            ],
        ];

        $calendar = $this->service->buildCalendar(
            $startDate,
            $endDate,
            $utsStart,
            $utsEnd,
            $uasStart,
            $uasEnd,
            $holidays
        );

        // 1. Tepat 14 minggu kuliah
        $kuliahWeeks = array_values(array_filter($calendar, fn ($w) => $w['type'] === WeekType::KULIAH));
        $this->assertCount(14, $kuliahWeeks, 'Harus ada tepat 14 minggu kuliah');

        // 2. Minggu 1 = 14 Sep 2026
        $minggu1 = $kuliahWeeks[0];
        $this->assertSame(1, $minggu1['meeting_no']);
        $this->assertSame('2026-09-14', $minggu1['start_date']);
        $this->assertSame('2026-09-18', $minggu1['end_date']);
        $this->assertSame('Minggu 1 (14 - 18 September 2026)', $minggu1['label']);

        // 3. Minggu 8 = 16 Nov 2026 (setelah UTS)
        $minggu8 = $kuliahWeeks[7]; // index 7 = pertemuan ke-8
        $this->assertSame(8, $minggu8['meeting_no']);
        $this->assertSame('2026-11-16', $minggu8['start_date']);
        $this->assertSame('2026-11-20', $minggu8['end_date']);
        $this->assertSame('Minggu 8 (16 - 20 November 2026)', $minggu8['label']);

        // 4. Minggu 21 Des dan 28 Des dilewati (Libur NATARU)
        $nataru1 = collect($calendar)->firstWhere('start_date', '2026-12-21');
        $this->assertNotNull($nataru1);
        $this->assertSame(WeekType::LIBUR, $nataru1['type']);
        $this->assertNull($nataru1['meeting_no']);
        $this->assertStringContainsString('NATARU', $nataru1['label']);

        $nataru2 = collect($calendar)->firstWhere('start_date', '2026-12-28');
        $this->assertNotNull($nataru2);
        $this->assertSame(WeekType::LIBUR, $nataru2['type']);
        $this->assertNull($nataru2['meeting_no']);
        $this->assertStringContainsString('28 Desember 2026 - 1 Januari 2027', $nataru2['label']);

        // 5. Minggu 13 = 4 Jan 2027 (libur 4 dan 5 Jan nonaktif)
        $minggu13 = $kuliahWeeks[12]; // index 12 = pertemuan ke-13
        $this->assertSame(13, $minggu13['meeting_no']);
        $this->assertSame('2027-01-04', $minggu13['start_date']);
        $this->assertSame('2027-01-08', $minggu13['end_date']);

        // Periksa hari di minggu 13: 4 Jan & 5 Jan harus is_holiday = true
        $daysMinggu13 = $minggu13['days'];
        $this->assertTrue($daysMinggu13[0]['is_holiday'], '4 Jan harus ditandai libur');
        $this->assertSame('2027-01-04', $daysMinggu13[0]['date']);
        $this->assertTrue($daysMinggu13[1]['is_holiday'], '5 Jan harus ditandai libur');
        $this->assertSame('2027-01-05', $daysMinggu13[1]['date']);
        // 6 Jan - 8 Jan aktif
        $this->assertFalse($daysMinggu13[2]['is_holiday'], '6 Jan harus aktif');
        $this->assertFalse($daysMinggu13[3]['is_holiday'], '7 Jan harus aktif');
        $this->assertFalse($daysMinggu13[4]['is_holiday'], '8 Jan harus aktif');

        // 6. UTS & UAS weeks
        $utsWeeks = array_values(array_filter($calendar, fn ($w) => $w['type'] === WeekType::UTS));
        $this->assertCount(2, $utsWeeks);
        $this->assertSame('2026-11-02', $utsWeeks[0]['start_date']);
        $this->assertSame('UTS (2 - 6 November 2026)', $utsWeeks[0]['label']);

        $uasWeeks = array_values(array_filter($calendar, fn ($w) => $w['type'] === WeekType::UAS));
        $this->assertCount(2, $uasWeeks);
        $this->assertSame('2027-01-18', $uasWeeks[0]['start_date']);
        $this->assertSame('UAS (18 - 22 Januari 2027)', $uasWeeks[0]['label']);
    }

    public function test_start_date_must_be_monday(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->service->buildCalendar('2026-09-15', '2027-01-29'); // Selasa
    }

    public function test_find_default_week(): void
    {
        $calendar = $this->service->buildCalendar('2026-09-14', '2027-01-29');

        // Tanggal di dalam minggu 1
        $w1 = $this->service->findDefaultWeek($calendar, Carbon::parse('2026-09-16'));
        $this->assertSame('2026-09-14', $w1['start_date']);

        // Tanggal sebelum periode dimulai -> minggu kuliah terdekat (Minggu 1)
        $before = $this->service->findDefaultWeek($calendar, Carbon::parse('2026-08-01'));
        $this->assertSame('2026-09-14', $before['start_date']);

        // Tanggal setelah periode selesai -> minggu kuliah terdekat (Minggu terakhir)
        $after = $this->service->findDefaultWeek($calendar, Carbon::parse('2027-03-01'));
        $this->assertSame(WeekType::KULIAH, $after['type']);
    }
}
