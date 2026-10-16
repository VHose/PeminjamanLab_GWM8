<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CalendarSeeder extends Seeder
{
    public function run(): void
    {
        $data = json_decode(
            file_get_contents(database_path('data/calendar_2026_ganjil.json')),
            true, 512, JSON_THROW_ON_ERROR
        );

        $period = DB::table('period')->where('name', $data['period'])->first()
            ?? throw new \RuntimeException("Periode '{$data['period']}' belum ada. Jalankan InitialDataSeeder dulu.");

        DB::table('period')->where('id', $period->id)->update([
            'uts_start' => $data['uts']['start'], 'uts_end' => $data['uts']['end'],
            'uas_start' => $data['uas']['start'], 'uas_end' => $data['uas']['end'],
        ]);

        DB::table('period_holiday')->insertOrIgnore(array_map(fn ($h) => [
            'period_id' => $period->id,
            'start_date' => $h['start_date'],
            'end_date' => $h['end_date'],
            'note' => $h['note'] ?? null,
        ], $data['holidays']));
    }
}
