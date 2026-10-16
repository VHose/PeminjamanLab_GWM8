<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class SettingSeeder extends Seeder
{
    public function run(): void
    {
        $now = now();

        // booking_block_minutes = 30
        DB::table('setting')->updateOrInsert(
            ['key' => 'booking_block_minutes'],
            ['value' => '30', 'updated_at' => $now]
        );

        // Empat parameter lainnya BELUM ditentukan: jangan beri default diam-diam.
        // Baris disimpan dengan value NULL agar admin wajib mengisinya lewat form Pengaturan.
        $keys = [
            'lab_open_time',
            'lab_close_time',
            'booking_max_days_ahead',
            'booking_max_duration_hours',
            'lab_open_time_saturday',
            'lab_close_time_saturday',
        ];

        foreach ($keys as $key) {
            if (! DB::table('setting')->where('key', $key)->exists()) {
                DB::table('setting')->insert([
                    'key' => $key,
                    'value' => null,
                    'updated_at' => $now,
                ]);
            }
        }
    }
}
