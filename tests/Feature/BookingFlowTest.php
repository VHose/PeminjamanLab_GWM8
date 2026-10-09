<?php

namespace Tests\Feature;

use App\Constants\BookingDetailStatus;
use App\Constants\BookingStatus;
use App\Constants\BookingType;
use App\Constants\ScheduleType;
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
use App\Notifications\BookingApprovedByKaprodiNotification;
use App\Notifications\BookingFinalResultNotification;
use App\Notifications\NewBookingSubmittedNotification;
use App\Notifications\RescheduleBookingSubmittedNotification;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
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
    private StudyProgram $studyProgram;

    protected function setUp(): void
    {
        parent::setUp();

        $roleVisitor = Role::create(['name' => 'Visitor']);
        $roleStaff = Role::create(['name' => 'Staf_Lab']);
        $roleKaprodi = Role::create(['name' => 'Kepala_Prodi']);
        $roleKalab = Role::create(['name' => 'Kepala_Lab']);
        $roleDosen = Role::create(['name' => 'Dosen']);
        $roleAdmin = Role::create(['name' => 'Admin']);

        $this->studyProgram = StudyProgram::create([
            'code' => 'IF',
            'name' => 'S1 Teknik Informatika',
            'color_hex' => '#FFF3B0',
            'active' => true,
        ]);

        $this->visitor = User::create([
            'id' => 'V000001',
            'name' => 'Valentino Hose',
            'email' => 'visitor@test.com',
            'password' => Hash::make('password'),
        ]);
        UserRole::create([
            'user_id' => $this->visitor->id,
            'role_id' => $roleVisitor->id,
            'start_date' => now()->subMonth()->toDateString(),
        ]);

        $this->staff = User::create([
            'id' => 'STF001',
            'name' => 'Staf Lab',
            'email' => 'staff@test.com',
            'password' => Hash::make('password'),
        ]);
        UserRole::create([
            'user_id' => $this->staff->id,
            'role_id' => $roleStaff->id,
            'start_date' => now()->subMonth()->toDateString(),
        ]);

        $this->kaprodi = User::create([
            'id' => '720319',
            'name' => 'Rossevine Artha Nathasya',
            'email' => 'kaprodi@test.com',
            'password' => Hash::make('password'),
            'study_program_id' => $this->studyProgram->id,
        ]);
        UserRole::create([
            'user_id' => $this->kaprodi->id,
            'role_id' => $roleDosen->id,
            'start_date' => now()->subMonth()->toDateString(),
        ]);
        UserRole::create([
            'user_id' => $this->kaprodi->id,
            'role_id' => $roleKaprodi->id,
            'start_date' => now()->subMonth()->toDateString(),
        ]);

        $this->kalab = User::create([
            'id' => '720282',
            'name' => 'Andreas Widjaja',
            'email' => 'kalab@test.com',
            'password' => Hash::make('password'),
            'study_program_id' => $this->studyProgram->id,
        ]);
        UserRole::create([
            'user_id' => $this->kalab->id,
            'role_id' => $roleDosen->id,
            'start_date' => now()->subMonth()->toDateString(),
        ]);
        UserRole::create([
            'user_id' => $this->kalab->id,
            'role_id' => $roleKalab->id,
            'start_date' => now()->subMonth()->toDateString(),
        ]);

        $this->adv1 = Room::create(['code' => 'ADV1', 'name' => 'Advanced 1', 'capacity' => 30, 'active' => true]);
        $this->adv2 = Room::create(['code' => 'ADV2', 'name' => 'Advanced 2', 'capacity' => 30, 'active' => true]);

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
    }

    /**
     * Test 1: User dengan dua role aktif, dan role yang end_date-nya sudah lewat tidak berlaku.
     */
    public function test_user_with_two_active_roles_and_expired_role_is_ignored(): void
    {
        $roleDosen = Role::where('name', 'Dosen')->first();
        $roleKalab = Role::where('name', 'Kepala_Lab')->first();

        $user = User::create([
            'id' => '123456',
            'name' => 'Test Multi Role',
            'email' => 'multirole@test.com',
            'password' => Hash::make('password'),
        ]);

        // Role Dosen aktif
        UserRole::create([
            'user_id' => $user->id,
            'role_id' => $roleDosen->id,
            'start_date' => now()->subMonth()->toDateString(),
            'end_date' => null,
        ]);

        // Role Kalab sudah kedaluwarsa kemarin
        UserRole::create([
            'user_id' => $user->id,
            'role_id' => $roleKalab->id,
            'start_date' => now()->subYear()->toDateString(),
            'end_date' => now()->subDay()->toDateString(),
        ]);

        $this->assertTrue($user->hasActiveRole('Dosen'));
        $this->assertFalse($user->hasActiveRole('Kepala_Lab'));

        // Jika user tidak punya role sama sekali -> diperlakukan sebagai Visitor
        $emptyUser = User::create([
            'id' => 'V999999',
            'name' => 'No Role User',
            'email' => 'norole@test.com',
            'password' => Hash::make('password'),
        ]);
        $this->assertTrue($emptyUser->hasActiveRole('Visitor'));
        $this->assertFalse($emptyUser->hasActiveRole('Dosen'));
    }

    /**
     * Test 2: Kaprodi tolak (alasan wajib) & Kaprodi setuju lalu Kalab setuju sebagian ruangan
     * (booking jadi status 2, ruangan ditolak tidak memblok kalender).
     */
    public function test_kaprodi_reject_requires_notes_and_kalab_partial_approval(): void
    {
        Notification::fake();

        // 1. Submit booking baru 2 slot
        $booking = Booking::create([
            'user_id' => $this->visitor->id,
            'requester_name' => 'Valentino Hose',
            'purpose' => 'Seminar AI',
            'type' => BookingType::NEW_BOOKING,
            'status' => BookingStatus::PENDING_KAPRODI,
            'submitted_at' => now(),
        ]);
        $d1 = $booking->details()->create([
            'room_id' => $this->adv1->id,
            'start_datetime' => now()->addDays(5)->setTime(9, 0),
            'end_datetime' => now()->addDays(5)->setTime(11, 0),
            'status' => BookingDetailStatus::PENDING,
        ]);
        $d2 = $booking->details()->create([
            'room_id' => $this->adv2->id,
            'start_datetime' => now()->addDays(5)->setTime(13, 0),
            'end_datetime' => now()->addDays(5)->setTime(15, 0),
            'status' => BookingDetailStatus::PENDING,
        ]);

        // Kaprodi tolak tanpa alasan -> gagal validasi
        $this->actingAs($this->kaprodi);
        $respRejectFail = $this->post(route('approvals.decideKaprodi', $booking), [
            'status' => 'rejected',
            'notes' => '',
        ]);
        $respRejectFail->assertSessionHasErrors('notes');

        // Kaprodi setuju -> booking jadi status 1 (PENDING_KALAB)
        $respApprove = $this->post(route('approvals.decideKaprodi', $booking), [
            'status' => 'approved',
        ]);
        $respApprove->assertSessionHas('success');
        $this->assertEquals(BookingStatus::PENDING_KALAB, $booking->fresh()->status);

        // Pastikan email ke Kalab terkirim dan TIDAK ke peminjam di tahap tengah
        Notification::assertSentTo($this->kalab, BookingApprovedByKaprodiNotification::class);
        Notification::assertNotSentTo($this->visitor, BookingFinalResultNotification::class);

        // Kalab memutuskan per ruangan: setuju d1, tolak d2 (dengan alasan)
        $this->actingAs($this->kalab);
        $this->post(route('approvals.decideKalabDetail', $d1), [
            'status' => 'approved',
        ])->assertSessionHas('success');

        $this->post(route('approvals.decideKalabDetail', $d2), [
            'status' => 'rejected',
            'notes' => 'Ruangan ADV2 sedang pemeliharaan AC.',
        ])->assertSessionHas('success');

        $booking->refresh();
        $this->assertEquals(BookingStatus::APPROVED, $booking->status);
        $this->assertEquals(BookingDetailStatus::APPROVED, $d1->fresh()->status);
        $this->assertEquals(BookingDetailStatus::REJECTED, $d2->fresh()->status);

        // Ruangan ditolak (d2) tidak boleh memblok kalender
        $service = app(\App\Services\BookingService::class);
        $this->assertTrue($service->isAvailable($this->adv2->id, $d2->start_datetime, $d2->end_datetime));
        $this->assertFalse($service->isAvailable($this->adv1->id, $d1->start_datetime, $d1->end_datetime));

        // Email hasil akhir terkirim ke peminjam
        Notification::assertSentTo($this->visitor, BookingFinalResultNotification::class);
    }

    /**
     * Test 3: Kalab tidak bisa menyetujui ruangan yang sudah bentrok.
     */
    public function test_kalab_cannot_approve_conflicting_slot(): void
    {
        $existingApproved = Booking::create([
            'user_id' => $this->staff->id,
            'requester_name' => 'Existing Dosen',
            'purpose' => 'Existing Event',
            'type' => BookingType::NEW_BOOKING,
            'status' => BookingStatus::APPROVED,
            'submitted_at' => now(),
        ]);
        $existingApproved->details()->create([
            'room_id' => $this->adv1->id,
            'start_datetime' => now()->addDays(5)->setTime(10, 0),
            'end_datetime' => now()->addDays(5)->setTime(12, 0),
            'status' => BookingDetailStatus::APPROVED,
        ]);

        $newBooking = Booking::create([
            'user_id' => $this->visitor->id,
            'requester_name' => 'Valentino Hose',
            'purpose' => 'Bentrok Event',
            'type' => BookingType::NEW_BOOKING,
            'status' => BookingStatus::PENDING_KALAB,
            'submitted_at' => now(),
        ]);
        $detailBentrok = $newBooking->details()->create([
            'room_id' => $this->adv1->id,
            'start_datetime' => now()->addDays(5)->setTime(11, 0),
            'end_datetime' => now()->addDays(5)->setTime(13, 0),
            'status' => BookingDetailStatus::PENDING,
        ]);

        $this->actingAs($this->kalab);
        $response = $this->post(route('approvals.decideKalabDetail', $detailBentrok), [
            'status' => 'approved',
        ]);
        $response->assertSessionHasErrors('status');
        $this->assertEquals(BookingDetailStatus::PENDING, $detailBentrok->fresh()->status);
    }

    /**
     * Test 4: Ubah jadwal langsung ke Kalab dan booking lama jadi 4 setelah disetujui.
     */
    public function test_reschedule_goes_directly_to_kalab_and_cancels_old_booking(): void
    {
        Notification::fake();

        $originalBooking = Booking::create([
            'user_id' => $this->visitor->id,
            'requester_name' => 'Valentino Hose',
            'purpose' => 'Workshop UI/UX',
            'type' => BookingType::NEW_BOOKING,
            'status' => BookingStatus::APPROVED,
            'submitted_at' => now()->subDays(3),
        ]);
        $origDetail = $originalBooking->details()->create([
            'room_id' => $this->adv1->id,
            'start_datetime' => now()->addDays(6)->setTime(9, 0),
            'end_datetime' => now()->addDays(6)->setTime(11, 0),
            'status' => BookingDetailStatus::APPROVED,
        ]);

        $this->actingAs($this->visitor);

        // Ajukan ubah jadwal
        $respChange = $this->post(route('bookings.store'), [
            'type' => 'change',
            'parent_booking_id' => $originalBooking->id,
            'requester_name' => 'Valentino Hose',
            'purpose' => 'Workshop UI/UX Update',
            'notes' => 'Pindah ke ADV2 karena peserta bertambah.',
            'slots' => [
                [
                    'room_id' => $this->adv2->id,
                    'date' => now()->addDays(6)->format('Y-m-d'),
                    'start' => '09:00',
                    'end' => '11:00',
                ],
            ],
        ]);
        $respChange->assertRedirect(route('bookings.index'));

        $changeBooking = Booking::where('parent_booking_id', $originalBooking->id)->first();
        $this->assertNotNull($changeBooking);
        $this->assertEquals(BookingType::RESCHEDULE, $changeBooking->type);
        // Langsung ke status 1 (PENDING_KALAB)
        $this->assertEquals(BookingStatus::PENDING_KALAB, $changeBooking->status);

        // Email langsung ke Kalab
        Notification::assertSentTo($this->kalab, RescheduleBookingSubmittedNotification::class);

        // Kalab setujui ruangan baru
        $this->actingAs($this->kalab);
        $changeDetail = $changeBooking->details->first();
        $this->post(route('approvals.decideKalabDetail', $changeDetail), [
            'status' => 'approved',
        ])->assertSessionHas('success');

        // Booking lama jadi status 4 (CANCELLED) dan detailnya jadi 3 (CANCELLED)
        $this->assertEquals(BookingStatus::CANCELLED, $originalBooking->fresh()->status);
        $this->assertEquals(BookingDetailStatus::CANCELLED, $origDetail->fresh()->status);
        $this->assertEquals(BookingStatus::APPROVED, $changeBooking->fresh()->status);
    }

    /**
     * Test 5: Pembatalan sebelum H-2 berhasil, setelah H-2 ditolak.
     */
    public function test_cancellation_allowed_before_h2_and_forbidden_within_h2(): void
    {
        // Booking A: Waktu mulai 5 hari lagi (> H-2) -> Bisa dibatalkan
        $bookingFar = Booking::create([
            'user_id' => $this->visitor->id,
            'requester_name' => 'Valentino Hose',
            'purpose' => 'Kegiatan Jauh',
            'type' => BookingType::NEW_BOOKING,
            'status' => BookingStatus::APPROVED,
            'submitted_at' => now(),
        ]);
        $dFar = $bookingFar->details()->create([
            'room_id' => $this->adv1->id,
            'start_datetime' => now()->addDays(5)->setTime(10, 0),
            'end_datetime' => now()->addDays(5)->setTime(12, 0),
            'status' => BookingDetailStatus::APPROVED,
        ]);

        $this->actingAs($this->visitor);
        $this->post(route('bookings.cancel', $bookingFar))->assertSessionHas('success');
        $this->assertEquals(BookingStatus::CANCELLED, $bookingFar->fresh()->status);
        $this->assertEquals(BookingDetailStatus::CANCELLED, $dFar->fresh()->status);

        // Booking B: Waktu mulai besok (< H-2) -> Ditolak (403)
        $bookingNear = Booking::create([
            'user_id' => $this->visitor->id,
            'requester_name' => 'Valentino Hose',
            'purpose' => 'Kegiatan Mepet',
            'type' => BookingType::NEW_BOOKING,
            'status' => BookingStatus::APPROVED,
            'submitted_at' => now(),
        ]);
        $bookingNear->details()->create([
            'room_id' => $this->adv1->id,
            'start_datetime' => now()->addDay()->setTime(10, 0),
            'end_datetime' => now()->addDay()->setTime(12, 0),
            'status' => BookingDetailStatus::APPROVED,
        ]);

        $responseNear = $this->post(route('bookings.cancel', $bookingNear));
        $responseNear->assertStatus(403);
        $this->assertEquals(BookingStatus::APPROVED, $bookingNear->fresh()->status);
    }

    /**
     * Test 6: Auto-reject H-2 command.
     */
    public function test_auto_reject_h2(): void
    {
        Notification::fake();

        $booking = Booking::create([
            'user_id' => $this->visitor->id,
            'requester_name' => 'Valentino Hose',
            'purpose' => 'Kegiatan Terlantar',
            'type' => BookingType::NEW_BOOKING,
            'status' => BookingStatus::PENDING_KAPRODI,
            'submitted_at' => now()->subDays(2),
        ]);
        $detail = $booking->details()->create([
            'room_id' => $this->adv1->id,
            'start_datetime' => now()->addDay()->setTime(10, 0),
            'end_datetime' => now()->addDay()->setTime(12, 0),
            'status' => BookingDetailStatus::PENDING,
        ]);

        Artisan::call('bookings:auto-reject');

        $this->assertEquals(BookingStatus::REJECTED, $booking->fresh()->status);
        $this->assertEquals(BookingDetailStatus::REJECTED, $detail->fresh()->status);
        Notification::assertSentTo($this->visitor, BookingFinalResultNotification::class);
    }

    /**
     * Test 7: Nomor "Pending (N)" bergeser setelah yang di depan ditolak.
     */
    public function test_pending_queue_number_shifts_after_earlier_booking_rejected(): void
    {
        $service = app(\App\Services\BookingService::class);

        // Booking 1 (submitted jam 08:00)
        $b1 = Booking::create([
            'user_id' => $this->visitor->id,
            'requester_name' => 'Antrian 1',
            'purpose' => 'P1',
            'type' => BookingType::NEW_BOOKING,
            'status' => BookingStatus::PENDING_KAPRODI,
            'submitted_at' => now()->subHours(2),
        ]);
        $d1 = $b1->details()->create([
            'room_id' => $this->adv1->id,
            'start_datetime' => now()->addDays(5)->setTime(10, 0),
            'end_datetime' => now()->addDays(5)->setTime(12, 0),
            'status' => BookingDetailStatus::PENDING,
        ]);

        // Booking 2 (submitted jam 09:00, waktu sama)
        $b2 = Booking::create([
            'user_id' => $this->visitor->id,
            'requester_name' => 'Antrian 2',
            'purpose' => 'P2',
            'type' => BookingType::NEW_BOOKING,
            'status' => BookingStatus::PENDING_KAPRODI,
            'submitted_at' => now()->subHour(),
        ]);
        $d2 = $b2->details()->create([
            'room_id' => $this->adv1->id,
            'start_datetime' => now()->addDays(5)->setTime(10, 0),
            'end_datetime' => now()->addDays(5)->setTime(12, 0),
            'status' => BookingDetailStatus::PENDING,
        ]);

        // Antrian d1 = 1, d2 = 2
        $this->assertEquals(1, $service->pendingQueuePosition($d1));
        $this->assertEquals(2, $service->pendingQueuePosition($d2));

        // d1 ditolak Kaprodi
        $this->actingAs($this->kaprodi);
        $this->post(route('approvals.decideKaprodi', $b1), [
            'status' => 'rejected',
            'notes' => 'Tidak diizinkan.',
        ]);

        // Sekarang antrian d2 bergeser menjadi 1!
        $this->assertEquals(1, $service->pendingQueuePosition($d2->fresh()));
    }

    /**
     * Test 8: NIK tidak muncul di halaman kalender atau form peminjaman.
     */
    public function test_nik_does_not_leak_on_calendar_or_booking_form(): void
    {
        $dosenUser = User::create([
            'id' => '198705152010121005', // NIK
            'name' => 'Prof. Dr. Dosen Rahasia',
            'email' => 'dosenrahasia@test.com',
            'password' => Hash::make('password'),
        ]);

        $course = Course::create([
            'study_program_id' => $this->studyProgram->id,
            'code' => 'IF101',
            'name' => 'Algoritma',
            'active' => true,
        ]);

        Section::create([
            'period_id' => $this->period->id,
            'course_id' => $course->id,
            'room_id' => $this->adv1->id,
            'lecturer_nik' => $dosenUser->id,
            'class_code' => 'A',
            'day_of_week' => 1,
            'start_time' => '08:00',
            'end_time' => '10:00',
            'schedule_type' => ScheduleType::REGULAR,
        ]);

        // Visitor buka halaman utama / kalender
        $responseHome = $this->get('/?week=1&day=1');
        $responseHome->assertOk();
        $responseHome->assertDontSee($dosenUser->id); // NIK TIDAK boleh muncul

        // Form create peminjaman
        $this->actingAs($this->visitor);
        $responseCreate = $this->get(route('bookings.create'));
        $responseCreate->assertOk();
        $responseCreate->assertDontSee($dosenUser->id);
    }
}
