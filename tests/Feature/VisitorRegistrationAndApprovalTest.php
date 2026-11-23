<?php

namespace Tests\Feature;

use App\Enums\BookingStatus;
use App\Models\Booking;
use App\Models\Period;
use App\Models\Role;
use App\Models\Room;
use App\Models\StudyProgram;
use App\Models\User;
use App\Models\UserRole;
use Illuminate\Auth\Events\Registered;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class VisitorRegistrationAndApprovalTest extends TestCase
{
    use RefreshDatabase;

    private User $kalab;
    private User $staff;
    private User $admin;
    private Period $period;
    private Room $room;

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

        $roleVisitor = Role::firstOrCreate(['name' => 'Visitor']);
        $roleKalab = Role::firstOrCreate(['name' => 'Kepala_Lab']);
        $roleStaff = Role::firstOrCreate(['name' => 'Staf_Lab']);
        $roleAdmin = Role::firstOrCreate(['name' => 'Admin']);

        StudyProgram::firstOrCreate([
            'id' => '72',
            'name' => 'S1 Teknik Informatika',
            'color_hex' => '#FFF3B0',
            'is_active' => true,
        ]);

        $this->room = Room::firstOrCreate([
            'id' => 'ADV1',
            'name' => 'Lab Advance 1',
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

        $this->admin = User::create([
            'id' => 'ADMIN01',
            'name' => 'Admin',
            'email' => 'admin@example.com',
            'password' => Hash::make('password123'),
            'status' => 'active',
            'is_active' => true,
            'email_verified_at' => now(),
        ]);
        UserRole::create(['user_id' => $this->admin->id, 'role_id' => $roleAdmin->id, 'period_id' => $this->period->id]);
    }

    public function test_visitor_registration_creates_pending_unverified_user_and_fires_registered_event(): void
    {
        Event::fake([Registered::class]);

        $response = $this->post('/register', [
            'name' => 'Budi Santoso',
            'pic_or_instansi' => 'PT Teknologi Jaya',
            'phone' => '081234567890',
            'email' => 'budi.santoso@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $response->assertRedirect(route('verification.notice'));

        Event::assertDispatched(Registered::class);

        $user = User::where('email', 'budi.santoso@example.com')->first();
        $this->assertNotNull($user);
        $this->assertEquals('Budi Santoso', $user->name);
        $this->assertEquals('PT Teknologi Jaya', $user->pic_or_instansi);
        $this->assertEquals('081234567890', $user->phone);
        $this->assertEquals('pending', $user->status);
        $this->assertFalse($user->is_active);
        $this->assertNull($user->email_verified_at);
    }

    public function test_unverified_visitor_cannot_access_booking_create_and_is_redirected_to_verify_notice(): void
    {
        $visitor = User::create([
            'id' => 'V000099',
            'name' => 'Unverified Visitor',
            'pic_or_instansi' => 'Komunitas IT',
            'email' => 'unverified@example.com',
            'password' => Hash::make('password123'),
            'status' => 'pending',
            'is_active' => false,
            'email_verified_at' => null,
        ]);

        $response = $this->actingAs($visitor)->get(route('bookings.create'));
        $response->assertRedirect(route('verification.notice'));
    }

    public function test_verified_visitor_with_pending_status_cannot_book_and_sees_approval_warning(): void
    {
        $visitor = User::create([
            'id' => 'V000100',
            'name' => 'Verified Pending Visitor',
            'pic_or_instansi' => 'PT Maju Bersama',
            'email' => 'pending.visitor@example.com',
            'password' => Hash::make('password123'),
            'status' => 'pending',
            'is_active' => false,
            'email_verified_at' => now(), // Email verified
        ]);

        $response = $this->actingAs($visitor)->get(route('bookings.create'));
        $response->assertRedirect(route('bookings.index'));
        $response->assertSessionHas('warning');

        // Attempting to post booking directly also forbidden
        $responseStore = $this->actingAs($visitor)->post(route('bookings.store'), [
            'pic_name' => 'Verified Pending Visitor',
            'pic_phone' => '08123456789',
            'purpose' => 'Seminar Teknologi',
            'slots' => [
                [
                    'room_id' => 'ADV1',
                    'date' => now()->addDays(5)->toDateString(),
                    'start' => '08:00',
                    'end' => '10:00',
                ],
            ],
        ]);

        $responseStore->assertRedirect(route('bookings.index'));
        $responseStore->assertSessionHas('warning');
    }

    public function test_kalab_and_staff_can_view_visitor_approvals_with_pic_and_status(): void
    {
        $visitor = User::create([
            'id' => 'V000101',
            'name' => 'Calon Peminjam',
            'pic_or_instansi' => 'Yayasan Pendidikan ABC',
            'email' => 'calon@example.com',
            'phone' => '081987654321',
            'password' => Hash::make('password123'),
            'status' => 'pending',
            'is_active' => false,
            'email_verified_at' => now(),
        ]);

        // Staf Lab check
        $staffResp = $this->actingAs($this->staff)->get(route('admin.visitor-approvals.index'));
        $staffResp->assertOk();
        $staffResp->assertSee('Calon Peminjam');
        $staffResp->assertSee('Yayasan Pendidikan ABC');
        $staffResp->assertSee('Verified');
        $staffResp->assertSee('Menunggu Persetujuan');

        // Kalab check
        $kalabResp = $this->actingAs($this->kalab)->get(route('admin.visitor-approvals.index'));
        $kalabResp->assertOk();
        $kalabResp->assertSee('Calon Peminjam');
        $kalabResp->assertSee('Yayasan Pendidikan ABC');
    }

    public function test_kalab_or_staff_can_approve_visitor_allowing_them_to_book(): void
    {
        $visitor = User::create([
            'id' => 'V000102',
            'name' => 'Visitor Disetujui',
            'pic_or_instansi' => 'Inkubator Bisnis',
            'email' => 'disetujui@example.com',
            'phone' => '081211112222',
            'password' => Hash::make('password123'),
            'status' => 'pending',
            'is_active' => false,
            'email_verified_at' => now(),
        ]);

        // Approve by Kalab
        $approveResp = $this->actingAs($this->kalab)->post(route('admin.visitor-approvals.approve', $visitor));
        $approveResp->assertRedirect();

        $visitor->refresh();
        $this->assertEquals('active', $visitor->status);
        $this->assertTrue($visitor->is_active);

        // Now visitor can access booking create
        $canAccessResp = $this->actingAs($visitor)->get(route('bookings.create'));
        $canAccessResp->assertOk();
    }

    public function test_kalab_or_staff_can_reject_visitor(): void
    {
        $visitor = User::create([
            'id' => 'V000103',
            'name' => 'Visitor Ditolak',
            'pic_or_instansi' => 'Organisasi Anonim',
            'email' => 'ditolak@example.com',
            'phone' => '081200000000',
            'password' => Hash::make('password123'),
            'status' => 'pending',
            'is_active' => false,
            'email_verified_at' => now(),
        ]);

        $rejectResp = $this->actingAs($this->staff)->post(route('admin.visitor-approvals.reject', $visitor), [
            'reason' => 'Identitas tidak jelas.',
        ]);
        $rejectResp->assertRedirect();

        $visitor->refresh();
        $this->assertEquals('rejected', $visitor->status);
        $this->assertFalse($visitor->is_active);

        // Visitor cannot access create
        $resp = $this->actingAs($visitor)->get(route('bookings.create'));
        $resp->assertRedirect(route('bookings.index'));
        $resp->assertSessionHas('error');
    }

    public function test_only_booking_owner_can_cancel_booking(): void
    {
        $owner = User::create([
            'id' => 'V000104',
            'name' => 'Owner Peminjaman',
            'email' => 'owner@example.com',
            'password' => Hash::make('password123'),
            'status' => 'active',
            'is_active' => true,
            'email_verified_at' => now(),
        ]);

        $otherUser = User::create([
            'id' => 'V000105',
            'name' => 'Other User',
            'email' => 'other@example.com',
            'password' => Hash::make('password123'),
            'status' => 'active',
            'is_active' => true,
            'email_verified_at' => now(),
        ]);

        $booking = Booking::create([
            'user_id' => $owner->id,
            'pic_name' => 'Owner Peminjaman',
            'pic_phone' => '08123456789',
            'purpose' => 'Uji Coba Pembatalan',
            'status' => BookingStatus::PENDING_PRODI,
        ]);

        // Other user cannot cancel (403)
        $respOther = $this->actingAs($otherUser)->post(route('bookings.cancel', $booking));
        $respOther->assertStatus(403);

        // Staff / Kalab also cannot cancel via user self-cancel route (403)
        $respStaff = $this->actingAs($this->staff)->post(route('bookings.cancel', $booking));
        $respStaff->assertStatus(403);

        // Owner CAN cancel
        $respOwner = $this->actingAs($owner)->post(route('bookings.cancel', $booking));
        $respOwner->assertRedirect();

        $booking->refresh();
        $this->assertEquals(BookingStatus::CANCELLED, $booking->status);
    }
}
