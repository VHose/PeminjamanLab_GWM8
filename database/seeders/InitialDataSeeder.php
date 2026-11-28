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

        // 2. Program Studi (PK kode prodi + '00' Undefined untuk umum/luar prodi)
        $studyPrograms = [
            ['id' => '00', 'name' => 'Undefined',                           'color_hex' => '#E2E3E5', 'is_active' => true],
            ['id' => '10', 'name' => 'Kedokteran',                          'color_hex' => '#FADBD8', 'is_active' => true],
            ['id' => '14', 'name' => 'Bioteknologi',                        'color_hex' => '#D5F5E3', 'is_active' => true],
            ['id' => '15', 'name' => 'Profesi Dokter',                      'color_hex' => '#F5B7B1', 'is_active' => true],
            ['id' => '17', 'name' => 'Magister Penuaan Kulit dan Estetika', 'color_hex' => '#FDEDEC', 'is_active' => true],
            ['id' => '18', 'name' => 'Obstetri dan Ginekologi',             'color_hex' => '#F9EBEA', 'is_active' => true],
            ['id' => '21', 'name' => 'Teknik Sipil',                        'color_hex' => '#D6EAF8', 'is_active' => true],
            ['id' => '22', 'name' => 'Teknik Elektro',                      'color_hex' => '#AED6F1', 'is_active' => true],
            ['id' => '23', 'name' => 'Teknik Industri',                     'color_hex' => '#EBF5FB', 'is_active' => true],
            ['id' => '27', 'name' => 'Sistem Komputer',                     'color_hex' => '#D4E6F1', 'is_active' => true],
            ['id' => '28', 'name' => 'Magister Teknik Sipil',               'color_hex' => '#A9CCE3', 'is_active' => true],
            ['id' => '30', 'name' => 'Psikologi',                           'color_hex' => '#E8DAEF', 'is_active' => true],
            ['id' => '32', 'name' => 'Magister Psikologi Profesi',          'color_hex' => '#D2B4DE', 'is_active' => true],
            ['id' => '34', 'name' => 'Magister Science Psikologi',          'color_hex' => '#F4ECF7', 'is_active' => true],
            ['id' => '35', 'name' => 'Pendidikan Profesi Psikolog',         'color_hex' => '#EBDEF0', 'is_active' => true],
            ['id' => '41', 'name' => 'Sastra Inggris',                      'color_hex' => '#FCF3CF', 'is_active' => true],
            ['id' => '42', 'name' => 'Sastra Jepang',                       'color_hex' => '#F9E79F', 'is_active' => true],
            ['id' => '43', 'name' => 'D-III Bahasa Inggris',                'color_hex' => '#FEF9E7', 'is_active' => false],
            ['id' => '44', 'name' => 'D-III Bahasa Mandarin',               'color_hex' => '#FDEBD0', 'is_active' => true],
            ['id' => '46', 'name' => 'Sastra China',                        'color_hex' => '#FAD7A0', 'is_active' => true],
            ['id' => '51', 'name' => 'Akuntansi',                           'color_hex' => '#D1F2EB', 'is_active' => true],
            ['id' => '52', 'name' => 'Manajemen',                           'color_hex' => '#A3E4D7', 'is_active' => true],
            ['id' => '53', 'name' => 'Magister Manajemen',                  'color_hex' => '#E8F8F5', 'is_active' => true],
            ['id' => '56', 'name' => 'Pendidikan Profesi Akuntansi',        'color_hex' => '#E8F6F3', 'is_active' => false],
            ['id' => '57', 'name' => 'Magister Akuntansi',                  'color_hex' => '#A2D9CE', 'is_active' => true],
            ['id' => '59', 'name' => 'S3 Ilmu Manajemen',                   'color_hex' => '#D0ECE7', 'is_active' => true],
            ['id' => '61', 'name' => 'D-III Seni Rupa dan Disain',          'color_hex' => '#FCE4EC', 'is_active' => true],
            ['id' => '62', 'name' => 'Seni Rupa Murni',                     'color_hex' => '#F8BBD0', 'is_active' => true],
            ['id' => '63', 'name' => 'Desain Interior',                     'color_hex' => '#F3E5F5', 'is_active' => true],
            ['id' => '64', 'name' => 'Desain Komunikasi Visual',            'color_hex' => '#E1BEE7', 'is_active' => true],
            ['id' => '65', 'name' => 'Arsitektur',                          'color_hex' => '#EDE7F6', 'is_active' => true],
            ['id' => '66', 'name' => 'Desain Mode',                         'color_hex' => '#F48FB1', 'is_active' => true],
            ['id' => '71', 'name' => 'D-III Teknik Informatika',            'color_hex' => '#E5E7E9', 'is_active' => false],
            ['id' => '72', 'name' => 'Teknik Informatika',                  'color_hex' => '#FFF3B0', 'is_active' => true],
            ['id' => '73', 'name' => 'Sistem Informasi',                    'color_hex' => '#FBEFD1', 'is_active' => true],
            ['id' => '79', 'name' => 'Magister Ilmu Komputer',              'color_hex' => '#FFB366', 'is_active' => true],
            ['id' => '86', 'name' => 'Hukum (S-2)',                         'color_hex' => '#D7CCC8', 'is_active' => true],
            ['id' => '87', 'name' => 'Hukum (S-1)',                         'color_hex' => '#EFEBE9', 'is_active' => true],
            ['id' => '90', 'name' => 'Pendidikan Dokter Gigi',              'color_hex' => '#B2EBF2', 'is_active' => true],
            ['id' => '95', 'name' => 'Profesi Dokter Gigi',                 'color_hex' => '#E0F7FA', 'is_active' => true],
        ];

        DB::table('study_program')->upsert(
            array_map(fn ($sp) => $sp + ['created_at' => $now, 'updated_at' => $now], $studyPrograms),
            ['id'],
            ['name', 'color_hex', 'is_active', 'updated_at']
        );

        // 3. Ruangan Laboratorium GWM Lantai 8 beserta kapasitas masing-masing
        $roomCapacities = [
            'ADV1'    => 32,
            'ADV2'    => 32,
            'ADV3'    => 32,
            'ADV4'    => 30,
            'PROG1'   => 40,
            'PROG2'   => 32,
            'ENT1'    => 28,
            'ENT2'    => 32,
            'DB'      => 30,
            'Network' => 32,
            'MMD'     => 41,
            'INT1'    => 36,
            'INT2'    => 48,
        ];

        DB::table('room')->upsert(
            array_map(
                fn ($code, $cap) => [
                    'id' => $code,
                    'name' => "Lab {$code}",
                    'capacity' => $cap,
                    'is_active' => true,
                    'created_at' => $now,
                    'updated_at' => $now,
                ],
                array_keys($roomCapacities),
                array_values($roomCapacities)
            ),
            ['id'],
            ['name', 'capacity', 'is_active', 'updated_at']
        );

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
                    'password' => $u['role'] === 'Dosen' ? Hash::make($u['id'] . $u['id']) : $defaultPassword,
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
