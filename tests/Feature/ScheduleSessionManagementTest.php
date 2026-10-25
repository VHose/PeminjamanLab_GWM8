<?php

namespace Tests\Feature;

use App\Enums\BookingDetailStatus;
use App\Enums\BookingStatus;
use App\Enums\ScheduleType;
use App\Models\Booking;
use App\Models\BookingDetail;
use App\Models\Course;
use App\Models\Period;
use App\Models\PeriodHoliday;
use App\Models\Role;
use App\Models\Room;
use App\Models\Schedule;
use App\Models\ScheduleSession;
use App\Models\StudyProgram;
use App\Models\User;
use App\Models\UserRole;
use App\Services\BookingService;
use App\Services\ScheduleSessionGenerator;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class ScheduleSessionManagementTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private User $visitor;
    private User $staff;
    private User $lecturer;
    private User $kalab;
    private User $kaprodi;
    private Period $period;
    private Room $adv1;
    private Room $adv2;
    private Course $course;

    protected function setUp(): void
    {
        parent::setUp();

        $adminRole = Role::create(['name' => 'Admin']);
        $kalabRole = Role::create(['name' => 'Kepala_Lab']);
        $staffRole = Role::create(['name' => 'Staf_Lab']);
        $dosenRole = Role::create(['name' => 'Dosen']);
        $kaprodiRole = Role::create(['name' => 'Kepala_Prodi']);
        $visitorRole = Role::create(['name' => 'Visitor']);

        $prodi = StudyProgram::create(['id' => '72', 'name' => 'Informatika', 'color_hex' => '#FFF3B0', 'is_active' => true]);

        $this->adv1 = Room::create(['id' => 'ADV1', 'name' => 'Lab ADV 1', 'capacity' => 40, 'is_active' => true]);
        $this->adv2 = Room::create(['id' => 'ADV2', 'name' => 'Lab ADV 2', 'capacity' => 40, 'is_active' => true]);

        $this->period = Period::create([
            'name' => '2026/2027 Ganjil',
            'start_date' => '2026-09-14', // Senin W01
            'end_date' => '2027-01-29',
            'uts_start' => '2026-11-02',
            'uts_end' => '2026-11-13',
            'uas_start' => '2027-01-18',
            'uas_end' => '2027-01-29',
            'is_active' => true,
        ]);

        PeriodHoliday::create([
            'period_id' => $this->period->id,
            'start_date' => '2026-12-21',
            'end_date' => '2027-01-03',
            'note' => 'NATARU (Natal dan Tahun Baru)',
        ]);

        $this->admin = User::create([
            'id' => 'ADMIN01',
            'name' => 'Admin Lab',
            'email' => 'admin@test.com',
            'password' => Hash::make('password123'),
            'is_active' => true,
            'must_change_password' => false,
        ]);
        UserRole::create([
            'user_id' => $this->admin->id,
            'role_id' => $adminRole->id,
            'period_id' => $this->period->id,
        ]);

        $this->visitor = User::create([
            'id' => 'VISITOR01',
            'name' => 'Peminjam Mahasiswa',
            'email' => 'visitor@test.com',
            'password' => Hash::make('password123'),
            'is_active' => true,
            'must_change_password' => false,
        ]);
        UserRole::create([
            'user_id' => $this->visitor->id,
            'role_id' => $visitorRole->id,
            'period_id' => $this->period->id,
        ]);

        $this->staff = User::create([
            'id' => 'STAFF01',
            'name' => 'Staf Laboratorium',
            'email' => 'staff@test.com',
            'password' => Hash::make('password123'),
            'is_active' => true,
            'must_change_password' => false,
        ]);
        UserRole::create([
            'user_id' => $this->staff->id,
            'role_id' => $staffRole->id,
            'period_id' => $this->period->id,
        ]);

        $this->lecturer = User::create([
            'id' => 'DOSEN01',
            'name' => 'Dosen Pengampu',
            'email' => 'dosen@test.com',
            'password' => Hash::make('password123'),
            'is_active' => true,
            'must_change_password' => false,
        ]);
        UserRole::create([
            'user_id' => $this->lecturer->id,
            'role_id' => $dosenRole->id,
            'period_id' => $this->period->id,
        ]);

        $this->kalab = User::create([
            'id' => 'KALAB01',
            'name' => 'Kepala Lab',
            'email' => 'kalab@test.com',
            'password' => Hash::make('password123'),
            'is_active' => true,
            'must_change_password' => false,
        ]);
        UserRole::create([
            'user_id' => $this->kalab->id,
            'role_id' => $kalabRole->id,
            'period_id' => $this->period->id,
        ]);

        $this->kaprodi = User::create([
            'id' => 'KAPRODI01',
            'name' => 'Kepala Prodi',
            'email' => 'kaprodi@test.com',
            'password' => Hash::make('password123'),
            'is_active' => true,
            'must_change_password' => false,
        ]);
        UserRole::create([
            'user_id' => $this->kaprodi->id,
            'role_id' => $kaprodiRole->id,
            'period_id' => $this->period->id,
        ]);

        $this->course = Course::create([
            'id' => 'IF2110',
            'name' => 'Algoritma & Pemrograman',
            'study_program_id' => $prodi->id,
            'is_active' => true,
        ]);
    }

    /**
     * 1. Admin dapat membatalkan (meliburkan) dan mengaktifkan kembali pertemuan kuliah via toggle.
     */
    public function test_admin_can_toggle_lecture_session_off_and_on_directly(): void
    {
        $schedule = Schedule::create([
            'period_id' => $this->period->id,
            'course_id' => $this->course->id,
            'class_code' => 'A',
            'type' => ScheduleType::THEORY,
            'day_of_week' => 1, // Senin
            'user_id' => $this->lecturer->id,
            'room_id' => $this->adv1->id,
            'start_time' => '08:00:00',
            'end_time' => '10:00:00',
        ]);

        app(ScheduleSessionGenerator::class)->generateForPeriod($this->period);

        $session1 = ScheduleSession::where('schedule_id', $schedule->id)->where('week_no', 1)->first();
        $session2 = ScheduleSession::where('schedule_id', $schedule->id)->where('week_no', 2)->first();
        $session3 = ScheduleSession::where('schedule_id', $schedule->id)->where('week_no', 3)->first();

        $this->assertTrue($session1->is_active);
        $this->assertTrue($session2->is_active);
        $this->assertTrue($session3->is_active);

        // Admin toggle OFF (meliburkan pertemuan ke-2)
        $response = $this->actingAs($this->admin)->post(
            route('admin.schedule-sessions.toggle', [$schedule->id, 2]),
            ['note' => 'Kuliah diliburkan karena dosen cuti.']
        );

        $response->assertSessionHas('success');

        $session1->refresh();
        $session2->refresh();
        $session3->refresh();

        $this->assertTrue($session1->is_active, 'Pertemuan ke-1 harus tetap aktif.');
        $this->assertFalse($session2->is_active, 'Pertemuan ke-2 harus menjadi libur/nonaktif.');
        $this->assertSame('Kuliah diliburkan karena dosen cuti.', $session2->note);
        $this->assertTrue($session3->is_active, 'Pertemuan ke-3 harus tetap aktif.');

        // Jadwal master Schedule TIDAK boleh berubah
        $schedule->refresh();
        $this->assertSame('08:00:00', $schedule->start_time);
        $this->assertSame('10:00:00', $schedule->end_time);
        $this->assertSame($this->adv1->id, $schedule->room_id);

        // Pastikan tercatat di activity log
        $this->assertDatabaseHas('activity_log', [
            'user_id' => $this->admin->id,
            'action' => 'cancel_session',
            'subject_type' => 'schedule_session',
            'subject_id' => "{$schedule->id}-2",
        ]);

        // Admin toggle ON (mengaktifkan kembali pertemuan ke-2)
        $responseReactivate = $this->actingAs($this->admin)->post(
            route('admin.schedule-sessions.toggle', [$schedule->id, 2])
        );

        $responseReactivate->assertSessionHas('success');

        $session2->refresh();
        $this->assertTrue($session2->is_active, 'Pertemuan ke-2 harus kembali aktif.');
        $this->assertNull($session2->note, 'Catatan pembatalan harus dibersihkan saat diaktifkan kembali.');

        $this->assertDatabaseHas('activity_log', [
            'user_id' => $this->admin->id,
            'action' => 'activate_session',
            'subject_type' => 'schedule_session',
            'subject_id' => "{$schedule->id}-2",
        ]);
    }

    /**
     * 2. Memastikan ruangan hanya dibebaskan pada slot yang diliburkan.
     */
    public function test_room_is_freed_only_for_cancelled_slot(): void
    {
        $bookingService = app(BookingService::class);

        $schedule = Schedule::create([
            'period_id' => $this->period->id,
            'course_id' => $this->course->id,
            'class_code' => 'A',
            'type' => ScheduleType::THEORY,
            'day_of_week' => 1, // Senin
            'user_id' => $this->lecturer->id,
            'room_id' => $this->adv1->id,
            'start_time' => '08:00:00',
            'end_time' => '10:00:00',
        ]);

        app(ScheduleSessionGenerator::class)->generateForPeriod($this->period);

        $session1 = ScheduleSession::where('schedule_id', $schedule->id)->where('week_no', 1)->first();
        $session2 = ScheduleSession::where('schedule_id', $schedule->id)->where('week_no', 2)->first();

        $date1 = $session1->session_date->toDateString();
        $date2 = $session2->session_date->toDateString();

        $start1 = Carbon::parse("{$date1} 08:00:00");
        $end1   = Carbon::parse("{$date1} 10:00:00");

        $start2 = Carbon::parse("{$date2} 08:00:00");
        $end2   = Carbon::parse("{$date2} 10:00:00");

        // Awal: Keduanya aktif memblokir ruangan ADV1
        $this->assertFalse($bookingService->isAvailable($this->adv1->id, $start1, $end1));
        $this->assertFalse($bookingService->isAvailable($this->adv1->id, $start2, $end2));

        // Batalkan / liburkan pertemuan ke-2
        $this->actingAs($this->admin)->post(route('admin.schedule-sessions.toggle', [$schedule->id, 2]), [
            'note' => 'Libur kuliah.',
        ]);

        // Ruangan pada tanggal pertemuan ke-2 sekarang BEBAS dan dapat dipinjam
        $this->assertTrue($bookingService->isAvailable($this->adv1->id, $start2, $end2), 'Slot pertemuan ke-2 harus bebas.');

        // Ruangan pada tanggal pertemuan ke-1 TETAP TERBLOKIR
        $this->assertFalse($bookingService->isAvailable($this->adv1->id, $start1, $end1), 'Slot pertemuan ke-1 harus tetap terblokir.');
    }

    /**
     * 3. Mengaktifkan kembali pertemuan & mencegah bentrok jika slot sudah terisi peminjaman approved.
     */
    public function test_reactivate_meeting_and_prevent_conflict_when_slot_already_booked(): void
    {
        $bookingService = app(BookingService::class);

        $schedule = Schedule::create([
            'period_id' => $this->period->id,
            'course_id' => $this->course->id,
            'class_code' => 'A',
            'type' => ScheduleType::THEORY,
            'day_of_week' => 1,
            'user_id' => $this->lecturer->id,
            'room_id' => $this->adv1->id,
            'start_time' => '08:00:00',
            'end_time' => '10:00:00',
        ]);

        app(ScheduleSessionGenerator::class)->generateForPeriod($this->period);

        $session2 = ScheduleSession::where('schedule_id', $schedule->id)->where('week_no', 2)->first();
        $date2 = $session2->session_date->toDateString();

        // 1. Batalkan pertemuan ke-2
        $this->actingAs($this->admin)->post(route('admin.schedule-sessions.toggle', [$schedule->id, 2]));
        $this->assertFalse($session2->fresh()->is_active);

        // 2. Karena slot bebas, ada peminjaman masuk dan disetujui (APPROVED)
        $booking = Booking::create([
            'user_id' => $this->visitor->id,
            'pic_name' => 'Valentino Hose',
            'pic_phone' => '08123456789',
            'purpose' => 'Workshop Coding',
            'status' => BookingStatus::APPROVED,
        ]);
        BookingDetail::create([
            'booking_id' => $booking->id,
            'room_id' => $this->adv1->id,
            'booking_date' => $date2,
            'start_time' => '08:30:00',
            'end_time' => '09:30:00',
            'status' => BookingDetailStatus::APPROVED,
        ]);

        // 3. Admin mencoba mengaktifkan kembali pertemuan ke-2 -> harus DITOLAK karena bentrok dengan booking APPROVED
        $failResponse = $this->actingAs($this->admin)->post(route('admin.schedule-sessions.toggle', [$schedule->id, 2]));
        $failResponse->assertSessionHasErrors(['error']);
        $this->assertFalse($session2->fresh()->is_active, 'Pertemuan ke-2 harus tetap nonaktif karena ada bentrok.');

        // 4. Batalkan peminjaman tersebut sehingga slot kembali bebas
        $booking->details()->update(['status' => BookingDetailStatus::CANCELLED]);

        // 5. Admin mengaktifkan kembali pertemuan ke-2 -> SEKARANG BERHASIL
        $successResponse = $this->actingAs($this->admin)->post(route('admin.schedule-sessions.toggle', [$schedule->id, 2]));
        $successResponse->assertSessionHas('success');
        $this->assertTrue($session2->fresh()->is_active, 'Pertemuan ke-2 berhasil diaktifkan kembali.');

        // 6. Slot ADV1 kembali terblokir oleh kuliah pertemuan ke-2
        $start2 = Carbon::parse("{$date2} 08:00:00");
        $end2   = Carbon::parse("{$date2} 10:00:00");
        $this->assertFalse($bookingService->isAvailable($this->adv1->id, $start2, $end2));
    }

    /**
     * 4. Mencegah generate ulang membuat data duplikat atau menimpa status yang sudah diubah.
     */
    public function test_regenerate_does_not_create_duplicates_or_overwrite_custom_status(): void
    {
        $schedule = Schedule::create([
            'period_id' => $this->period->id,
            'course_id' => $this->course->id,
            'class_code' => 'A',
            'type' => ScheduleType::THEORY,
            'day_of_week' => 1,
            'user_id' => $this->lecturer->id,
            'room_id' => $this->adv1->id,
            'start_time' => '08:00:00',
            'end_time' => '10:00:00',
        ]);

        $generator = app(ScheduleSessionGenerator::class);

        // Generate pertama
        $res1 = $generator->generateForPeriod($this->period);
        $totalInitial = ScheduleSession::where('schedule_id', $schedule->id)->count();
        $this->assertSame(14, $totalInitial, 'Harus menghasilkan 14 pertemuan kuliah reguler.');
        $this->assertSame(14, $res1['created']);

        // Ubah pertemuan ke-2 menjadi nonaktif dengan catatan khusus
        $session2 = ScheduleSession::where('schedule_id', $schedule->id)->where('week_no', 2)->first();
        $session2->update([
            'is_active' => false,
            'note' => 'Dibatalkan khusus oleh koordinator.',
        ]);

        // Generate kedua (regenerate)
        $res2 = $generator->generateForPeriod($this->period);
        $this->assertSame(0, $res2['created'], 'Tidak boleh ada sesi baru yang dibuat duplikat.');
        $this->assertSame(14, $res2['skipped'], 'Semua 14 sesi yang ada harus dilewati/dipertahankan.');

        // Total tetap 14 baris
        $this->assertSame(14, ScheduleSession::where('schedule_id', $schedule->id)->count());

        // Status custom pertemuan ke-2 TIDAK BOLEH tertimpa
        $session2->refresh();
        $this->assertFalse($session2->is_active);
        $this->assertSame('Dibatalkan khusus oleh koordinator.', $session2->note);
    }

    /**
     * 5. Uji aturan visibilitas dan tampilan toggle pada tabel jadwal:
     *    - Admin: melihat sesi libur DAN melihat toggle switch.
     *    - Dosen, Kaprodi, Kalab: melihat sesi libur, TIDAK melihat toggle switch.
     *    - Staf_Lab, Visitor, Guest: TIDAK melihat sesi libur sama sekali (slot kosong dapat dipinjam), TIDAK melihat toggle switch.
     */
    public function test_visibility_and_toggle_rules_for_different_user_roles(): void
    {
        $schedule = Schedule::create([
            'period_id' => $this->period->id,
            'course_id' => $this->course->id,
            'class_code' => 'A',
            'type' => ScheduleType::THEORY,
            'day_of_week' => 1, // Senin
            'user_id' => $this->lecturer->id,
            'room_id' => $this->adv1->id,
            'start_time' => '08:00:00',
            'end_time' => '10:00:00',
        ]);

        app(ScheduleSessionGenerator::class)->generateForPeriod($this->period);

        // Liburkan pertemuan ke-1 (Senin W01: 2026-09-14)
        $session1 = ScheduleSession::where('schedule_id', $schedule->id)->where('week_no', 1)->first();
        $session1->update([
            'is_active' => false,
            'note' => 'Hari pertama diliburkan.',
        ]);

        $url = route('home', ['period_id' => $this->period->id, 'week' => 1, 'day' => 1]);

        // A. ADMIN: Melihat sesi libur dan toggle switch
        $resAdmin = $this->actingAs($this->admin)->get($url);
        $resAdmin->assertOk();
        $resAdmin->assertSee('Kuliah Libur');
        $resAdmin->assertSee($this->course->name);
        $resAdmin->assertSee('toggle-' . $schedule->id . '-1'); // Ada elemen input toggle switch

        // B. DOSEN: Melihat sesi libur, TIDAK melihat toggle switch
        $resDosen = $this->actingAs($this->lecturer)->get($url);
        $resDosen->assertOk();
        $resDosen->assertSee('Kuliah Libur');
        $resDosen->assertSee($this->course->name);
        $resDosen->assertDontSee('toggle-' . $schedule->id . '-1'); // Tidak ada toggle switch

        // C. KEPALA PRODI: Melihat sesi libur, TIDAK melihat toggle switch
        $resKaprodi = $this->actingAs($this->kaprodi)->get($url);
        $resKaprodi->assertOk();
        $resKaprodi->assertSee('Kuliah Libur');
        $resKaprodi->assertSee($this->course->name);
        $resKaprodi->assertDontSee('toggle-' . $schedule->id . '-1');

        // D. KEPALA LAB: Melihat sesi libur, TIDAK melihat toggle switch
        $resKalab = $this->actingAs($this->kalab)->get($url);
        $resKalab->assertOk();
        $resKalab->assertSee('Kuliah Libur');
        $resKalab->assertSee($this->course->name);
        $resKalab->assertDontSee('toggle-' . $schedule->id . '-1');

        // E. STAF LAB: TIDAK melihat sesi libur sama sekali, TIDAK melihat toggle switch
        $resStaff = $this->actingAs($this->staff)->get($url);
        $resStaff->assertOk();
        $resStaff->assertDontSee('Kuliah Libur');
        $resStaff->assertDontSee($this->course->name);
        $resStaff->assertDontSee('toggle-' . $schedule->id . '-1');

        // F. VISITOR: TIDAK melihat sesi libur sama sekali, TIDAK melihat toggle switch
        $resVisitor = $this->actingAs($this->visitor)->get($url);
        $resVisitor->assertOk();
        $resVisitor->assertDontSee('Kuliah Libur');
        $resVisitor->assertDontSee($this->course->name);
        $resVisitor->assertDontSee('toggle-' . $schedule->id . '-1');

        // G. GUEST (Unauthenticated): TIDAK melihat sesi libur sama sekali, TIDAK melihat toggle switch
        $resGuest = $this->get($url);
        $resGuest->assertOk();
        $resGuest->assertDontSee('Kuliah Libur');
        $resGuest->assertDontSee($this->course->name);
        $resGuest->assertDontSee('toggle-' . $schedule->id . '-1');
    }

    /**
     * 6. User selain Admin tidak dapat mengakses aksi toggle (403 Forbidden).
     */
    public function test_non_admin_cannot_toggle_session(): void
    {
        $schedule = Schedule::create([
            'period_id' => $this->period->id,
            'course_id' => $this->course->id,
            'class_code' => 'A',
            'type' => ScheduleType::THEORY,
            'day_of_week' => 1,
            'user_id' => $this->lecturer->id,
            'room_id' => $this->adv1->id,
            'start_time' => '08:00:00',
            'end_time' => '10:00:00',
        ]);

        app(ScheduleSessionGenerator::class)->generateForPeriod($this->period);

        $toggleUrl = route('admin.schedule-sessions.toggle', [$schedule->id, 1]);

        $this->actingAs($this->visitor)->post($toggleUrl)->assertForbidden();
        $this->actingAs($this->staff)->post($toggleUrl)->assertForbidden();
        $this->actingAs($this->lecturer)->post($toggleUrl)->assertForbidden();
        $this->actingAs($this->kaprodi)->post($toggleUrl)->assertForbidden();
        $this->actingAs($this->kalab)->post($toggleUrl)->assertForbidden();
    }
}
