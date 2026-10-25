<?php

namespace Database\Seeders;

use App\Models\Period;
use App\Services\PeriodWeekCalendar;
use App\Services\ScheduleSessionGenerator;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class ScheduleSeeder extends Seeder
{
    public function run(): void
    {
        $data = json_decode(
            file_get_contents(database_path('data/schedule_2026_ganjil.json')),
            true, 512, JSON_THROW_ON_ERROR
        );
        $now = now();

        DB::transaction(function () use ($data, $now) {
            $periodId = DB::table('period')->where('name', $data['period'])->value('id')
                ?? throw new \RuntimeException("Periode '{$data['period']}' belum ada. Jalankan InitialDataSeeder dulu.");
            $dosenRole = DB::table('role')->where('name', 'Dosen')->value('id');
            $prodiIds = DB::table('study_program')->pluck('id')->all();

            foreach ($data['lecturers'] as $l) {
                $prefix = substr($l['id'], 0, 2);
                DB::table('user')->insertOrIgnore([
                    'id' => $l['id'],
                    'study_program_id' => in_array($prefix, $prodiIds, true) ? $prefix : null,
                    'name' => $l['name'],
                    'email' => $l['id'] . '@belum-diisi.invalid',
                    'password' => Hash::make(Str::random(40)),
                    'is_active' => true,
                    'must_change_password' => true,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
                DB::table('user_role')->insertOrIgnore([
                    'user_id' => $l['id'],
                    'role_id' => $dosenRole,
                    'period_id' => $periodId,
                ]);
            }

            $courses = $data['courses'] ?? $data['course'] ?? [];
            foreach ($courses as $c) {
                DB::table('course')->insertOrIgnore($c + [
                    'is_active' => true,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }

            $rows = [];
            $schedules = $data['schedules'] ?? $data['schedule'] ?? [];
            foreach ($schedules as $s) {
                unset($s['meetings']);
                $rows[] = $s + [
                    'period_id' => $periodId,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }
            foreach (array_chunk($rows, 100) as $chunk) {
                DB::table('schedule')->insertOrIgnore($chunk);
            }

            // Generate sesi pertemuan menggunakan ScheduleSessionGenerator & PeriodWeekCalendar murni
            $period = Period::findOrFail($periodId);
            $generator = new ScheduleSessionGenerator(new PeriodWeekCalendar());
            $generator->generateForPeriod($period);
        });
    }
}
