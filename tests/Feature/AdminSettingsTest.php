<?php

namespace Tests\Feature;

use App\Enums\ScheduleType;
use App\Enums\WeekType;
use App\Models\ActivityLog;
use App\Models\ClassSlot;
use App\Models\Course;
use App\Models\Period;
use App\Models\PeriodHoliday;
use App\Models\Role;
use App\Models\Room;
use App\Models\Schedule;
use App\Models\ScheduleSession;
use App\Models\Setting;
use App\Models\StudyProgram;
use App\Models\User;
use App\Models\UserRole;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AdminSettingsTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private User $kalab;
    private User $visitor;
    private Period $period;
    private Role $adminRole;
    private Role $kalabRole;
    private Role $visitorRole;

    protected function setUp(): void
    {
        parent::setUp();

        $this->adminRole = Role::create(['name' => 'Admin']);
        $this->kalabRole = Role::create(['name' => 'Kepala_Lab']);
        Role::create(['name' => 'Staf_Lab']);
        Role::create(['name' => 'Dosen']);
        Role::create(['name' => 'Kepala_Prodi']);
        $this->visitorRole = Role::create(['name' => 'Visitor']);

        StudyProgram::create(['id' => '72', 'name' => 'Informatika', 'is_active' => true]);
        Room::create(['id' => 'ADV1', 'name' => 'Lab ADV 1', 'capacity' => 40, 'is_active' => true]);
        Room::create(['id' => 'DB', 'name' => 'Lab Database', 'capacity' => 40, 'is_active' => true]);

        $this->period = Period::create([
            'name' => '2026/2027 Ganjil',
            'start_date' => '2026-09-14',
            'end_date' => '2027-01-29',
            'uts_start' => '2026-11-02',
            'uts_end' => '2026-11-13',
            'uas_start' => '2027-01-18',
            'uas_end' => '2027-01-29',
            'is_active' => true,
        ]);

        $this->admin = User::create([
            'id' => 'ADMIN01',
            'name' => 'Admin Test',
            'email' => 'admin@test.com',
            'password' => Hash::make('password123'),
            'is_active' => true,
            'must_change_password' => false,
        ]);
        UserRole::create([
            'user_id' => $this->admin->id,
            'role_id' => $this->adminRole->id,
            'period_id' => $this->period->id,
        ]);

        $this->kalab = User::create([
            'id' => 'KALAB01',
            'name' => 'Kalab Test',
            'email' => 'kalab@test.com',
            'password' => Hash::make('password123'),
            'is_active' => true,
            'must_change_password' => false,
        ]);
        UserRole::create([
            'user_id' => $this->kalab->id,
            'role_id' => $this->kalabRole->id,
            'period_id' => $this->period->id,
        ]);

        $this->visitor = User::create([
            'id' => 'V000001',
            'name' => 'Visitor Test',
            'email' => 'visitor@test.com',
            'password' => Hash::make('password123'),
            'is_active' => true,
            'must_change_password' => false,
        ]);
    }

    public function test_non_admin_cannot_access_settings(): void
    {
        $this->actingAs($this->visitor)
            ->get(route('admin.settings.index'))
            ->assertStatus(403);

        $this->actingAs($this->kalab)
            ->get(route('admin.settings.index'))
            ->assertStatus(403);
    }

    public function test_admin_can_access_and_update_settings(): void
    {
        $response = $this->actingAs($this->admin)
            ->get(route('admin.settings.index'));

        $response->assertStatus(200);
        $response->assertSee('Pengaturan Sistem');

        // Update settings
        $updateResponse = $this->actingAs($this->admin)
            ->post(route('admin.settings.update'), [
                'booking_block_minutes' => 30,
                'lab_open_time' => '07:00',
                'lab_close_time' => '21:00',
                'lab_open_time_saturday' => '08:00',
                'lab_close_time_saturday' => '17:00',
            ]);

        $updateResponse->assertRedirect(route('admin.settings.index', ['tab' => 'general']));

        $this->assertSame('30', Setting::get('booking_block_minutes'));
        $this->assertSame('07:00', Setting::get('lab_open_time'));
        $this->assertSame('21:00', Setting::get('lab_close_time'));
        $this->assertSame('08:00', Setting::get('lab_open_time_saturday'));
        $this->assertSame('17:00', Setting::get('lab_close_time_saturday'));
        $this->assertTrue(Setting::isBookingConfigured());

        // Pastikan tercatat di activity_log
        $this->assertDatabaseHas('activity_log', [
            'user_id' => $this->admin->id,
            'action' => 'update',
            'subject_type' => 'setting',
        ]);
    }

    public function test_class_slot_crud_and_toggle(): void
    {
        // 1. Create slot
        $this->actingAs($this->admin)
            ->post(route('admin.class-slots.store'), [
                'code' => 'T2-1',
                'start_time' => '07:00',
                'end_time' => '08:40',
                'is_active' => 1,
            ])
            ->assertRedirect(route('admin.settings.index', ['tab' => 'slots']));

        $slot = ClassSlot::find('T2-1');
        $this->assertNotNull($slot);
        $this->assertTrue($slot->is_active);

        // 2. Toggle active
        $this->actingAs($this->admin)
            ->post(route('admin.class-slots.toggle', $slot))
            ->assertSessionHas('success');

        $this->assertFalse($slot->fresh()->is_active);

        $this->assertDatabaseHas('activity_log', [
            'user_id' => $this->admin->id,
            'subject_type' => 'class_slot',
            'subject_id' => 'T2-1',
        ]);
    }

    public function test_period_validation_must_be_monday_and_exam_ordering(): void
    {
        // 1. Coba buat periode dengan start_date hari Selasa (bukan Senin) -> gagal
        $this->actingAs($this->admin)
            ->post(route('admin.periods.store'), [
                'name' => '2027/2028 Ganjil',
                'start_date' => '2027-09-14', // Selasa
                'end_date' => '2028-01-28',
            ])
            ->assertSessionHasErrors(['start_date']);

        // 2. Coba buat periode dengan UAS sebelum UTS -> gagal
        $this->actingAs($this->admin)
            ->post(route('admin.periods.store'), [
                'name' => '2027/2028 Ganjil',
                'start_date' => '2027-09-13', // Senin
                'end_date' => '2028-01-28',
                'uts_start' => '2027-12-01',
                'uts_end' => '2027-12-10',
                'uas_start' => '2027-11-01', // UAS sebelum UTS!
                'uas_end' => '2027-11-10',
            ])
            ->assertSessionHasErrors(['uas_start']);

        // 3. Buat periode yang valid (Senin, UTS sebelum UAS, hanya 1 aktif)
        $this->actingAs($this->admin)
            ->post(route('admin.periods.store'), [
                'name' => '2026/2027 Genap',
                'start_date' => '2027-02-22', // Senin
                'end_date' => '2027-07-02',
                'is_active' => 1,
            ])
            ->assertRedirect(route('admin.settings.index', ['tab' => 'periods']));

        $newPeriod = Period::where('name', '2026/2027 Genap')->first();
        $this->assertNotNull($newPeriod);
        $this->assertTrue($newPeriod->is_active);
        // Periode lama harus dinonaktifkan otomatis
        $this->assertFalse($this->period->fresh()->is_active);
    }

    public function test_period_holiday_crud_and_overlap_validation(): void
    {
        // 1. Tambah hari libur valid
        $this->actingAs($this->admin)
            ->post(route('admin.holidays.store', $this->period), [
                'period_id' => $this->period->id,
                'start_date' => '2026-12-21',
                'end_date' => '2027-01-03',
                'note' => 'NATARU',
            ])
            ->assertSessionHas('success');

        $holiday = PeriodHoliday::where('period_id', $this->period->id)->where('note', 'NATARU')->first();
        $this->assertNotNull($holiday);
        $this->assertSame('2026-12-21', $holiday->start_date->toDateString());
        $this->assertSame('2027-01-03', $holiday->end_date->toDateString());

        // 2. Coba tambah libur yang tumpang tindih (overlap) -> harus ditolak
        $this->actingAs($this->admin)
            ->post(route('admin.holidays.store', $this->period), [
                'period_id' => $this->period->id,
                'start_date' => '2026-12-25',
                'end_date' => '2026-12-28',
                'note' => 'Libur Natal Bersama',
            ])
            ->assertSessionHasErrors(['start_date']);

        // 3. Hapus libur
        $this->actingAs($this->admin)
            ->delete(route('admin.holidays.destroy', [$this->period, '2026-12-21']))
            ->assertSessionHas('success');

        $this->assertDatabaseMissing('period_holiday', [
            'period_id' => $this->period->id,
            'start_date' => '2026-12-21',
        ]);
    }

    public function test_schedule_session_regeneration_does_not_overwrite_manual_edits(): void
    {
        $course = Course::create([
            'id' => 'INF001',
            'study_program_id' => '72',
            'name' => 'Logika Informatika',
            'is_active' => true,
        ]);

        $schedule = Schedule::create([
            'period_id' => $this->period->id,
            'course_id' => $course->id,
            'class_code' => 'A',
            'type' => ScheduleType::THEORY,
            'day_of_week' => 1, // Senin
            'user_id' => $this->admin->id,
            'room_id' => 'ADV1',
            'start_time' => '07:00:00',
            'end_time' => '08:40:00',
        ]);

        // 1. Trigger generate sesi
        $this->actingAs($this->admin)
            ->post(route('admin.periods.regenerate', $this->period))
            ->assertSessionHas('success');

        $sessionW1 = ScheduleSession::where('schedule_id', $schedule->id)->where('week_no', 1)->first();
        $this->assertNotNull($sessionW1);
        $this->assertTrue($sessionW1->is_active);

        // 2. Staf melakukan edit manual pada sesi pertemuan 1 (misal dinonaktifkan dan diberi catatan)
        $sessionW1->update([
            'is_active' => false,
            'note' => 'Dosen izin sakit, kelas ditiadakan',
            'room_id' => 'DB',
        ]);

        // 3. Trigger regenerate kembali
        $this->actingAs($this->admin)
            ->post(route('admin.periods.regenerate', $this->period))
            ->assertSessionHas('success');

        // Pastikan perubahan manual TIDAK tertimpa!
        $sessionW1Refreshed = ScheduleSession::where('schedule_id', $schedule->id)->where('week_no', 1)->first();
        $this->assertFalse($sessionW1Refreshed->is_active);
        $this->assertSame('Dosen izin sakit, kelas ditiadakan', $sessionW1Refreshed->note);
        $this->assertSame('DB', $sessionW1Refreshed->room_id);
    }

    public function test_forced_initial_password_change_enforced_by_middleware(): void
    {
        $seededUser = User::create([
            'id' => 'DOSEN01',
            'name' => 'Dosen Seeder',
            'email' => 'dosen01@test.com',
            'password' => Hash::make('password123'),
            'is_active' => true,
            'must_change_password' => true,
        ]);

        // User mencoba membuka halaman home -> dialihkan ke password.change
        $this->actingAs($seededUser)
            ->get(route('home'))
            ->assertRedirect(route('password.change'));

        // User mengganti kata sandi
        $this->actingAs($seededUser)
            ->post(route('password.change.update'), [
                'current_password' => 'password123',
                'password' => 'sandiBaru2026!',
                'password_confirmation' => 'sandiBaru2026!',
            ])
            ->assertRedirect(route('home'));

        // Flag must_change_password sekarang false
        $this->assertFalse($seededUser->fresh()->must_change_password);
        $this->assertTrue(Hash::check('sandiBaru2026!', $seededUser->fresh()->password));

        // Sekarang user bisa akses home tanpa redirect
        $this->actingAs($seededUser->fresh())
            ->get(route('home'))
            ->assertStatus(200);
    }

    public function test_kalab_can_copy_roles_and_upload_staff(): void
    {
        $oldPeriod = Period::create([
            'name' => '2025/2026 Genap',
            'start_date' => '2026-02-16',
            'end_date' => '2026-06-26',
            'is_active' => false,
        ]);

        $stafRole = Role::where('name', 'Staf_Lab')->first();

        // Di periode lalu ada STF001
        $staffUser = User::create([
            'id' => 'STF001',
            'name' => 'Staf Lama',
            'email' => 'staf@test.com',
            'password' => Hash::make('secret123'),
            'is_active' => true,
        ]);
        UserRole::create([
            'user_id' => $staffUser->id,
            'role_id' => $stafRole->id,
            'period_id' => $oldPeriod->id,
        ]);

        // Kepala Lab menyalin peran dari oldPeriod ke this->period
        $this->actingAs($this->kalab)
            ->post(route('admin.period-roles.copy'), [
                'from_period_id' => $oldPeriod->id,
                'to_period_id' => $this->period->id,
            ])
            ->assertSessionHas('success');

        $this->assertDatabaseHas('user_role', [
            'user_id' => 'STF001',
            'role_id' => $stafRole->id,
            'period_id' => $this->period->id,
        ]);

        // Upload daftar staf teks
        $this->actingAs($this->kalab)
            ->post(route('admin.period-roles.upload'), [
                'period_id' => $this->period->id,
                'staff_text' => "STF002, Asisten Baru, asisten@test.com, Staf_Lab\n720999, Dosen Baru, dosen99@test.com, Dosen, 72",
            ])
            ->assertSessionHas('success');

        $this->assertDatabaseHas('user', ['id' => 'STF002', 'email' => 'asisten@test.com']);
        $this->assertDatabaseHas('user_role', [
            'user_id' => 'STF002',
            'period_id' => $this->period->id,
        ]);
        $this->assertDatabaseHas('user', ['id' => '720999', 'email' => 'dosen99@test.com']);
    }

    public function test_dashboard_week_selector_and_holiday_display(): void
    {
        PeriodHoliday::create([
            'period_id' => $this->period->id,
            'start_date' => '2026-12-21',
            'end_date' => '2027-01-03',
            'note' => 'NATARU (Natal dan Tahun Baru)',
        ]);

        $response = $this->get(route('home', [
            'period_id' => $this->period->id,
            'week' => 1,
            'day' => 1, // Senin
        ]));

        $response->assertStatus(200);
        $response->assertSee('Minggu 1 (14 - 18 September 2026)');

        // Sabtu ada di tampilan
        $satResponse = $this->get(route('home', [
            'period_id' => $this->period->id,
            'week' => 1,
            'day' => 6, // Sabtu
        ]));
        $satResponse->assertStatus(200);
        $satResponse->assertSee('Hari Sabtu:');
    }
}
