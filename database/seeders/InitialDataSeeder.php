<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class InitialDataSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Role Lookup (tanpa timestamps)
        DB::table('role')->insertOrIgnore(array_map(
            fn ($n) => ['name' => $n],
            ['Visitor', 'Staf_Lab', 'Dosen', 'Kepala_Prodi', 'Kepala_Lab', 'Admin']
        ));

        $now = now();

        // 2. Program Studi (PK kode: 72 IF, 73 SI, 79 S2)
        DB::table('study_program')->upsert([
            ['id' => '72', 'name' => 'S1 Teknik Informatika', 'color_hex' => '#FFF3B0', 'is_active' => true, 'created_at' => $now, 'updated_at' => $now],
            ['id' => '73', 'name' => 'S1 Sistem Informasi',    'color_hex' => '#FBEFD1', 'is_active' => true, 'created_at' => $now, 'updated_at' => $now],
            ['id' => '79', 'name' => 'S2 Magister',            'color_hex' => '#FFB366', 'is_active' => true, 'created_at' => $now, 'updated_at' => $now],
        ], ['id'], ['name', 'color_hex', 'is_active', 'updated_at']);

        // 3. Ruangan Laboratorium GWM Lantai 8
        DB::table('room')->insertOrIgnore(array_map(
            fn ($c) => [
                'id' => $c,
                'name' => "Lab {$c}",
                'capacity' => 40,
                'is_active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            ['ADV1', 'ADV2', 'ADV3', 'ADV4', 'PROG1', 'PROG2', 'ENT1', 'ENT2', 'DB', 'MMD', 'Network', 'INT1', 'INT2']
        ));

        // 4. Periode Ganjil 2026/2027 (periode berikutnya dikelola langsung lewat panel admin di aplikasi)
        $periodId = DB::table('period')->where('name', '2026/2027 Ganjil')->value('id');
        if (! $periodId) {
            $periodId = DB::table('period')->insertGetId([
                'name' => '2026/2027 Ganjil',
                'start_date' => '2026-09-14', // Wajib Senin pertama kuliah
                'end_date' => '2027-01-29',   // Akhir UAS
                'uts_start' => '2026-11-02',
                'uts_end' => '2026-11-13',
                'uas_start' => '2027-01-18',
                'uas_end' => '2027-01-29',
                'is_active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        // 5. Akun Administrator Awal (hanya untuk bootstrap pertama kali)
        $adminId = env('ADMIN_ID', 'ADMIN');
        $adminEmail = env('ADMIN_EMAIL', 'admin@example.com');
        $adminPassword = env('ADMIN_PASSWORD', 'ganti-segera');

        DB::table('user')->updateOrInsert(
            ['id' => $adminId],
            [
                'study_program_id' => '72',
                'name' => 'Administrator',
                'email' => $adminEmail,
                'password' => Hash::make($adminPassword),
                'is_active' => true,
                'must_change_password' => true, // Paksa ganti password awal saat login pertama
                'created_at' => $now,
                'updated_at' => $now,
            ]
        );

        $adminRole = DB::table('role')->where('name', 'Admin')->value('id');
        if ($adminRole && $periodId) {
            DB::table('user_role')->insertOrIgnore([
                'user_id' => $adminId,
                'role_id' => $adminRole,
                'period_id' => $periodId,
            ]);
        }
    }
}
