<?php

namespace Tests\Feature;

use App\Enums\BookingDetailStatus;
use App\Enums\BookingStatus;
use App\Models\Booking;
use App\Models\BookingAttachment;
use App\Models\BookingDetail;
use App\Models\Period;
use App\Models\Role;
use App\Models\Room;
use App\Models\StudyProgram;
use App\Models\User;
use App\Models\UserRole;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class BookingAttachmentTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private User $otherUser;
    private User $staff;
    private Room $room;
    private Period $period;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('local');

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

        $roleVisitor = Role::create(['name' => 'Visitor']);
        $roleStaff = Role::create(['name' => 'Staf_Lab']);
        $roleAdmin = Role::create(['name' => 'Admin']);

        StudyProgram::create([
            'id' => '72',
            'name' => 'S1 Teknik Informatika',
            'color_hex' => '#FFF3B0',
            'is_active' => true,
        ]);

        $this->room = Room::create([
            'id' => 'ADV1',
            'name' => 'Lab Advance 1',
            'capacity' => 40,
            'is_active' => true,
        ]);

        $this->user = User::create([
            'id' => 'V000001',
            'name' => 'Valentino Hose',
            'email' => 'valentino@example.com',
            'password' => Hash::make('password'),
            'is_active' => true,
            'must_change_password' => false,
        ]);
        UserRole::create([
            'user_id' => $this->user->id,
            'role_id' => $roleVisitor->id,
            'period_id' => $this->period->id,
        ]);

        $this->otherUser = User::create([
            'id' => 'V000002',
            'name' => 'Other Student',
            'email' => 'other@example.com',
            'password' => Hash::make('password'),
            'is_active' => true,
            'must_change_password' => false,
        ]);
        UserRole::create([
            'user_id' => $this->otherUser->id,
            'role_id' => $roleVisitor->id,
            'period_id' => $this->period->id,
        ]);

        $this->staff = User::create([
            'id' => 'STF001',
            'name' => 'Staf Laboratorium',
            'email' => 'staf@example.com',
            'password' => Hash::make('password'),
            'is_active' => true,
            'must_change_password' => false,
        ]);
        UserRole::create([
            'user_id' => $this->staff->id,
            'role_id' => $roleStaff->id,
            'period_id' => $this->period->id,
        ]);
    }

    public function test_booking_can_be_submitted_without_attachments(): void
    {
        // Cari tanggal hari kerja minimal H+2 (bukan hari Minggu)
        $targetDate = now()->addDays(3);
        if ($targetDate->isSunday()) {
            $targetDate->addDay();
        }

        $response = $this->actingAs($this->user)->post(route('bookings.store'), [
            'pic_name' => 'Valentino Hose',
            'pic_phone' => '08123456789',
            'purpose' => 'Seminar Kecerdasan Buatan',
            'slots' => [
                [
                    'room_id' => 'ADV1',
                    'date' => $targetDate->toDateString(),
                    'start' => '09:00',
                    'end' => '11:00',
                ],
            ],
            // attachments tidak diisi sama sekali (opsional)
        ]);

        $response->assertSessionHasNoErrors();
        $this->assertDatabaseHas('booking', [
            'user_id' => $this->user->id,
            'pic_name' => 'Valentino Hose',
            'purpose' => 'Seminar Kecerdasan Buatan',
        ]);

        $booking = Booking::where('user_id', $this->user->id)->first();
        $this->assertNotNull($booking);
        $this->assertCount(0, $booking->attachments);
    }

    public function test_booking_can_be_submitted_with_optional_attachments(): void
    {
        $targetDate = now()->addDays(3);
        if ($targetDate->isSunday()) {
            $targetDate->addDay();
        }

        $poster = UploadedFile::fake()->image('poster_seminar.png', 800, 600)->size(1200);
        $letter = UploadedFile::fake()->create('surat_permohonan.pdf', 800, 'application/pdf');

        $response = $this->actingAs($this->user)->post(route('bookings.store'), [
            'pic_name' => 'Valentino Hose',
            'pic_phone' => '08123456789',
            'purpose' => 'Workshop UI/UX Design',
            'slots' => [
                [
                    'room_id' => 'ADV1',
                    'date' => $targetDate->toDateString(),
                    'start' => '13:00',
                    'end' => '15:00',
                ],
            ],
            'attachments' => [$poster, $letter],
        ]);

        $response->assertSessionHasNoErrors();

        $booking = Booking::where('user_id', $this->user->id)->where('purpose', 'Workshop UI/UX Design')->first();
        $this->assertNotNull($booking);
        $this->assertCount(2, $booking->attachments);

        $attachment1 = $booking->attachments->firstWhere('original_name', 'poster_seminar.png');
        $this->assertNotNull($attachment1);
        $this->assertTrue($attachment1->is_image);
        Storage::disk('local')->assertExists($attachment1->path);

        $attachment2 = $booking->attachments->firstWhere('original_name', 'surat_permohonan.pdf');
        $this->assertNotNull($attachment2);
        $this->assertTrue($attachment2->is_pdf);
        Storage::disk('local')->assertExists($attachment2->path);
    }

    public function test_only_authorized_user_can_access_booking_attachments(): void
    {
        $targetDate = now()->addDays(4);
        if ($targetDate->isSunday()) {
            $targetDate->addDay();
        }

        $file = UploadedFile::fake()->create('dokumen_rahasia.pdf', 300, 'application/pdf');

        $this->actingAs($this->user)->post(route('bookings.store'), [
            'pic_name' => 'Valentino Hose',
            'pic_phone' => '08123456789',
            'purpose' => 'Kegiatan Khusus Mahasiswa',
            'slots' => [
                [
                    'room_id' => 'ADV1',
                    'date' => $targetDate->toDateString(),
                    'start' => '08:00',
                    'end' => '10:00',
                ],
            ],
            'attachments' => [$file],
        ]);

        $booking = Booking::where('user_id', $this->user->id)->where('purpose', 'Kegiatan Khusus Mahasiswa')->first();
        $attachment = $booking->attachments->first();

        // 1. Pemohon sendiri dapat melihat/mengunduh
        $responseOwner = $this->actingAs($this->user)->get(route('bookings.attachments.show', [$booking, $attachment]));
        $responseOwner->assertOk();

        // 2. Download parameter
        $responseDownload = $this->actingAs($this->user)->get(route('bookings.attachments.show', [$booking, $attachment, 'download' => 1]));
        $responseDownload->assertOk();

        // 3. User internal (Staf Lab) dapat melihat/mengunduh
        $responseStaff = $this->actingAs($this->staff)->get(route('bookings.attachments.show', [$booking, $attachment]));
        $responseStaff->assertOk();

        // 4. User lain (mahasiswa/visitor lain) ditolak 403 Forbidden
        $responseUnauthorized = $this->actingAs($this->otherUser)->get(route('bookings.attachments.show', [$booking, $attachment]));
        $responseUnauthorized->assertForbidden();
    }
}
