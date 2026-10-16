<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ClassSlotSeeder extends Seeder
{
    public function run(): void
    {
        $now = now();
        $slots = [
            // Teori 2 SKS (100 menit)
            ['code' => 'T2-1', 'start_time' => '07:00:00', 'end_time' => '08:40:00', 'is_active' => true],
            ['code' => 'T2-2', 'start_time' => '09:30:00', 'end_time' => '11:10:00', 'is_active' => true],
            ['code' => 'T2-3', 'start_time' => '13:00:00', 'end_time' => '14:40:00', 'is_active' => true],

            // Teori 3 SKS (150 menit)
            ['code' => 'T3-1', 'start_time' => '07:00:00', 'end_time' => '09:30:00', 'is_active' => true],
            ['code' => 'T3-2', 'start_time' => '09:30:00', 'end_time' => '12:00:00', 'is_active' => true],
            ['code' => 'T3-3', 'start_time' => '13:00:00', 'end_time' => '15:30:00', 'is_active' => true],

            // Praktikum (120 menit)
            ['code' => 'P2-1', 'start_time' => '07:00:00', 'end_time' => '09:00:00', 'is_active' => true],
            ['code' => 'P2-2', 'start_time' => '09:30:00', 'end_time' => '11:30:00', 'is_active' => true],
            ['code' => 'P2-3', 'start_time' => '13:00:00', 'end_time' => '15:00:00', 'is_active' => true],
            ['code' => 'P2-4', 'start_time' => '15:30:00', 'end_time' => '17:30:00', 'is_active' => true],
        ];

        foreach ($slots as $slot) {
            DB::table('class_slot')->updateOrInsert(
                ['code' => $slot['code']],
                $slot + ['created_at' => $now, 'updated_at' => $now]
            );
        }
    }
}
