<?php

namespace Tests\Feature;

use App\Enums\ScheduleType;
use App\Models\Course;
use App\Models\Period;
use App\Models\Role;
use App\Models\Room;
use App\Models\Schedule;
use App\Models\ScheduleSession;
use App\Models\StudyProgram;
use App\Models\User;
use App\Models\UserRole;
use App\Services\ScheduleSessionGenerator;
use Carbon\Carbon;
use Database\Seeders\InitialDataSeeder;
use Database\Seeders\ScheduleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class SevenRevisionsFeatureTest extends TestCase
{
    use RefreshDatabase;

    public function test_seeders_apply_prodi_list_room_capacities_and_doubled_nik_dosen_passwords(): void
    {
        $this->seed(InitialDataSeeder::class);
        $this->seed(ScheduleSeeder::class);

        // 1. Cek jumlah Prodi: 39 prodi sesuai daftar + '00' Undefined = 40
        $this->assertSame(40, StudyProgram::count());

        $expectedInactive = ['43', '56', '71'];
        foreach ($expectedInactive as $code) {
            $sp = StudyProgram::findOrFail($code);
            $this->assertFalse($sp->is_active, "Prodi {$code} harus Tidak Aktif");
        }

        $this->assertSame('Teknik Informatika', StudyProgram::findOrFail('72')->name);
        $this->assertSame('Sistem Informasi', StudyProgram::findOrFail('73')->name);
        $this->assertSame('Magister Ilmu Komputer', StudyProgram::findOrFail('79')->name);
        $this->assertSame('Magister Penuaan Kulit dan Estetika', StudyProgram::findOrFail('17')->name);
        $this->assertSame('Profesi Dokter Gigi', StudyProgram::findOrFail('95')->name);

        // 2. Cek kapasitas 13 ruangan lab
        $expectedCapacities = [
            'ADV1' => 32, 'ADV2' => 32, 'ADV3' => 32, 'ADV4' => 30,
            'PROG1' => 40, 'PROG2' => 32, 'ENT1' => 28, 'ENT2' => 32,
            'DB' => 30, 'Network' => 32, 'MMD' => 41, 'INT1' => 36, 'INT2' => 48,
        ];
        foreach ($expectedCapacities as $roomId => $cap) {
            $room = Room::findOrFail($roomId);
            $this->assertSame($cap, $room->capacity, "Kapasitas {$roomId} harus {$cap}");
            $this->assertSame("{$roomId} ({$cap})", $room->labelWithCapacity());
        }

        // 3. Cek password akun dosen = 2x NIK (contoh 720319 -> 720319720319 & 720140 -> 720140720140)
        $dosen720319 = User::findOrFail('720319');
        $this->assertTrue(Hash::check('720319720319', $dosen720319->password));

        $mewati = User::findOrFail('720140');
        $this->assertTrue(Hash::check('720140720140', $mewati->password));

        // Login menggunakan 2x NIK berhasil
        $this->post('/login', [
            'email' => $dosen720319->email,
            'password' => '720319720319',
        ])->assertRedirect(route('home'));
    }

    public function test_lecturer_can_toggle_and_move_own_schedule_only(): void
    {
        $this->seed(InitialDataSeeder::class);
        $period = Period::where('is_active', true)->firstOrFail();
        $dosenRole = Role::where('name', 'Dosen')->firstOrFail();

        $mewati = User::create([
            'id' => '720140',
            'study_program_id' => '72',
            'name' => 'Prof. Dr. Ir. Mewati Ayub, M.T.',
            'email' => '720140@maranatha.ac.id',
            'password' => Hash::make('720140720140'),
            'is_active' => true,
        ]);
        UserRole::create(['user_id' => $mewati->id, 'role_id' => $dosenRole->id, 'period_id' => $period->id]);

        $rossevine = User::create([
            'id' => '720319',
            'study_program_id' => '72',
            'name' => 'Rossevine Artha Nathasya, S.Kom., M.T.',
            'email' => '720319@maranatha.ac.id',
            'password' => Hash::make('720319720319'),
            'is_active' => true,
        ]);
        UserRole::create(['user_id' => $rossevine->id, 'role_id' => $dosenRole->id, 'period_id' => $period->id]);

        $course = Course::create([
            'id' => 'IN220',
            'study_program_id' => '72',
            'name' => 'Dasar Pemrograman',
            'is_active' => true,
        ]);

        $schedMewati = Schedule::create([
            'period_id' => $period->id,
            'course_id' => $course->id,
            'class_code' => 'A',
            'type' => ScheduleType::THEORY,
            'day_of_week' => 2,
            'user_id' => $mewati->id,
            'room_id' => 'ADV4',
            'start_time' => '09:30:00',
            'end_time' => '11:10:00',
        ]);

        $schedRossevine = Schedule::create([
            'period_id' => $period->id,
            'course_id' => $course->id,
            'class_code' => 'A1',
            'type' => ScheduleType::PRACTICUM,
            'day_of_week' => 2,
            'user_id' => $rossevine->id,
            'room_id' => 'ADV1',
            'start_time' => '13:00:00',
            'end_time' => '15:00:00',
        ]);

        app(ScheduleSessionGenerator::class)->generateForPeriod($period);

        // Mewati bisa on/off jadwalnya sendiri
        $this->actingAs($mewati)
            ->post(route('admin.schedule-sessions.toggle', [$schedMewati->id, 1]), ['note' => 'Konferensi'])
            ->assertRedirect()
            ->assertSessionHas('success');

        $sessMewati = ScheduleSession::where('schedule_id', $schedMewati->id)->where('week_no', 1)->firstOrFail();
        $this->assertFalse($sessMewati->is_active);

        // Mewati bisa memindahkan ruangan & jam jadwalnya sendiri
        $this->actingAs($mewati)
            ->patch(route('admin.schedule-sessions.update', [$schedMewati->id, 2]), [
                'session_date' => '2026-09-24', // Kamis
                'room_id' => 'MMD',
                'start_time' => '10:00',
                'end_time' => '12:00',
                'note' => 'Kuliah pengganti oleh dosen',
            ])
            ->assertRedirect()
            ->assertSessionHas('success');

        $sessMewati2 = ScheduleSession::where('schedule_id', $schedMewati->id)->where('week_no', 2)->firstOrFail();
        $this->assertSame('MMD', $sessMewati2->room_id);
        $this->assertSame('2026-09-24', $sessMewati2->session_date->toDateString());

        // Mewati TIDAK BISA mengubah jadwal milik Rossevine
        $this->actingAs($mewati)
            ->post(route('admin.schedule-sessions.toggle', [$schedRossevine->id, 1]))
            ->assertForbidden();

        $this->actingAs($mewati)
            ->patch(route('admin.schedule-sessions.update', [$schedRossevine->id, 1]), ['room_id' => 'PROG1'])
            ->assertForbidden();
    }

    public function test_schedule_columns_show_capacities_and_drag_respects_h_plus_2_and_multi_room(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-11-28 16:00:00'));

        $this->seed(InitialDataSeeder::class);
        $period = Period::where('is_active', true)->firstOrFail();

        // 1. Pada hari ini (Sabtu, 28 Nov 2026 -> week 11, day 6), semua slot < H+2 sehingga unbookable-slot
        $todayResp = $this->get(route('home', ['period_id' => $period->id, 'week' => 11, 'day' => 6]));
        $todayResp->assertOk();
        $todayResp->assertSee('ADV1 (32)');
        $todayResp->assertSee('ADV4 (30)');
        $todayResp->assertSee('PROG1 (40)');
        $todayResp->assertSee('ENT1 (28)');
        $todayResp->assertSee('MMD (41)');
        $todayResp->assertSee('INT1 (36)');
        $todayResp->assertSee('INT2 (48)');
        $todayResp->assertSee('id="btnFreezeAddRoom"', false);
        $todayResp->assertSee('+ Tambah Ruangan');
        $todayResp->assertSee('unbookable-slot', false);
        $todayResp->assertDontSee('data-bookable="1"', false);

        // 2. Pada Selasa minggu berikutnya (1 Des 2026 -> week 12, day 2, >= H+2), slot kosong dapat di-drag
        $futureResp = $this->get(route('home', ['period_id' => $period->id, 'week' => 12, 'day' => 2]));
        $futureResp->assertOk();
        $futureResp->assertSee('data-bookable="1"', false);

        // 3. Halaman bookings/create mendukung pre-fill multi-ruangan hasil freeze drag
        $admin = User::findOrFail('ADMIN');
        $multiResp = $this->actingAs($admin)->get(route('bookings.create', [
            'slots' => [
                ['room_id' => 'ADV1', 'date' => '2026-12-02', 'start' => '08:00', 'end' => '10:00'],
                ['room_id' => 'INT2', 'date' => '2026-12-02', 'start' => '10:00', 'end' => '12:00'],
            ],
        ]));
        $multiResp->assertOk();
        $multiResp->assertSee('Ruangan #1');
        $multiResp->assertSee('Ruangan #2');
        $multiResp->assertSee('selected>ADV1 (32)', false);
        $multiResp->assertSee('selected>INT2 (48)', false);

        Carbon::setTestNow();
    }

    public function test_bottom_right_stacked_alerts_and_no_native_confirm_attributes_remain(): void
    {
        $resp = $this->from('/login')->post('/login', [
            'email' => 'bukan-email',
            'password' => '',
        ]);
        $followed = $this->get('/login');
        $followed->assertOk();
        $followed->assertSee('id="appToastStack"', false);
        $followed->assertSee('flex-direction: column-reverse', false);

        // Pastikan tidak ada lagi atribut onclick/onsubmit="return confirm(...)" di seluruh blade views
        $bladeFiles = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator(resource_path('views'))
        );
        foreach ($bladeFiles as $file) {
            if ($file->isFile() && str_ends_with($file->getFilename(), '.blade.php')) {
                $contents = file_get_contents($file->getPathname());
                $this->assertDoesNotMatchRegularExpression(
                    '/return\s+confirm\s*\(/',
                    $contents,
                    "Masih ada native confirm() pada {$file->getPathname()}"
                );
            }
        }
    }

    public function test_combined_gabung_schedules_toggle_off_and_back_on_without_self_conflict(): void
    {
        $this->seed(InitialDataSeeder::class);
        $period = Period::where('is_active', true)->firstOrFail();
        $dosenRole = Role::where('name', 'Dosen')->firstOrFail();

        $sulaeman = User::create([
            'id' => '710096',
            'study_program_id' => '71',
            'name' => 'Sulaeman Santoso, S.Kom., M.T.',
            'email' => '710096@maranatha.ac.id',
            'password' => Hash::make('710096710096'),
            'is_active' => true,
        ]);
        UserRole::create(['user_id' => $sulaeman->id, 'role_id' => $dosenRole->id, 'period_id' => $period->id]);

        $courseMain = Course::create([
            'id' => 'IN253',
            'study_program_id' => '72',
            'name' => 'Grafika Komputer (Praktikum)',
            'is_active' => true,
        ]);
        $courseGabung = Course::create([
            'id' => 'IN063',
            'study_program_id' => '72',
            'name' => 'Grafika Komputer (Praktikum) (Gabung IN253 (P) kelas A)',
            'is_active' => true,
        ]);

        // Dua baris schedule gabungan di ruangan, hari, dan jam yang persis sama
        $schedMain = Schedule::create([
            'period_id' => $period->id,
            'course_id' => $courseMain->id,
            'class_code' => 'A',
            'type' => ScheduleType::PRACTICUM,
            'day_of_week' => 2, // Selasa
            'user_id' => $sulaeman->id,
            'room_id' => 'ADV2',
            'start_time' => '09:30:00',
            'end_time' => '11:30:00',
        ]);
        $schedGabung = Schedule::create([
            'period_id' => $period->id,
            'course_id' => $courseGabung->id,
            'class_code' => 'A',
            'type' => ScheduleType::PRACTICUM,
            'day_of_week' => 2, // Selasa
            'user_id' => $sulaeman->id,
            'room_id' => 'ADV2',
            'start_time' => '09:30:00',
            'end_time' => '11:30:00',
        ]);

        app(ScheduleSessionGenerator::class)->generateForPeriod($period);

        // 1. Dosen menonaktifkan pertemuan ke-10 (AJAX JSON) -> KEDUA sesi gabungan ikut nonaktif
        $offResp = $this->actingAs($sulaeman)->postJson(
            route('admin.schedule-sessions.toggle', [$schedMain->id, 10]),
            ['is_active' => false]
        );
        $offResp->assertOk()->assertJson(['success' => true, 'is_active' => false]);

        $this->assertFalse(
            ScheduleSession::where('schedule_id', $schedMain->id)->where('week_no', 10)->firstOrFail()->is_active
        );
        $this->assertFalse(
            ScheduleSession::where('schedule_id', $schedGabung->id)->where('week_no', 10)->firstOrFail()->is_active
        );

        // 2. Dosen mengaktifkan kembali pertemuan ke-10 (baik via ID utama maupun ID gabung) -> BERHASIL tanpa bentrok semu!
        $onResp = $this->actingAs($sulaeman)->postJson(
            route('admin.schedule-sessions.toggle', [$schedGabung->id, 10]),
            ['is_active' => true]
        );
        $onResp->assertOk()->assertJson(['success' => true, 'is_active' => true]);

        $this->assertTrue(
            ScheduleSession::where('schedule_id', $schedMain->id)->where('week_no', 10)->firstOrFail()->is_active
        );
        $this->assertTrue(
            ScheduleSession::where('schedule_id', $schedGabung->id)->where('week_no', 10)->firstOrFail()->is_active
        );

        // 3. Dosen memindahkan ruangan pertemuan ke-10 -> KEDUA sesi gabungan ikut pindah tanpa bentrok semu
        $this->actingAs($sulaeman)->patch(
            route('admin.schedule-sessions.update', [$schedMain->id, 10]),
            ['room_id' => 'MMD', 'note' => 'Pindah bersama kelas gabungan']
        )->assertRedirect()->assertSessionHas('success');

        $this->assertSame('MMD', ScheduleSession::where('schedule_id', $schedMain->id)->where('week_no', 10)->firstOrFail()->room_id);
        $this->assertSame('MMD', ScheduleSession::where('schedule_id', $schedGabung->id)->where('week_no', 10)->firstOrFail()->room_id);
    }

    public function test_welcome_schedule_table_uses_frontend_datatable_without_reload_submit_button(): void
    {
        $this->seed(InitialDataSeeder::class);
        $this->seed(ScheduleSeeder::class);

        $resp = $this->get('/');
        $resp->assertOk();
        $resp->assertSee('id="scheduleGridTable"', false);
        $resp->assertSee('id="schedulePeriodFilter"', false);
        $resp->assertSee('id="scheduleWeekFilter"', false);
        $resp->assertSee('id="scheduleDayFilter"', false);
        $resp->assertSee('id="scheduleRoomFilter"', false);
        $resp->assertSee("$('#scheduleGridTable').DataTable", false);
        $resp->assertDontSee('schedule-grid.js', false);
        $resp->assertDontSee('>Tampilkan</button>', false);

        // Pastikan data JSON front-end valid & tidak membocorkan NIK dosen (contoh 720140)
        preg_match('/JSON\.parse\(atob\("([^"]+)"\)\)/', $resp->getContent(), $matches);
        $this->assertNotEmpty($matches[1] ?? '');
        $decodedJson = base64_decode($matches[1], true);
        $this->assertNotFalse($decodedJson);
        $this->assertStringNotContainsString('720140', $decodedJson);

        $data = json_decode($decodedJson, true);
        $this->assertIsArray($data);
        $this->assertArrayHasKey('periods', $data);
        $this->assertArrayHasKey('sessions', $data);
        $this->assertArrayHasKey('rooms', $data);
        $this->assertNotEmpty($data['sessions']);
    }
}
