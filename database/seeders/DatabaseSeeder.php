<?php

namespace Database\Seeders;

use App\Constants\BookingDetailStatus;
use App\Constants\BookingStatus;
use App\Constants\BookingType;
use App\Constants\ScheduleType;
use App\Models\ActivityLog;
use App\Models\Booking;
use App\Models\BookingDetail;
use App\Models\Course;
use App\Models\Period;
use App\Models\Role;
use App\Models\Room;
use App\Models\Section;
use App\Models\StudyProgram;
use App\Models\User;
use App\Models\UserRole;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Roles (6 role per spec)
        $roleNames = ['Visitor', 'Staf_Lab', 'Dosen', 'Kepala_Prodi', 'Kepala_Lab', 'Admin'];
        $roles = [];
        foreach ($roleNames as $name) {
            $roles[$name] = Role::query()->firstOrCreate(['name' => $name]);
        }

        // 2. 3 Program Studi
        $studyPrograms = [
            ['code' => 'IF', 'name' => 'S1 Teknik Informatika', 'color_hex' => '#FFF3B0', 'active' => true],
            ['code' => 'SI', 'name' => 'S1 Sistem Informasi', 'color_hex' => '#FBEFD1', 'active' => true],
            ['code' => 'S2', 'name' => 'S2 Magister Ilmu Komputer', 'color_hex' => '#FFB366', 'active' => true],
        ];
        foreach ($studyPrograms as $sp) {
            StudyProgram::query()->updateOrCreate(['code' => $sp['code']], $sp);
        }
        $prodiMap = StudyProgram::query()->pluck('id', 'code');

        // Helper helper role assign
        $assignRole = function (User $user, string $roleName, ?string $startDate = null, ?string $endDate = null) use ($roles) {
            return UserRole::query()->updateOrCreate([
                'user_id' => $user->id,
                'role_id' => $roles[$roleName]->id,
                'start_date' => $startDate ?? now()->subMonths(6)->toDateString(),
            ], [
                'end_date' => $endDate,
            ]);
        };

        // 3. User Admin
        $admin = User::query()->updateOrCreate(
            ['email' => 'admin@example.com'],
            ['id' => 'ADM001', 'name' => 'Administrator Lab', 'password' => Hash::make('password')]
        );
        $assignRole($admin, 'Admin');

        // 4. Dosen yang juga Kepala_Prodi (2 baris user_role)
        $kaprodi = User::query()->updateOrCreate(
            ['email' => 'kaprodi@example.com'],
            ['id' => '720319', 'name' => 'Rossevine Artha Nathasya, S.Kom., M.T.', 'study_program_id' => $prodiMap['IF'], 'password' => Hash::make('password')]
        );
        $assignRole($kaprodi, 'Dosen');
        $assignRole($kaprodi, 'Kepala_Prodi');

        // 5. Dosen yang juga Kepala_Lab (2 baris user_role)
        $kalab = User::query()->updateOrCreate(
            ['email' => 'kalab@example.com'],
            ['id' => '720282', 'name' => 'Andreas Widjaja, S.Si., M.Sc., Ph.D.', 'study_program_id' => $prodiMap['IF'], 'password' => Hash::make('password')]
        );
        $assignRole($kalab, 'Dosen');
        $assignRole($kalab, 'Kepala_Lab');

        // 6. Staf Lab
        $staf = User::query()->updateOrCreate(
            ['email' => 'staf.lab@example.com'],
            ['id' => 'STF001', 'name' => 'Staf Laboratorium', 'password' => Hash::make('password')]
        );
        $assignRole($staf, 'Staf_Lab');

        // 7. Akun Visitor
        $visitorUser = User::query()->updateOrCreate(
            ['email' => 'visitor@example.com'],
            ['id' => 'V000001', 'name' => 'Valentino Hose', 'password' => Hash::make('password')]
        );
        $assignRole($visitorUser, 'Visitor');

        $studentUser = User::query()->updateOrCreate(
            ['email' => 'sheila@example.com'],
            ['id' => 'V000002', 'name' => 'Sheila Utomo', 'password' => Hash::make('password')]
        );
        $assignRole($studentUser, 'Visitor');

        $otherStudent = User::query()->updateOrCreate(
            ['email' => 'budi@example.com'],
            ['id' => 'V000003', 'name' => 'Budi Setiawan', 'password' => Hash::make('password')]
        );
        $assignRole($otherStudent, 'Visitor');

        // 8. 13 Ruangan
        $roomCodes = ['ADV1', 'ADV2', 'ADV3', 'ADV4', 'PROG1', 'PROG2', 'ENT1', 'ENT2', 'DB', 'MMD', 'Network', 'INT1', 'INT2'];
        foreach ($roomCodes as $code) {
            Room::query()->updateOrCreate(['code' => $code], [
                'name' => 'Laboratorium ' . $code,
                'capacity' => 40,
                'active' => true,
            ]);
        }
        $rooms = Room::query()->pluck('id', 'code');

        // 9. Load schedule_data.json jika ada
        $dataPath = database_path('seeders/schedule_data.json');
        $scheduleData = File::exists($dataPath) ? json_decode(File::get($dataPath), true) : ['lecturers' => [], 'courses' => [], 'sections' => []];

        // Seed dosen-dosen lain dari JSON ke tabel user dengan role Dosen
        foreach ($scheduleData['lecturers'] as $lec) {
            $existing = User::query()->where('id', $lec['nik'])->first();
            if (! $existing) {
                $u = User::query()->create([
                    'id' => $lec['nik'],
                    'name' => $lec['name'],
                    'email' => "dosen.{$lec['nik']}@example.com",
                    'password' => Hash::make('password'),
                    'study_program_id' => $prodiMap['IF'],
                ]);
                $assignRole($u, 'Dosen');
            }
        }

        // Seed courses
        foreach ($scheduleData['courses'] as $course) {
            Course::query()->updateOrCreate(
                ['code' => $course['code']],
                [
                    'name' => $course['name'],
                    'study_program_id' => $prodiMap[$course['study_program_code']] ?? $prodiMap['IF'],
                    'active' => true,
                ]
            );
        }
        $courses = Course::query()->pluck('id', 'code');

        // 10. Period (tanpa kolom semester, is_active)
        $ganjilPeriod = Period::query()->updateOrCreate(
            ['name' => 'Semester Ganjil 2026/2027'],
            [
                'start_date' => '2026-09-14',
                'end_date' => '2027-01-29',
                'uts_start' => '2026-11-02',
                'uts_end' => '2026-11-14',
                'uas_start' => '2027-01-18',
                'uas_end' => '2027-01-29',
                'is_active' => true,
            ]
        );

        $genapPeriod = Period::query()->updateOrCreate(
            ['name' => 'Semester Genap 2026/2027'],
            [
                'start_date' => '2027-02-15',
                'end_date' => '2027-06-12',
                'uts_start' => '2027-04-05',
                'uts_end' => '2027-04-10',
                'uas_start' => '2027-05-31',
                'uas_end' => '2027-06-05',
                'is_active' => true,
            ]
        );

        // 11. Sections (Jadwal)
        foreach ([$ganjilPeriod, $genapPeriod] as $period) {
            foreach ($scheduleData['sections'] as $sec) {
                if (! isset($courses[$sec['course_code']], $rooms[$sec['room_code']])) {
                    continue;
                }

                Section::query()->create([
                    'period_id' => $period->id,
                    'course_id' => $courses[$sec['course_code']],
                    'room_id' => $rooms[$sec['room_code']],
                    'lecturer_nik' => $sec['lecturer_nik'],
                    'class_code' => $sec['class_code'] ?? 'A',
                    'day_of_week' => $sec['day_of_week'],
                    'start_time' => $sec['start_time'],
                    'end_time' => $sec['end_time'],
                    'schedule_type' => ScheduleType::REGULAR,
                ]);
            }
        }

        // 12. Contoh Booking & BookingDetail
        // Booking 1: Disetujui
        $bookingApproved = Booking::query()->firstOrCreate(
            ['requester_name' => 'Valentino Hose'],
            [
                'user_id' => $visitorUser->id,
                'purpose' => 'Workshop UI/UX Design System',
                'type' => BookingType::NEW_BOOKING,
                'status' => BookingStatus::APPROVED,
                'submitted_at' => '2026-10-01 09:00:00',
            ]
        );

        $bookingApproved->details()->firstOrCreate(
            ['room_id' => $rooms['ADV1'], 'start_datetime' => '2026-10-07 13:00:00'],
            ['end_datetime' => '2026-10-07 15:00:00', 'status' => BookingDetailStatus::APPROVED]
        );

        // Booking 2: Pending
        $bookingPending1 = Booking::query()->firstOrCreate(
            ['requester_name' => 'Sheila Utomo'],
            [
                'user_id' => $studentUser->id,
                'purpose' => 'Pelatihan Organisasi Mahasiswa',
                'type' => BookingType::NEW_BOOKING,
                'status' => BookingStatus::PENDING_KAPRODI,
                'submitted_at' => '2026-10-02 08:30:00',
            ]
        );

        $bookingPending1->details()->firstOrCreate(
            ['room_id' => $rooms['ADV2'], 'start_datetime' => '2026-10-08 13:00:00'],
            ['end_datetime' => '2026-10-08 15:00:00', 'status' => BookingDetailStatus::PENDING]
        );

        // Booking 3: Pending antrian ke-2 pada waktu yang sama
        $bookingPending2 = Booking::query()->firstOrCreate(
            ['requester_name' => 'Budi Setiawan'],
            [
                'user_id' => $otherStudent->id,
                'purpose' => 'Sesi Belajar Bersama Coding Club',
                'type' => BookingType::NEW_BOOKING,
                'status' => BookingStatus::PENDING_KAPRODI,
                'submitted_at' => '2026-10-02 09:15:00',
            ]
        );

        $bookingPending2->details()->firstOrCreate(
            ['room_id' => $rooms['ADV2'], 'start_datetime' => '2026-10-08 13:00:00'],
            ['end_datetime' => '2026-10-08 15:00:00', 'status' => BookingDetailStatus::PENDING]
        );

        // 13. Activity Log
        ActivityLog::query()->create([
            'user_id' => $visitorUser->id,
            'action' => 'create',
            'entity_type' => 'booking',
            'entity_id' => (string) $bookingApproved->id,
            'data' => ['description' => 'Pengajuan peminjaman oleh Valentino Hose'],
            'created_at' => now(),
        ]);

        ActivityLog::query()->create([
            'user_id' => $kaprodi->id,
            'action' => 'approve',
            'entity_type' => 'booking',
            'entity_id' => (string) $bookingApproved->id,
            'data' => ['description' => 'Disetujui Kaprodi untuk pengajuan ID ' . $bookingApproved->id],
            'created_at' => now(),
        ]);

        $firstDetail = $bookingApproved->details->first();
        if ($firstDetail) {
            ActivityLog::query()->create([
                'user_id' => $kalab->id,
                'action' => 'approve',
                'entity_type' => 'booking_detail',
                'entity_id' => (string) $firstDetail->id,
                'data' => ['description' => 'Disetujui Kalab untuk ruangan ADV1'],
                'created_at' => now(),
            ]);
        }
    }
}
