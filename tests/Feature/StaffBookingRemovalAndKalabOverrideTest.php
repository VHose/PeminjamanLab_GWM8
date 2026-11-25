<?php

namespace Tests\Feature;

use App\Enums\ScheduleType;
use App\Models\Period;
use App\Models\Role;
use App\Models\Room;
use App\Models\Schedule;
use App\Models\ScheduleSession;
use App\Models\StudyProgram;
use App\Models\User;
use App\Models\UserRole;
use App\Services\ScheduleSessionGenerator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class StaffBookingRemovalAndKalabOverrideTest extends TestCase
{
    use RefreshDatabase;

    private User $kalab;
    private User $staff;
    private Period $period;
    private Room $adv1;
    private Room $adv2;
    private Schedule $schedule;

    protected function setUp(): void
    {
        parent::setUp();

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

        $roleKalab = Role::firstOrCreate(['name' => 'Kepala_Lab']);
        $roleStaff = Role::firstOrCreate(['name' => 'Staf_Lab']);

        $prodi = StudyProgram::firstOrCreate([
            'id' => '72',
            'name' => 'S1 Teknik Informatika',
            'color_hex' => '#FFF3B0',
            'is_active' => true,
        ]);

        $this->adv1 = Room::firstOrCreate([
            'id' => 'ADV1',
            'name' => 'Lab Advance 1',
            'capacity' => 40,
            'is_active' => true,
        ]);

        $this->adv2 = Room::firstOrCreate([
            'id' => 'ADV2',
            'name' => 'Lab Advance 2',
            'capacity' => 40,
            'is_active' => true,
        ]);

        $this->kalab = User::create([
            'id' => 'KALAB01',
            'name' => 'Kepala Lab',
            'email' => 'kalab@example.com',
            'password' => Hash::make('password123'),
            'status' => 'active',
            'is_active' => true,
            'email_verified_at' => now(),
        ]);
        UserRole::create(['user_id' => $this->kalab->id, 'role_id' => $roleKalab->id, 'period_id' => $this->period->id]);

        $this->staff = User::create([
            'id' => 'STAFF01',
            'name' => 'Staf Lab',
            'email' => 'staflab@example.com',
            'password' => Hash::make('password123'),
            'status' => 'active',
            'is_active' => true,
            'email_verified_at' => now(),
        ]);
        UserRole::create(['user_id' => $this->staff->id, 'role_id' => $roleStaff->id, 'period_id' => $this->period->id]);

        $course = \App\Models\Course::firstOrCreate([
            'id' => 'IF101',
            'study_program_id' => $prodi->id,
            'name' => 'Algoritma Pemrograman',
            'is_active' => true,
        ]);

        $this->schedule = Schedule::create([
            'period_id' => $this->period->id,
            'course_id' => $course->id,
            'class_code' => 'A',
            'type' => ScheduleType::THEORY,
            'day_of_week' => 1,
            'user_id' => $this->kalab->id,
            'room_id' => $this->adv1->id,
            'start_time' => '08:00:00',
            'end_time' => '10:00:00',
        ]);

        app(ScheduleSessionGenerator::class)->generateForPeriod($this->period);
    }

    public function test_staff_bookings_routes_removed_and_return_404(): void
    {
        // Rute staff-bookings.create telah dihapus
        $respCreate = $this->actingAs($this->staff)->get('/staff-bookings/create');
        $respCreate->assertNotFound();

        // Rute staff-bookings.store telah dihapus
        $respStore = $this->actingAs($this->staff)->post('/staff-bookings', []);
        $respStore->assertNotFound();
    }

    public function test_kalab_can_toggle_lecture_session(): void
    {
        $toggleUrl = route('admin.schedule-sessions.toggle', [$this->schedule->id, 1]);

        $resp = $this->actingAs($this->kalab)->post($toggleUrl);
        $resp->assertRedirect();

        $session = ScheduleSession::where('schedule_id', $this->schedule->id)->where('week_no', 1)->first();
        $this->assertFalse($session->is_active);

        // Staf Lab juga bisa toggle
        $respStaff = $this->actingAs($this->staff)->post($toggleUrl);
        $respStaff->assertRedirect();
        $session->refresh();
        $this->assertTrue($session->is_active);
    }

    public function test_kalab_can_override_session_room(): void
    {
        $updateUrl = route('admin.schedule-sessions.update', [$this->schedule->id, 1]);

        $resp = $this->actingAs($this->kalab)->patch($updateUrl, [
            'room_id' => $this->adv2->id,
        ]);
        $resp->assertRedirect();

        $session = ScheduleSession::where('schedule_id', $this->schedule->id)->where('week_no', 1)->first();
        $this->assertEquals($this->adv2->id, $session->room_id);

        // Staf Lab juga bisa override ruangan
        $respStaff = $this->actingAs($this->staff)->patch($updateUrl, [
            'room_id' => $this->adv1->id,
        ]);
        $respStaff->assertRedirect();
        $session->refresh();
        $this->assertEquals($this->adv1->id, $session->room_id);
    }

    public function test_booking_create_form_has_h_plus_2_min_date(): void
    {
        $hPlus2Str = now()->addDays(2)->toDateString();

        $resp = $this->actingAs($this->kalab)->get(route('bookings.create'));
        $resp->assertOk();
        $resp->assertSee('min="' . $hPlus2Str . '"', false);
    }

    public function test_approvals_index_has_no_mismatched_colspan_when_empty(): void
    {
        $resp = $this->actingAs($this->kalab)->get(route('approvals.index'));
        $resp->assertOk();
        // Memastikan tidak ada td colspan="7" yang memicu error TN/18 di DataTables
        $resp->assertDontSee('<td colspan="7"', false);
    }
}
