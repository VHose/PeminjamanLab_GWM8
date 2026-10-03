<?php

namespace Database\Seeders;

use App\Models\ActivityLog;
use App\Models\Booking;
use App\Models\BookingApproval;
use App\Models\Course;
use App\Models\Lecturer;
use App\Models\Period;
use App\Models\Role;
use App\Models\Room;
use App\Models\Section;
use App\Models\StudyProgram;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $roles = collect(['Visitor', 'Staf_Lab', 'Kepala_Prodi', 'Kepala_Lab'])
            ->mapWithKeys(fn (string $name) => [$name => Role::query()->firstOrCreate(['name' => $name])]);

        foreach ([
            ['code' => 'IF', 'name' => 'S1 Teknik Informatika', 'color_hex' => '#FFF3B0', 'active' => true],
            ['code' => 'SI', 'name' => 'S1 Sistem Informasi', 'color_hex' => '#FBEFD1', 'active' => true],
            ['code' => 'S2', 'name' => 'S2 Magister Ilmu Komputer', 'color_hex' => '#FFB366', 'active' => true],
        ] as $studyProgram) {
            StudyProgram::query()->updateOrCreate(['code' => $studyProgram['code']], $studyProgram);
        }

        foreach ([
            ['name' => 'Staf Lab', 'email' => 'staf.lab@example.com', 'role' => 'Staf_Lab'],
            ['name' => 'Kepala Prodi', 'email' => 'kaprodi@example.com', 'role' => 'Kepala_Prodi'],
            ['name' => 'Kepala Lab', 'email' => 'kalab@example.com', 'role' => 'Kepala_Lab'],
        ] as $account) {
            User::query()->updateOrCreate(
                ['email' => $account['email']],
                ['name' => $account['name'], 'role_id' => $roles[$account['role']]->id, 'password' => Hash::make('password')],
            );
        }

        $visitorUser = User::query()->updateOrCreate(
            ['email' => 'visitor@example.com'],
            ['name' => 'Valentino Hose', 'role_id' => $roles['Visitor']->id, 'password' => Hash::make('password')],
        );

        $studentUser = User::query()->updateOrCreate(
            ['email' => 'sheila@example.com'],
            ['name' => 'Sheila Utomo', 'role_id' => $roles['Visitor']->id, 'password' => Hash::make('password')],
        );

        $otherStudent = User::query()->updateOrCreate(
            ['email' => 'budi@example.com'],
            ['name' => 'Budi Setiawan', 'role_id' => $roles['Visitor']->id, 'password' => Hash::make('password')],
        );

        foreach (['ADV1', 'ADV2', 'ADV3', 'ADV4', 'PROG1', 'PROG2', 'ENT1', 'ENT2', 'DB', 'MMD', 'Network', 'INT1', 'INT2'] as $code) {
            Room::query()->updateOrCreate(['code' => $code], ['name' => null, 'capacity' => null, 'description' => null, 'active' => true]);
        }

        $dataPath = database_path('seeders/schedule_data.json');
        $scheduleData = File::exists($dataPath) ? json_decode(File::get($dataPath), true) : ['lecturers' => [], 'courses' => [], 'sections' => []];

        foreach ($scheduleData['lecturers'] as $lecturer) {
            Lecturer::query()->updateOrCreate(['nik' => $lecturer['nik']], $lecturer);
        }

        $programs = StudyProgram::query()->pluck('id', 'code');
        foreach ($scheduleData['courses'] as $course) {
            Course::query()->updateOrCreate(
                ['code' => $course['code']],
                [
                    'name' => $course['name'],
                    'study_program_id' => $programs[$course['study_program_code']] ?? $programs['IF'],
                    'active' => true,
                ],
            );
        }

        $ganjilPeriod = Period::query()->updateOrCreate(
            ['name' => 'Semester Ganjil 2026/2027'],
            [
                'semester' => 'odd',
                'start_date' => '2026-09-14',
                'end_date' => '2027-01-29',
                'uts_start' => '2026-11-02',
                'uts_end' => '2026-11-14',
                'uas_start' => '2027-01-18',
                'uas_end' => '2027-01-29',
                'active' => true,
            ],
        );

        $genapPeriod = Period::query()->updateOrCreate(
            ['name' => 'Semester Genap 2026/2027'],
            [
                'semester' => 'even',
                'start_date' => '2027-02-15',
                'end_date' => '2027-06-12',
                'uts_start' => '2027-04-05',
                'uts_end' => '2027-04-10',
                'uas_start' => '2027-05-31',
                'uas_end' => '2027-06-05',
                'active' => true,
            ],
        );

        $rooms = Room::query()->pluck('id', 'code');
        $courses = Course::query()->pluck('id', 'code');

        foreach ([$ganjilPeriod, $genapPeriod] as $period) {
            foreach ($scheduleData['sections'] as $sec) {
                if (!isset($courses[$sec['course_code']], $rooms[$sec['room_code']])) {
                    continue;
                }

                Section::query()->create([
                    'period_id' => $period->id,
                    'course_id' => $courses[$sec['course_code']],
                    'room_id' => $rooms[$sec['room_code']],
                    'lecturer_nik' => $sec['lecturer_nik'],
                    'class_code' => $sec['class_code'],
                    'day_of_week' => $sec['day_of_week'],
                    'start_time' => $sec['start_time'],
                    'end_time' => $sec['end_time'],
                ]);
            }
        }

        $bookingApproved = Booking::query()->firstOrCreate(
            ['requester_name' => 'Valentino Hose'],
            [
                'user_id' => $visitorUser->id,
                'purpose' => 'Workshop UI/UX Design System',
                'participant_count' => 30,
                'type' => 'new',
                'status' => 'approved',
                'submitted_at' => '2026-10-01 09:00:00',
            ],
        );

        $bookingApproved->roomBookings()->firstOrCreate(
            ['room_id' => $rooms['ADV1'], 'start_datetime' => '2026-10-07 13:00:00'],
            ['end_datetime' => '2026-10-07 15:00:00'],
        );

        BookingApproval::query()->updateOrCreate(
            ['booking_id' => $bookingApproved->id, 'level' => 1],
            ['approver_id' => User::where('email', 'kaprodi@example.com')->value('id'), 'status' => 'approved', 'notes' => 'Disetujui Kaprodi', 'decided_at' => '2026-10-01 10:30:00'],
        );

        BookingApproval::query()->updateOrCreate(
            ['booking_id' => $bookingApproved->id, 'level' => 2],
            ['approver_id' => User::where('email', 'kalab@example.com')->value('id'), 'status' => 'approved', 'notes' => 'Disetujui Kalab', 'decided_at' => '2026-10-01 11:00:00'],
        );

        $bookingPending1 = Booking::query()->firstOrCreate(
            ['requester_name' => 'Sheila Utomo'],
            [
                'user_id' => $studentUser->id,
                'purpose' => 'Pelatihan Organisasi Mahasiswa',
                'participant_count' => 25,
                'type' => 'new',
                'status' => 'pending',
                'submitted_at' => '2026-10-02 08:30:00',
            ],
        );

        $bookingPending1->roomBookings()->firstOrCreate(
            ['room_id' => $rooms['ADV2'], 'start_datetime' => '2026-10-08 13:00:00'],
            ['end_datetime' => '2026-10-08 15:00:00'],
        );

        BookingApproval::query()->updateOrCreate(
            ['booking_id' => $bookingPending1->id, 'level' => 1],
            ['status' => 'pending'],
        );

        $bookingPending2 = Booking::query()->firstOrCreate(
            ['requester_name' => 'Budi Setiawan'],
            [
                'user_id' => $otherStudent->id,
                'purpose' => 'Sesi Belajar Bersama Coding Club',
                'participant_count' => 20,
                'type' => 'new',
                'status' => 'pending',
                'submitted_at' => '2026-10-02 09:15:00',
            ],
        );

        $bookingPending2->roomBookings()->firstOrCreate(
            ['room_id' => $rooms['ADV2'], 'start_datetime' => '2026-10-08 13:00:00'],
            ['end_datetime' => '2026-10-08 15:00:00'],
        );

        BookingApproval::query()->updateOrCreate(
            ['booking_id' => $bookingPending2->id, 'level' => 1],
            ['status' => 'pending'],
        );

        ActivityLog::query()->create([
            'user_id' => $visitorUser->id,
            'action' => 'CREATE_BOOKING',
            'entity_type' => 'Booking',
            'entity_id' => $bookingApproved->id,
            'description' => 'Pengajuan peminjaman oleh Valentino Hose',
        ]);

        ActivityLog::query()->create([
            'user_id' => User::where('email', 'kaprodi@example.com')->value('id'),
            'action' => 'APPROVE_LEVEL_1',
            'entity_type' => 'Booking',
            'entity_id' => $bookingApproved->id,
            'description' => 'Disetujui Kaprodi untuk pengajuan ID ' . $bookingApproved->id,
        ]);

        ActivityLog::query()->create([
            'user_id' => User::where('email', 'kalab@example.com')->value('id'),
            'action' => 'APPROVE_LEVEL_2',
            'entity_type' => 'Booking',
            'entity_id' => $bookingApproved->id,
            'description' => 'Disetujui Kalab untuk pengajuan ID ' . $bookingApproved->id,
        ]);
    }
}
