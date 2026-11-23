<?php

namespace Tests\Feature;

use App\Constants\BookingType;
use App\Enums\BookingDetailStatus;
use App\Enums\BookingStatus;
use App\Models\Booking;
use App\Models\Period;
use App\Models\Role;
use App\Models\Room;
use App\Models\User;
use App\Models\UserRole;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BookingChangeAndCancelTest extends TestCase
{
    use RefreshDatabase;

    private User $visitor;
    private Period $period;
    private Room $room;

    protected function setUp(): void
    {
        parent::setUp();

        $roleVisitor = Role::create(['name' => 'Visitor']);

        $this->period = Period::create([
            'name' => 'Ganjil 2026/2027',
            'start_date' => '2026-09-14',
            'end_date' => '2027-01-29',
            'uts_start' => '2026-11-02',
            'uts_end' => '2026-11-14',
            'uas_start' => '2027-01-18',
            'uas_end' => '2027-01-29',
            'is_active' => true,
        ]);

        $this->visitor = User::create([
            'id' => 'V000001',
            'name' => 'Budi Budiman',
            'email' => '2472026@maranatha.ac.id',
            'password' => bcrypt('password123'),
        ]);

        UserRole::create([
            'user_id' => $this->visitor->id,
            'role_id' => $roleVisitor->id,
            'period_id' => $this->period->id,
        ]);

        $this->room = Room::create([
            'id' => 'ENT1',
            'name' => 'Lab Enterprise 1',
            'capacity' => 40,
            'is_active' => true,
        ]);
    }

    public function test_can_cancel_pending_booking_immediately(): void
    {
        $this->actingAs($this->visitor);

        $booking = Booking::create([
            'user_id' => $this->visitor->id,
            'pic_name' => 'Budi Budiman',
            'pic_phone' => '08123456789',
            'purpose' => 'Kegiatan Ormawa',
            'status' => BookingStatus::PENDING_PRODI,
        ]);

        $booking->details()->create([
            'room_id' => $this->room->id,
            'booking_date' => now()->addDays(5)->toDateString(),
            'start_time' => '09:00:00',
            'end_time' => '11:00:00',
            'status' => BookingDetailStatus::PENDING,
        ]);

        // Detail page renders cancel button
        $response = $this->get(route('bookings.show', $booking));
        $response->assertOk();
        $response->assertSee('Batalkan Peminjaman');

        // Cancel the pending booking
        $cancelResp = $this->post(route('bookings.cancel', $booking), [
            'reason' => 'Salah input jadwal',
        ]);

        $cancelResp->assertRedirect();
        $this->assertEquals(BookingStatus::CANCELLED, $booking->fresh()->status);
        $this->assertEquals(BookingDetailStatus::CANCELLED, $booking->details()->first()->status);
    }

    public function test_reschedule_submission_succeeds_without_reentering_pic_info(): void
    {
        $this->actingAs($this->visitor);

        $parent = Booking::create([
            'user_id' => $this->visitor->id,
            'pic_name' => 'Budi Budiman',
            'pic_phone' => '08123456789',
            'purpose' => 'Workshop UI/UX',
            'status' => BookingStatus::APPROVED,
        ]);

        $parent->details()->create([
            'room_id' => $this->room->id,
            'booking_date' => now()->addDays(5)->toDateString(),
            'start_time' => '09:00:00',
            'end_time' => '11:00:00',
            'status' => BookingDetailStatus::APPROVED,
        ]);

        // Submit reschedule request with new slots and reason
        $newDate = now()->addDays(6)->isSunday() ? now()->addDays(7)->toDateString() : now()->addDays(6)->toDateString();
        $response = $this->post(route('bookings.store'), [
            'type' => 'change',
            'parent_booking_id' => $parent->id,
            'notes' => 'Pindah hari karena ada agenda prodi mendadak.',
            'slots' => [
                [
                    'room_id' => $this->room->id,
                    'date' => $newDate,
                    'start' => '10:00',
                    'end' => '12:00',
                ],
            ],
        ]);

        $response->assertRedirect();

        $changeBooking = Booking::where('parent_booking_id', $parent->id)->first();
        $this->assertNotNull($changeBooking);
        $this->assertEquals(BookingType::RESCHEDULE, $changeBooking->type);
        $this->assertEquals(BookingStatus::PENDING_KALAB, $changeBooking->status);
        $this->assertEquals('Budi Budiman', $changeBooking->pic_name);
        $this->assertEquals('08123456789', $changeBooking->pic_phone);
        $this->assertEquals('Workshop UI/UX', $changeBooking->purpose);
    }
}
