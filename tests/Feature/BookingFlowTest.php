<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\Course;
use App\Models\Lecturer;
use App\Models\Period;
use App\Models\Role;
use App\Models\Room;
use App\Models\Section;
use App\Models\StudyProgram;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class BookingFlowTest extends TestCase
{
    use RefreshDatabase;

    private User $visitor;
    private User $kaprodi;
    private User $kalab;
    private User $staff;
    private Room $adv1;
    private Room $adv2;
    private Period $period;

    protected function setUp(): void
    {
        parent::setUp();

        $roleVisitor = Role::create(['name' => 'Visitor']);
        $roleStaff = Role::create(['name' => 'Staf_Lab']);
        $roleKaprodi = Role::create(['name' => 'Kepala_Prodi']);
        $roleKalab = Role::create(['name' => 'Kepala_Lab']);

        $this->visitor = User::create([
            'name' => 'Valentino Hose',
            'email' => 'visitor@test.com',
            'password' => Hash::make('password'),
            'role_id' => $roleVisitor->id,
        ]);

        $this->staff = User::create([
            'name' => 'Staf Lab',
            'email' => 'staff@test.com',
            'password' => Hash::make('password'),
            'role_id' => $roleStaff->id,
        ]);

        $this->kaprodi = User::create([
            'name' => 'Kepala Prodi',
            'email' => 'kaprodi@test.com',
            'password' => Hash::make('password'),
            'role_id' => $roleKaprodi->id,
        ]);

        $this->kalab = User::create([
            'name' => 'Kepala Lab',
            'email' => 'kalab@test.com',
            'password' => Hash::make('password'),
            'role_id' => $roleKalab->id,
        ]);

        $this->adv1 = Room::create(['code' => 'ADV1', 'name' => 'Advanced 1', 'capacity' => 30, 'active' => true]);
        $this->adv2 = Room::create(['code' => 'ADV2', 'name' => 'Advanced 2', 'capacity' => 30, 'active' => true]);

        $this->period = Period::create([
            'name' => 'Ganjil 2026/2027',
            'semester' => 'odd',
            'start_date' => '2026-09-14',
            'end_date' => '2027-01-29',
            'uts_start' => '2026-11-02',
            'uts_end' => '2026-11-14',
            'uas_start' => '2027-01-18',
            'uas_end' => '2027-01-29',
        ]);
    }

    public function test_visitor_can_submit_booking_and_two_step_approval_works(): void
    {
        $this->actingAs($this->visitor);

        $response = $this->post(route('bookings.store'), [
            'type' => 'new',
            'requester_name' => 'Valentino Hose',
            'purpose' => 'Seminar Kecerdasan Buatan',
            'participant_count' => 25,
            'slots' => [
                [
                    'room_id' => $this->adv1->id,
                    'date' => '2026-10-20',
                    'start' => '09:00',
                    'end' => '11:00',
                ],
                [
                    'room_id' => $this->adv2->id,
                    'date' => '2026-10-20',
                    'start' => '13:00',
                    'end' => '15:00',
                ],
            ],
        ]);

        $response->assertRedirect(route('bookings.index'));

        $booking = Booking::where('purpose', 'Seminar Kecerdasan Buatan')->first();
        $this->assertNotNull($booking);
        $this->assertEquals('pending', $booking->status);
        $this->assertCount(2, $booking->roomBookings);

        $approvalL1 = $booking->approvals()->where('level', 1)->first();
        $this->assertNotNull($approvalL1);
        $this->assertEquals('pending', $approvalL1->status);

        $this->actingAs($this->kaprodi);
        $responseKaprodi = $this->post(route('approvals.decide', $booking), [
            'status' => 'approved',
        ]);
        $responseKaprodi->assertSessionHas('success');

        $approvalL2 = $booking->fresh()->approvals()->where('level', 2)->first();
        $this->assertNotNull($approvalL2);
        $this->assertEquals('pending', $approvalL2->status);
        $this->assertEquals('pending', $booking->fresh()->status);

        $this->actingAs($this->kalab);
        $responseKalab = $this->post(route('approvals.decide', $booking), [
            'status' => 'approved',
        ]);
        $responseKalab->assertSessionHas('success');

        $this->assertEquals('approved', $booking->fresh()->status);
    }

    public function test_rejection_requires_notes_and_sets_status_to_rejected(): void
    {
        $this->actingAs($this->visitor);

        $this->post(route('bookings.store'), [
            'type' => 'new',
            'requester_name' => 'Valentino Hose',
            'purpose' => 'Lomba Game Development',
            'participant_count' => 15,
            'slots' => [
                [
                    'room_id' => $this->adv1->id,
                    'date' => '2026-10-25',
                    'start' => '10:00',
                    'end' => '12:00',
                ],
            ],
        ]);

        $booking = Booking::where('purpose', 'Lomba Game Development')->first();

        $this->actingAs($this->kaprodi);

        $responseEmptyNotes = $this->post(route('approvals.decide', $booking), [
            'status' => 'rejected',
            'notes' => '',
        ]);
        $responseEmptyNotes->assertSessionHasErrors(['notes']);

        $responseReject = $this->post(route('approvals.decide', $booking), [
            'status' => 'rejected',
            'notes' => 'Bentrok dengan agenda universitas.',
        ]);
        $responseReject->assertSessionHas('success');

        $booking->refresh();
        $this->assertEquals('rejected', $booking->status);
        $this->assertEquals('Bentrok dengan agenda universitas.', $booking->notes);
    }

    public function test_staff_booking_is_immediately_approved(): void
    {
        $this->actingAs($this->staff);

        $response = $this->post(route('staff-bookings.store'), [
            'requester_name' => 'Dr. Andi Pratama',
            'purpose' => 'Praktikum Pengganti',
            'participant_count' => 30,
            'slots' => [
                [
                    'room_id' => $this->adv1->id,
                    'date' => '2026-10-22',
                    'start' => '14:00',
                    'end' => '16:00',
                ],
            ],
        ]);

        $response->assertRedirect(route('bookings.index'));

        $booking = Booking::where('purpose', 'Praktikum Pengganti')->first();
        $this->assertNotNull($booking);
        $this->assertEquals('approved', $booking->status);
        $this->assertCount(0, $booking->approvals);
    }

    public function test_change_booking_approval_goes_directly_to_kalab(): void
    {
        $this->actingAs($this->visitor);

        $this->post(route('bookings.store'), [
            'type' => 'new',
            'requester_name' => 'Valentino Hose',
            'purpose' => 'Workshop UI/UX',
            'participant_count' => 20,
            'slots' => [
                [
                    'room_id' => $this->adv1->id,
                    'date' => '2026-10-21',
                    'start' => '08:00',
                    'end' => '10:00',
                ],
            ],
        ]);

        $originalBooking = Booking::where('purpose', 'Workshop UI/UX')->first();
        $originalBooking->update(['status' => 'approved']);

        $responseChange = $this->post(route('bookings.store'), [
            'type' => 'change',
            'parent_booking_id' => $originalBooking->id,
            'requester_name' => 'Valentino Hose',
            'purpose' => 'Workshop UI/UX',
            'participant_count' => 20,
            'notes' => 'Pindah ke ruang ADV2 karena peserta bertambah.',
            'slots' => [
                [
                    'room_id' => $this->adv2->id,
                    'date' => '2026-10-21',
                    'start' => '08:00',
                    'end' => '10:00',
                ],
            ],
        ]);

        $responseChange->assertRedirect(route('bookings.index'));

        $changeBooking = Booking::where('parent_booking_id', $originalBooking->id)->first();
        $this->assertNotNull($changeBooking);
        $this->assertEquals('change', $changeBooking->type);

        $approvals = $changeBooking->approvals;
        $this->assertCount(1, $approvals);
        $this->assertEquals(2, $approvals->first()->level);

        $this->actingAs($this->kalab);
        $this->post(route('approvals.decide', $changeBooking), [
            'status' => 'approved',
        ]);

        $this->assertEquals('cancelled', $originalBooking->fresh()->status);
        $this->assertEquals('approved', $changeBooking->fresh()->status);
    }

    public function test_intra_request_collisions_are_rejected(): void
    {
        $this->actingAs($this->visitor);

        $response = $this->post(route('bookings.store'), [
            'type' => 'new',
            'requester_name' => 'Valentino Hose',
            'purpose' => 'Kegiatan Bentrok Internal',
            'participant_count' => 10,
            'slots' => [
                [
                    'room_id' => $this->adv1->id,
                    'date' => '2026-10-28',
                    'start' => '08:00',
                    'end' => '10:00',
                ],
                [
                    'room_id' => $this->adv1->id,
                    'date' => '2026-10-28',
                    'start' => '09:00',
                    'end' => '11:00',
                ],
            ],
        ]);

        $response->assertSessionHasErrors(['slots']);
    }

    public function test_booking_during_exam_period_without_schedule_is_rejected(): void
    {
        $this->actingAs($this->visitor);

        $response = $this->post(route('bookings.store'), [
            'type' => 'new',
            'requester_name' => 'Valentino Hose',
            'purpose' => 'Kegiatan Masa UTS',
            'participant_count' => 10,
            'slots' => [
                [
                    'room_id' => $this->adv1->id,
                    'date' => '2026-11-04',
                    'start' => '08:00',
                    'end' => '10:00',
                ],
            ],
        ]);

        $response->assertSessionHasErrors(['slots']);
    }

    public function test_auto_reject_command_and_queue_filtering(): void
    {
        $booking = Booking::create([
            'user_id' => $this->visitor->id,
            'requester_name' => 'Valentino Hose',
            'purpose' => 'Kegiatan H-1',
            'participant_count' => 10,
            'type' => 'new',
            'status' => 'pending',
            'submitted_at' => now(),
        ]);

        $booking->roomBookings()->create([
            'room_id' => $this->adv1->id,
            'start_datetime' => now()->addDay()->setTime(10, 0),
            'end_datetime' => now()->addDay()->setTime(12, 0),
        ]);

        $approval = $booking->approvals()->create([
            'level' => 1,
            'status' => 'pending',
        ]);

        Artisan::call('bookings:auto-reject');

        $booking->refresh();
        $this->assertEquals('rejected', $booking->status);
        $this->actingAs($this->kaprodi);
        $response = $this->get(route('approvals.index'));
        $response->assertSee('Tidak ada pengajuan yang sedang menunggu persetujuan Anda saat ini.');
        $response->assertSee('Ditolak');
    }

    public function test_visitor_can_cancel_approved_booking_before_h2(): void
    {
        $booking = Booking::create([
            'user_id' => $this->visitor->id,
            'requester_name' => 'Valentino Hose',
            'purpose' => 'Kegiatan Dibatalkan',
            'participant_count' => 10,
            'type' => 'new',
            'status' => 'approved',
            'submitted_at' => now(),
        ]);

        $booking->roomBookings()->create([
            'room_id' => $this->adv1->id,
            'start_datetime' => now()->addDays(5)->setTime(10, 0),
            'end_datetime' => now()->addDays(5)->setTime(12, 0),
        ]);

        $this->actingAs($this->visitor);
        $response = $this->post(route('bookings.cancel', $booking));
        $response->assertSessionHas('success');

        $this->assertEquals('cancelled', $booking->fresh()->status);
    }

    public function test_landing_page_renders_cleanly_with_filters(): void
    {
        $response = $this->get('/?week=1&day=1');
        $response->assertOk();
        $response->assertSee('Denah Laboratorium Komputer GWM Lantai 8');
        $response->assertSee('Minggu 1 14-19 September 2026');
        $response->assertSee('Ganjil 2026/2027');
        $response->assertSee('Semua Lab (13 Ruangan)');
        $response->assertDontSee('Minggu yang dipilih berada pada rentang UTS/UAS');

        $utsResponse = $this->get('/?week=8&day=1');
        $utsResponse->assertOk();
        $utsResponse->assertSee('Minggu yang dipilih berada pada rentang UTS/UAS dan jadwal ujian belum tersedia');

        $uasResponse = $this->get('/?week=19&day=1');
        $uasResponse->assertOk();
        $uasResponse->assertSee('Minggu yang dipilih berada pada rentang UTS/UAS dan jadwal ujian belum tersedia');
    }
}
