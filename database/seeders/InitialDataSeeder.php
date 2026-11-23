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

        // 2. Program Studi (PK kode: 72 IF, 73 SI, 79 S2, 00 Undefined untuk dosen luar prodi)
        DB::table('study_program')->upsert([
            ['id' => '00', 'name' => 'Undefined',              'color_hex' => '#E2E3E5', 'is_active' => true, 'created_at' => $now, 'updated_at' => $now],
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

        // 5. Akun Awal Pengguna Tiap Role (untuk login & pengujian)
        $defaultPassword = Hash::make(env('DEFAULT_PASSWORD', 'password123'));
        $roles = DB::table('role')->pluck('id', 'name');

        $initialUsers = [
            [
                'id' => env('ADMIN_ID', 'ADMIN'),
                'study_program_id' => '72',
                'name' => 'Administrator',
                'email' => env('ADMIN_EMAIL', 'admin@example.com'),
                'role' => 'Admin',
            ],
            [
                'id' => 'KALAB',
                'study_program_id' => '72',
                'name' => 'Kepala Laboratorium',
                'email' => 'kalab@example.com',
                'role' => 'Kepala_Lab',
            ],
            [
                'id' => 'KAPRODI',
                'study_program_id' => '72',
                'name' => 'Kepala Program Studi IF',
                'email' => 'kaprodi@example.com',
                'role' => 'Kepala_Prodi',
            ],
            [
                'id' => 'KAPRODI_SI',
                'study_program_id' => '73',
                'name' => 'Kepala Program Studi SI',
                'email' => 'kaprodi.si@example.com',
                'role' => 'Kepala_Prodi',
            ],
            [
                'id' => 'STAFLAB',
                'study_program_id' => '00',
                'name' => 'Staf Laboratorium',
                'email' => 'staflab@example.com',
                'role' => 'Staf_Lab',
            ],
            [
                'id' => 'DOSEN',
                'study_program_id' => '72',
                'name' => 'Dosen Informatika',
                'email' => 'dosen@example.com',
                'role' => 'Dosen',
            ],
            [
                'id' => 'VISITOR',
                'study_program_id' => '72',
                'name' => 'Valentino Hose (Mahasiswa)',
                'email' => 'visitor@example.com',
                'role' => 'Visitor',
            ],
        ];

        foreach ($initialUsers as $u) {
            DB::table('user')->updateOrInsert(
                ['id' => $u['id']],
                [
                    'study_program_id' => $u['study_program_id'],
                    'name' => $u['name'],
                    'email' => $u['email'],
                    'email_verified_at' => $now,
                    'pic_or_instansi' => $u['role'] === 'Visitor' ? 'Universitas / Mahasiswa' : null,
                    'password' => $defaultPassword,
                    'is_active' => true,
                    'status' => 'active',
                    'created_at' => $now,
                    'updated_at' => $now,
                ]
            );

            if (isset($roles[$u['role']]) && $periodId) {
                DB::table('user_role')->insertOrIgnore([
                    'user_id' => $u['id'],
                    'role_id' => $roles[$u['role']],
                    'period_id' => $periodId,
                ]);
            }
        }
    }
}
