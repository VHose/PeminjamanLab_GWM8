<?php

namespace Database\Seeders;

use App\Models\Booking;
use App\Models\Course;
use App\Models\Lecturer;
use App\Models\Period;
use App\Models\Role;
use App\Models\Room;
use App\Models\Section;
use App\Models\StudyProgram;
use App\Models\User;
use Illuminate\Database\Seeder;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $roles = collect(['Visitor', 'Staf_Lab', 'Kepala_Prodi', 'Kepala_Lab'])
            ->mapWithKeys(fn (string $name) => [$name => Role::query()->firstOrCreate(['name' => $name])]);

        foreach ([
            ['code' => 'IF', 'name' => 'S1 Teknik Informatika', 'color_hex' => '#FFF3B0'],
            ['code' => 'SI', 'name' => 'S1 Sistem Informasi', 'color_hex' => '#FBEFD1'],
            ['code' => 'S2', 'name' => 'S2 Magister Ilmu Komputer', 'color_hex' => '#FFB366'],
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

        foreach ([['nik' => '19800101', 'name' => 'Dr. Andi Pratama'], ['nik' => '19810202', 'name' => 'Dr. Budi Santoso'], ['nik' => '19820303', 'name' => 'Siti Rahma, M.Kom.'], ['nik' => '19830404', 'name' => 'Rina Wijaya, M.T.'], ['nik' => '19840505', 'name' => 'Deni Kurniawan, M.Kom.']] as $lecturer) {
            Lecturer::updateOrCreate(['nik' => $lecturer['nik']], $lecturer);
        }
        foreach (['ADV1', 'ADV2', 'ADV3', 'ADV4', 'PROG1', 'PROG2', 'ENT1', 'ENT2', 'DB', 'MMD', 'Network', 'INT1', 'INT2'] as $code) {
            Room::updateOrCreate(['code' => $code], ['name' => null, 'capacity' => null, 'description' => null, 'active' => true]);
        }
        $programs = StudyProgram::pluck('id', 'code');
        foreach ([['code' => 'IF101', 'name' => 'Dasar Pemrograman', 'study_program_id' => $programs['IF']], ['code' => 'IF201', 'name' => 'Basis Data', 'study_program_id' => $programs['IF']], ['code' => 'SI101', 'name' => 'Analisis Sistem', 'study_program_id' => $programs['SI']], ['code' => 'SI202', 'name' => 'Manajemen Proyek TI', 'study_program_id' => $programs['SI']], ['code' => 'S201', 'name' => 'Kecerdasan Buatan', 'study_program_id' => $programs['S2']]] as $course) {
            Course::updateOrCreate(['code' => $course['code']], $course);
        }
        $period = Period::updateOrCreate(['name' => 'Ganjil 2026/2027'], ['semester' => 'odd', 'start_date' => '2026-10-05', 'end_date' => '2027-01-30', 'uts_start' => '2026-11-16', 'uts_end' => '2026-11-21', 'uas_start' => '2027-01-18', 'uas_end' => '2027-01-23']);
        $rooms = Room::pluck('id', 'code');
        $courses = Course::pluck('id', 'code');
        foreach ([['course_id' => $courses['IF101'], 'room_id' => $rooms['ADV1'], 'lecturer_nik' => '19800101', 'day_of_week' => 1, 'start_time' => '07:00', 'end_time' => '09:00'], ['course_id' => $courses['IF201'], 'room_id' => $rooms['ADV2'], 'lecturer_nik' => '19810202', 'day_of_week' => 1, 'start_time' => '09:00', 'end_time' => '11:00'], ['course_id' => $courses['SI101'], 'room_id' => $rooms['ENT1'], 'lecturer_nik' => '19820303', 'day_of_week' => 2, 'start_time' => '08:00', 'end_time' => '10:00'], ['course_id' => $courses['SI202'], 'room_id' => $rooms['ENT2'], 'presenter_name' => 'Praktisi Industri', 'day_of_week' => 3, 'start_time' => '13:00', 'end_time' => '15:00'], ['course_id' => $courses['S201'], 'room_id' => $rooms['INT1'], 'lecturer_nik' => '19840505', 'day_of_week' => 4, 'start_time' => '10:00', 'end_time' => '12:00']] as $section) {
            Section::updateOrCreate(['period_id' => $period->id, 'course_id' => $section['course_id'], 'room_id' => $section['room_id'], 'day_of_week' => $section['day_of_week'], 'start_time' => $section['start_time']], $section + ['period_id' => $period->id]);
        }
        $visitor = $roles['Visitor'];
        $visitorUser = User::updateOrCreate(['email' => 'visitor@example.com'], ['name' => 'Valentino Hose', 'role_id' => $visitor->id, 'password' => Hash::make('password')]);
        $booking = Booking::firstOrCreate(['requester_name' => 'Valentino Hose'], ['user_id' => $visitorUser->id, 'purpose' => 'Workshop UI/UX', 'participant_count' => 25, 'type' => 'new', 'status' => 'approved', 'submitted_at' => '2026-10-01 09:00:00']);
        $booking->roomBookings()->firstOrCreate(['room_id' => $rooms['ADV1'], 'start_datetime' => '2026-10-07 13:00:00'], ['end_datetime' => '2026-10-07 15:00:00']);
        $pending = Booking::firstOrCreate(['requester_name' => 'Sheila Utomo'], ['user_id' => $visitorUser->id, 'purpose' => 'Pelatihan organisasi mahasiswa', 'participant_count' => 30, 'type' => 'new', 'status' => 'pending', 'submitted_at' => '2026-10-02 09:00:00']);
        $pending->roomBookings()->firstOrCreate(['room_id' => $rooms['ADV2'], 'start_datetime' => '2026-10-08 13:00:00'], ['end_datetime' => '2026-10-08 15:00:00']);
        $pending->approvals()->firstOrCreate(['level' => 1], ['status' => 'pending']);
    }
}
