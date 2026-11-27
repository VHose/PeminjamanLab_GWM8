<?php

namespace Tests\Feature;

use App\Constants\BookingType;
use App\Enums\BookingDetailStatus;
use App\Enums\BookingStatus;
use App\Models\Booking;
use App\Models\BookingDetail;
use App\Models\Course;
use App\Models\Period;
use App\Models\PeriodHoliday;
use App\Models\Role;
use App\Models\Room;
use App\Models\StudyProgram;
use App\Models\User;
use App\Models\UserRole;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ComprehensiveSeniorQaTest extends TestCase
{
    use RefreshDatabase;

    private Period $period;
    private StudyProgram $prodiIf;
    private StudyProgram $prodiSi;
    private Room $roomAdv1;
    private Room $roomProg1;

    private Role $roleAdmin;
    private Role $roleKalab;
    private Role $roleKaprodi;
    private Role $roleStaff;
    private Role $roleDosen;
    private Role $roleVisitor;

    private User $admin;
    private User $kalab;
    private User $kaprodi;
    private User $staff;
    private User $dosen;
    private User $visitor;

    protected function setUp(): void
    {
        parent::setUp();

        Notification::fake();
        Storage::fake('local');

        StudyProgram::firstOrCreate(
            ['id' => '00'],
            ['name' => 'Umum', 'color_hex' => '#E2E3E5', 'is_active' => true]
        );
        $this->prodiIf = StudyProgram::create([
            'id' => '72',
            'name' => 'S1 Teknik Informatika',
            'color_hex' => '#FFF3B0',
            'is_active' => true,
        ]);
        $this->prodiSi = StudyProgram::create([
            'id' => '73',
            'name' => 'S1 Sistem Informasi',
            'color_hex' => '#D8F3DC',
            'is_active' => true,
        ]);

        $this->period = Period::create([
            'name' => '2026/2027 Ganjil',
            'start_date' => '2026-09-14', // Monday
            'end_date' => '2027-01-29',
            'uts_start' => '2026-11-02',
            'uts_end' => '2026-11-13',
            'uas_start' => '2027-01-18',
            'uas_end' => '2027-01-29',
            'is_active' => true,
        ]);

        $this->roomAdv1 = Room::create([
            'id' => 'ADV1',
            'name' => 'Lab Advance 1',
            'capacity' => 40,
            'is_active' => true,
        ]);
        $this->roomProg1 = Room::create([
            'id' => 'PROG1',
            'name' => 'Lab Programming 1',
            'capacity' => 45,
            'is_active' => true,
        ]);

        $this->roleAdmin = Role::create(['name' => 'Admin']);
        $this->roleKalab = Role::create(['name' => 'Kepala_Lab']);
        $this->roleKaprodi = Role::create(['name' => 'Kepala_Prodi']);
        $this->roleStaff = Role::create(['name' => 'Staf_Lab']);
        $this->roleDosen = Role::create(['name' => 'Dosen']);
        $this->roleVisitor = Role::create(['name' => 'Visitor']);

        $this->admin = $this->makeUser('ADM01', 'Admin Sistem', 'admin@qa.test', $this->roleAdmin);
        $this->kalab = $this->makeUser('KLB01', 'Kepala Lab QA', 'kalab@qa.test', $this->roleKalab, '72');
        $this->kaprodi = $this->makeUser('KPR01', 'Kaprodi QA', 'kaprodi@qa.test', $this->roleKaprodi, '72');
        $this->staff = $this->makeUser('STF01', 'Staf Lab QA', 'staff@qa.test', $this->roleStaff, '72');
        $this->dosen = $this->makeUser('DSN01', 'Dosen QA', 'dosen@qa.test', $this->roleDosen, '72');
        $this->visitor = $this->makeUser('V000001', 'Visitor Aktif QA', 'visitor@qa.test', $this->roleVisitor, '00', 'HIMA IF');
    }

    private function makeUser(
        string $id,
        string $name,
        string $email,
        Role $role,
        string $prodiId = '00',
        ?string $instansi = null
    ): User {
        $user = User::create([
            'id' => $id,
            'study_program_id' => $prodiId,
            'name' => $name,
            'email' => $email,
            'phone' => '081234567890',
            'pic_or_instansi' => $instansi,
            'password' => Hash::make('password123'),
            'status' => 'active',
            'is_active' => true,
            'email_verified_at' => now(),
        ]);

        UserRole::create([
            'user_id' => $user->id,
            'role_id' => $role->id,
            'period_id' => $this->period->id,
        ]);

        return $user;
    }

    /**
     * Find the next Monday-Saturday date at least $minDaysFromNow days ahead.
     */
    private function nextValidBookingDate(int $minDaysFromNow = 3): string
    {
        $date = now()->addDays($minDaysFromNow)->startOfDay();
        if ($date->isSunday()) {
            $date->addDay();
        }

        return $date->toDateString();
    }

    /* =========================================================================
     * 1. LOGIN 2x2 DECISION TABLE + INPUT VALIDATION + LOGOUT
     * ========================================================================= */

    public function test_login_decision_table_all_four_credential_combinations_and_logout(): void
    {
        // Case 1: Email BENAR & Password SALAH -> Gagal login
        $respTrueFalse = $this->from('/login')->post('/login', [
            'email' => 'dosen@qa.test',
            'password' => 'wrong-password-999',
        ]);
        $respTrueFalse->assertRedirect('/login');
        $respTrueFalse->assertSessionHasErrors('email');
        $this->assertGuest();

        // Case 2: Email SALAH & Password BENAR -> Gagal login
        $respFalseTrue = $this->from('/login')->post('/login', [
            'email' => 'tidak-terdaftar@qa.test',
            'password' => 'password123',
        ]);
        $respFalseTrue->assertRedirect('/login');
        $respFalseTrue->assertSessionHasErrors('email');
        $this->assertGuest();

        // Case 3: Email SALAH & Password SALAH -> Gagal login
        $respFalseFalse = $this->from('/login')->post('/login', [
            'email' => 'tidak-terdaftar@qa.test',
            'password' => 'salah-juga-999',
        ]);
        $respFalseFalse->assertRedirect('/login');
        $respFalseFalse->assertSessionHasErrors('email');
        $this->assertGuest();

        // Validation: Email kosong / format salah & Password kosong
        $respInvalidFormat = $this->from('/login')->post('/login', [
            'email' => 'bukan-format-email',
            'password' => '',
        ]);
        $respInvalidFormat->assertRedirect('/login');
        $respInvalidFormat->assertSessionHasErrors(['email', 'password']);
        $this->assertGuest();

        // Case 4: Email BENAR & Password BENAR -> Berhasil login
        $respTrueTrue = $this->post('/login', [
            'email' => 'dosen@qa.test',
            'password' => 'password123',
        ]);
        $respTrueTrue->assertRedirect(route('home'));
        $this->assertAuthenticatedAs($this->dosen);

        // Logout -> Sesi diakhiri kembali menjadi Guest
        $this->post('/logout')->assertRedirect(route('home'));
        $this->assertGuest();
    }

    /* =========================================================================
     * 2. REGISTRASI VISITOR & MATRIKS AKSES 4 KONDISI (VERIFIKASI x APPROVAL)
     * ========================================================================= */

    public function test_visitor_registration_combinatorial_validation_matrix(): void
    {
        // Invalid 1: Konfirmasi password tidak cocok
        $this->from('/register')->post('/register', [
            'name' => 'Calon Visitor',
            'pic_or_instansi' => 'BEM',
            'phone' => '08111222333',
            'email' => 'baru@qa.test',
            'password' => 'password123',
            'password_confirmation' => 'berbeda123',
        ])->assertSessionHasErrors('password');

        // Invalid 2: Password kurang dari 8 karakter
        $this->from('/register')->post('/register', [
            'name' => 'Calon Visitor',
            'pic_or_instansi' => 'BEM',
            'phone' => '08111222333',
            'email' => 'baru@qa.test',
            'password' => '12345',
            'password_confirmation' => '12345',
        ])->assertSessionHasErrors('password');

        // Invalid 3: Email duplikat (sudah dipakai user lain)
        $this->from('/register')->post('/register', [
            'name' => 'Duplikat Email',
            'pic_or_instansi' => 'BEM',
            'phone' => '08111222333',
            'email' => 'visitor@qa.test',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ])->assertSessionHasErrors('email');

        // Invalid 4: Nama kosong & format email tidak valid
        $this->from('/register')->post('/register', [
            'name' => '',
            'email' => 'email-tanpa-domain',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ])->assertSessionHasErrors(['name', 'email']);

        // Valid: Semua input valid -> Akun V000002 terbuat dengan status pending & belum verifikasi email
        $respValid = $this->post('/register', [
            'name' => 'Visitor Valid Baru',
            'pic_or_instansi' => 'UKM Robotika',
            'phone' => '089988776655',
            'email' => 'robotika@qa.test',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);
        $respValid->assertRedirect(route('verification.notice'));

        $created = User::where('email', 'robotika@qa.test')->firstOrFail();
        $this->assertSame('V000002', $created->id);
        $this->assertSame('pending', $created->status);
        $this->assertFalse($created->is_active);
        $this->assertNull($created->email_verified_at);
    }

    public function test_visitor_booking_access_four_state_truth_table(): void
    {
        $v = User::create([
            'id' => 'V000099',
            'name' => 'Matrix Visitor',
            'pic_or_instansi' => 'Komunitas IT',
            'email' => 'matrix@qa.test',
            'password' => Hash::make('password123'),
            'status' => 'pending',
            'is_active' => false,
            'email_verified_at' => null,
        ]);
        $v->forceFill(['email_verified_at' => null])->save();
        UserRole::create(['user_id' => $v->id, 'role_id' => $this->roleVisitor->id, 'period_id' => null]);

        // State 1: Email BELUM verifikasi & Akun PENDING -> Redirect ke verification.notice
        $this->actingAs($v)->get(route('bookings.create'))
            ->assertRedirect(route('verification.notice'));

        // State 2: Email SUDAH verifikasi & Akun PENDING -> Redirect ke bookings.index (warning)
        $v->forceFill(['email_verified_at' => now()])->save();
        $this->actingAs($v->fresh())->get(route('bookings.create'))
            ->assertRedirect(route('bookings.index'))
            ->assertSessionHas('warning');

        // State 3: Email SUDAH verifikasi & Akun DITOLAK (rejected) -> Redirect ke bookings.index (error)
        $this->actingAs($this->staff)->post(route('admin.visitor-approvals.reject', $v), [
            'reason' => 'Data belum lengkap',
        ])->assertRedirect();
        $this->actingAs($v->fresh())->get(route('bookings.create'))
            ->assertRedirect(route('bookings.index'))
            ->assertSessionHas('error');

        // State 4: Email SUDAH verifikasi & Akun DISETUJUI (active) -> Diizinkan membuka form peminjaman (200 OK)
        $this->actingAs($this->kalab)->post(route('admin.visitor-approvals.approve', $v))
            ->assertRedirect();
        $this->actingAs($v->fresh())->get(route('bookings.create'))
            ->assertOk();
    }

    /* =========================================================================
     * 3. GANTI KATA SANDI (DECISION TABLE 4 KOMBINASI)
     * ========================================================================= */

    public function test_change_password_decision_table_combinations(): void
    {
        // Case 1: Password lama SALAH + Password baru valid -> Gagal
        $this->actingAs($this->dosen)->from(route('password.change'))
            ->post(route('password.change.update'), [
                'current_password' => 'salah-lama',
                'password' => 'newpassword123',
                'password_confirmation' => 'newpassword123',
            ])->assertSessionHasErrors('current_password');

        // Case 2: Password lama BENAR + Konfirmasi baru TIDAK COCOK -> Gagal
        $this->actingAs($this->dosen)->from(route('password.change'))
            ->post(route('password.change.update'), [
                'current_password' => 'password123',
                'password' => 'newpassword123',
                'password_confirmation' => 'beda999999',
            ])->assertSessionHasErrors('password');

        // Case 3: Password lama BENAR + Password baru TERLALU PENDEK (<8) -> Gagal
        $this->actingAs($this->dosen)->from(route('password.change'))
            ->post(route('password.change.update'), [
                'current_password' => 'password123',
                'password' => 'short',
                'password_confirmation' => 'short',
            ])->assertSessionHasErrors('password');

        // Case 4: Password lama BENAR + Password baru VALID & COCOK -> Berhasil
        $this->actingAs($this->dosen)
            ->post(route('password.change.update'), [
                'current_password' => 'password123',
                'password' => 'newsecurepass99',
                'password_confirmation' => 'newsecurepass99',
            ])->assertRedirect(route('home'))->assertSessionHas('success');

        $this->assertTrue(Hash::check('newsecurepass99', $this->dosen->fresh()->password));
    }

    /* =========================================================================
     * 4. PENGAJUAN PEMINJAMAN: BOUNDARY VALUE ANALYSIS & KONFLIK JADWAL
     * ========================================================================= */

    public function test_booking_submission_boundary_values_and_conflict_matrix(): void
    {
        // BVA 1: Kurang dari H+2 (besok / H+1) -> Ditolak
        $tomorrow = now()->addDay();
        if ($tomorrow->isSunday()) {
            $tomorrow = now(); // hari ini (Sabtu) juga < H+2
        }
        $this->actingAs($this->visitor)->post(route('bookings.store'), [
            'pic_name' => 'Visitor QA',
            'pic_phone' => '08123456789',
            'purpose' => 'Uji H+1',
            'slots' => [
                ['room_id' => 'ADV1', 'date' => $tomorrow->toDateString(), 'start' => '08:00', 'end' => '10:00'],
            ],
        ])->assertSessionHasErrors('slots');

        // BVA 2: Hari Minggu (>= H+2) -> Ditolak
        $nextSunday = now()->addDays(2)->next(\Carbon\Carbon::SUNDAY)->toDateString();
        $this->actingAs($this->visitor)->post(route('bookings.store'), [
            'pic_name' => 'Visitor QA',
            'pic_phone' => '08123456789',
            'purpose' => 'Uji Hari Minggu',
            'slots' => [
                ['room_id' => 'ADV1', 'date' => $nextSunday, 'start' => '09:00', 'end' => '11:00'],
            ],
        ])->assertSessionHasErrors('slots');

        $validDate = $this->nextValidBookingDate(4);

        // BVA 3: Di luar jam operasional sebelum 07:00 (06:30 - 08:00) -> Ditolak
        $this->actingAs($this->visitor)->post(route('bookings.store'), [
            'pic_name' => 'Visitor QA',
            'pic_phone' => '08123456789',
            'purpose' => 'Uji Jam Pagi',
            'slots' => [
                ['room_id' => 'ADV1', 'date' => $validDate, 'start' => '06:30', 'end' => '08:00'],
            ],
        ])->assertSessionHasErrors('slots');

        // BVA 4: Di luar jam operasional setelah 22:00 (21:00 - 22:30) -> Ditolak
        $this->actingAs($this->visitor)->post(route('bookings.store'), [
            'pic_name' => 'Visitor QA',
            'pic_phone' => '08123456789',
            'purpose' => 'Uji Jam Malam',
            'slots' => [
                ['room_id' => 'ADV1', 'date' => $validDate, 'start' => '21:00', 'end' => '22:30'],
            ],
        ])->assertSessionHasErrors('slots');

        // BVA 5: Jam selesai <= jam mulai (10:00 - 09:00) -> Ditolak
        $this->actingAs($this->visitor)->post(route('bookings.store'), [
            'pic_name' => 'Visitor QA',
            'pic_phone' => '08123456789',
            'purpose' => 'Uji Jam Terbalik',
            'slots' => [
                ['room_id' => 'ADV1', 'date' => $validDate, 'start' => '10:00', 'end' => '09:00'],
            ],
        ])->assertSessionHasErrors('slots.0.end');

        // BVA 6: Ekstensi lampiran berbahaya (.exe) -> Ditolak
        $badFile = UploadedFile::fake()->create('malware.exe', 100, 'application/octet-stream');
        $this->actingAs($this->visitor)->post(route('bookings.store'), [
            'pic_name' => 'Visitor QA',
            'pic_phone' => '08123456789',
            'purpose' => 'Uji File Exe',
            'slots' => [
                ['room_id' => 'ADV1', 'date' => $validDate, 'start' => '08:00', 'end' => '10:00'],
            ],
            'attachments' => [$badFile],
        ])->assertSessionHasErrors('attachments.0');

        // Valid Boundary: Tepat jam buka (07:00 - 10:00) dengan lampiran PDF valid -> Berhasil!
        $pdfFile = UploadedFile::fake()->create('surat-izin.pdf', 200, 'application/pdf');
        $respOk = $this->actingAs($this->visitor)->post(route('bookings.store'), [
            'pic_name' => 'Visitor QA',
            'pic_phone' => '08123456789',
            'purpose' => 'Workshop Pagi',
            'slots' => [
                ['room_id' => 'ADV1', 'date' => $validDate, 'start' => '07:00', 'end' => '10:00'],
            ],
            'attachments' => [$pdfFile],
        ]);
        $booking = Booking::latest('id')->firstOrFail();
        $respOk->assertRedirect(route('bookings.show', $booking));
        $this->assertSame(BookingStatus::PENDING_KAPRODI, $booking->status);

        // Buat booking approved pada PROG1 (13:00 - 15:00) lalu uji penolakan konflik di slot beririsan (14:00 - 16:00)
        $approvedExisting = Booking::create([
            'user_id' => $this->staff->id,
            'pic_name' => 'Staf QA',
            'pic_phone' => '08123456789',
            'purpose' => 'Kegiatan Disetujui',
            'status' => BookingStatus::APPROVED,
        ]);
        $approvedExisting->details()->create([
            'room_id' => 'PROG1',
            'start_datetime' => "{$validDate} 13:00:00",
            'end_datetime' => "{$validDate} 15:00:00",
            'status' => BookingDetailStatus::APPROVED,
        ]);

        $this->actingAs($this->dosen)->post(route('bookings.store'), [
            'pic_name' => 'Dosen QA',
            'pic_phone' => '081299998888',
            'purpose' => 'Bentrok Jadwal',
            'slots' => [
                ['room_id' => 'PROG1', 'date' => $validDate, 'start' => '14:00', 'end' => '16:00'],
            ],
        ])->assertSessionHasErrors('slots');
    }

    /* =========================================================================
     * 5. ALUR PERSETUJUAN 2 TAHAP, RESCHEDULE, DAN PEMBATALAN
     * ========================================================================= */

    public function test_approval_reschedule_and_cancellation_decision_matrix(): void
    {
        $validDate = $this->nextValidBookingDate(5);

        $booking = Booking::create([
            'user_id' => $this->visitor->id,
            'type' => BookingType::NEW_BOOKING,
            'pic_name' => 'Visitor QA',
            'pic_phone' => '081234567890',
            'purpose' => 'Pelatihan Multi Lab',
            'status' => BookingStatus::PENDING_KAPRODI,
        ]);
        $detailAdv = $booking->details()->create([
            'room_id' => 'ADV1',
            'start_datetime' => "{$validDate} 08:00:00",
            'end_datetime' => "{$validDate} 10:00:00",
            'status' => BookingDetailStatus::PENDING,
        ]);
        $detailProg = $booking->details()->create([
            'room_id' => 'PROG1',
            'start_datetime' => "{$validDate} 10:00:00",
            'end_datetime' => "{$validDate} 12:00:00",
            'status' => BookingDetailStatus::PENDING,
        ]);

        // Unauthorized role (Dosen) mencoba menyetujui tahap Kaprodi -> 403
        $this->actingAs($this->dosen)->post(route('approvals.decideKaprodi', $booking), [
            'status' => 'approved',
        ])->assertForbidden();

        // Kaprodi menolak TANPA alasan -> Gagal validasi notes
        $this->actingAs($this->kaprodi)->post(route('approvals.decideKaprodi', $booking), [
            'status' => 'rejected',
            'notes' => '',
        ])->assertSessionHasErrors('notes');

        // Kaprodi menyetujui -> Status naik ke PENDING_KALAB
        $this->actingAs($this->kaprodi)->post(route('approvals.decideKaprodi', $booking), [
            'status' => 'approved',
        ])->assertRedirect();
        $this->assertSame(BookingStatus::PENDING_KALAB, $booking->fresh()->status);

        // Kalab menyetujui ADV1 dan menolak PROG1 (dengan alasan) -> Booking menjadi APPROVED parsial
        $this->actingAs($this->kalab)->post(route('approvals.decideKalabDetail', $detailAdv), [
            'status' => 'approved',
        ])->assertRedirect();
        $this->assertSame(BookingStatus::PENDING_KALAB, $booking->fresh()->status);

        $this->actingAs($this->kalab)->post(route('approvals.decideKalabDetail', $detailProg), [
            'status' => 'rejected',
            'notes' => 'Dipakai maintenance',
        ])->assertRedirect();
        $this->assertSame(BookingStatus::APPROVED, $booking->fresh()->status);

        // Non-owner mencoba membatalkan -> 403
        $this->actingAs($this->dosen)->post(route('bookings.cancel', $booking))->assertForbidden();

        // Owner mengajukan Reschedule (Ubah Jadwal) H-5 -> Masuk PENDING_KALAB langsung
        $newDate = $this->nextValidBookingDate(7);
        $this->actingAs($this->visitor)->post(route('bookings.store'), [
            'type' => 'change',
            'parent_booking_id' => $booking->id,
            'notes' => 'Perubahan tanggal narasumber',
            'slots' => [
                ['room_id' => 'PROG1', 'date' => $newDate, 'start' => '13:00', 'end' => '15:00'],
            ],
        ])->assertRedirect();

        $rescheduleBooking = Booking::where('parent_booking_id', $booking->id)->firstOrFail();
        $this->assertSame(BookingType::RESCHEDULE, (int) $rescheduleBooking->type);
        $this->assertSame(BookingStatus::PENDING_KALAB, $rescheduleBooking->status);

        // Saat Kalab menyetujui reschedule, booking induk otomatis DIBATALKAN
        $rescheduleDetail = $rescheduleBooking->details()->firstOrFail();
        \Illuminate\Support\Facades\DB::table('booking_detail')
            ->where('booking_id', $rescheduleBooking->id)
            ->update(['booking_date' => $newDate]);
        $rescheduleDetail = $rescheduleBooking->details()->firstOrFail();

        $this->actingAs($this->kalab)->post(route('approvals.decideKalabDetail', $rescheduleDetail), [
            'status' => 'approved',
        ])->assertRedirect();

        $this->assertSame(BookingStatus::APPROVED, $rescheduleBooking->fresh()->status);
        $this->assertSame(BookingStatus::CANCELLED, $booking->fresh()->status);

        // Owner membatalkan reschedule sebelum H-2 -> Berhasil CANCELLED
        $this->actingAs($this->visitor)->post(route('bookings.cancel', $rescheduleBooking))
            ->assertRedirect();
        $this->assertSame(BookingStatus::CANCELLED, $rescheduleBooking->fresh()->status);
    }

    /* =========================================================================
     * 6. MATRIKS OTORISASI RBAC LINTAS 6 PERAN
     * ========================================================================= */

    public function test_rbac_access_matrix_across_all_protected_modules(): void
    {
        $matrix = [
            // Route => [Admin, Kalab, Kaprodi, Staf, Dosen, Visitor]
            route('admin.periods.index')           => [200, 403, 403, 403, 403, 403],
            route('master.index', 'courses')       => [200, 403, 403, 403, 403, 403],
            route('admin.users.index')             => [200, 200, 403, 403, 403, 403],
            route('admin.schedules.import')        => [200, 200, 403, 403, 403, 403],
            route('approvals.index')               => [403, 200, 200, 403, 403, 403],
            route('admin.visitor-approvals.index') => [200, 200, 403, 200, 403, 403],
            route('logs.index')                    => [200, 200, 200, 200, 403, 403],
            route('bookings.index')                => [200, 200, 200, 200, 200, 200],
        ];

        $actors = [
            $this->admin,
            $this->kalab,
            $this->kaprodi,
            $this->staff,
            $this->dosen,
            $this->visitor,
        ];

        foreach ($matrix as $url => $expectedCodes) {
            foreach ($actors as $idx => $actor) {
                $response = $this->actingAs($actor)->get($url);
                $this->assertSame(
                    $expectedCodes[$idx],
                    $response->getStatusCode(),
                    "RBAC mismatch pada URL {$url} untuk aktor {$actor->id}"
                );
            }
        }
    }

    /* =========================================================================
     * 7. MASTER DATA CRUD, VALIDASI BOUNDARY, DAN TOGGLE AKTIF/NONAKTIF
     * ========================================================================= */

    public function test_master_data_crud_validation_and_toggle_matrix(): void
    {
        // 1. Study Programs: hex invalid ditolak, duplikat ID ditolak, valid diterima
        $this->actingAs($this->admin)->post(route('master.store', 'study-programs'), [
            'id' => '79',
            'name' => 'Magister Ilmu Komputer',
            'color_hex' => 'BUKANHEX',
        ])->assertSessionHasErrors('color_hex');

        $this->actingAs($this->admin)->post(route('master.store', 'study-programs'), [
            'id' => '72', // sudah ada
            'name' => 'Prodi Duplikat',
            'color_hex' => '#AABBCC',
        ])->assertSessionHasErrors('id');

        $this->actingAs($this->admin)->post(route('master.store', 'study-programs'), [
            'id' => '79',
            'name' => 'S2 Ilmu Komputer',
            'color_hex' => '#CCE5FF',
            'is_active' => 1,
        ])->assertRedirect(route('master.index', 'study-programs'));
        $this->assertDatabaseHas('study_program', ['id' => '79', 'is_active' => true]);

        // 2. Rooms: kapasitas di luar batas (0 dan 250) ditolak, kapasitas valid (1..200) diterima
        $this->actingAs($this->admin)->post(route('master.store', 'rooms'), [
            'id' => 'MMD1',
            'name' => 'Lab Multimedia',
            'capacity' => 0,
        ])->assertSessionHasErrors('capacity');

        $this->actingAs($this->admin)->post(route('master.store', 'rooms'), [
            'id' => 'MMD1',
            'name' => 'Lab Multimedia',
            'capacity' => 250,
        ])->assertSessionHasErrors('capacity');

        $this->actingAs($this->admin)->post(route('master.store', 'rooms'), [
            'id' => 'MMD1',
            'name' => 'Lab Multimedia',
            'capacity' => 35,
            'is_active' => 1,
        ])->assertRedirect(route('master.index', 'rooms'));

        // 3. Courses: prodi tidak valid ditolak, valid diterima, update & toggle berfungsi
        $this->actingAs($this->admin)->post(route('master.store', 'courses'), [
            'id' => 'IF999',
            'study_program_id' => '99', // tidak ada
            'name' => 'Pengujian Perangkat Lunak',
        ])->assertSessionHasErrors('study_program_id');

        $this->actingAs($this->admin)->post(route('master.store', 'courses'), [
            'id' => 'IF999',
            'study_program_id' => '72',
            'name' => 'Pengujian Perangkat Lunak',
            'is_active' => 1,
        ])->assertRedirect(route('master.index', 'courses'));

        $this->actingAs($this->admin)->put(route('master.update', ['courses', 'IF999']), [
            'study_program_id' => '73',
            'name' => 'Software Quality Assurance',
            'is_active' => 1,
        ])->assertRedirect(route('master.index', 'courses'));
        $this->assertDatabaseHas('course', ['id' => 'IF999', 'study_program_id' => '73', 'name' => 'Software Quality Assurance']);

        $this->actingAs($this->admin)->post(route('master.toggle', ['courses', 'IF999']))->assertRedirect();
        $this->assertFalse(Course::findOrFail('IF999')->is_active);

        $this->actingAs($this->admin)->post(route('master.toggle', ['courses', 'IF999']))->assertRedirect();
        $this->assertTrue(Course::findOrFail('IF999')->is_active);
    }

    /* =========================================================================
     * 8. PERIODE SEMESTER, HARI LIBUR, DAN KELOLA PENGGUNA
     * ========================================================================= */

    public function test_period_and_holiday_validation_and_single_active_period_rule(): void
    {
        // Mulai hari Selasa (2027-02-02) -> Ditolak karena wajib Senin
        $this->actingAs($this->admin)->post(route('admin.periods.store'), [
            'name' => '2026/2027 Genap',
            'start_date' => '2027-02-02', // Tuesday
            'end_date' => '2027-06-18',
        ])->assertSessionHasErrors('start_date');

        // Mulai hari Senin (2027-02-01) & jadikan aktif -> Periode lama otomatis nonaktif
        $this->actingAs($this->admin)->post(route('admin.periods.store'), [
            'name' => '2026/2027 Genap',
            'start_date' => '2027-02-01', // Monday
            'end_date' => '2027-06-18',
            'uts_start' => '2027-03-22',
            'uts_end' => '2027-04-02',
            'uas_start' => '2027-06-07',
            'uas_end' => '2027-06-18',
            'is_active' => 1,
        ])->assertRedirect(route('admin.periods.index'));

        $newPeriod = Period::where('name', '2026/2027 Genap')->firstOrFail();
        $this->assertTrue($newPeriod->is_active);
        $this->assertFalse($this->period->fresh()->is_active);

        // Berikan peran Admin pada periode aktif baru agar otorisasi periode aktif terpenuhi
        UserRole::create([
            'user_id' => $this->admin->id,
            'role_id' => $this->roleAdmin->id,
            'period_id' => $newPeriod->id,
        ]);

        // Tambah hari libur valid & uji penolakan hari libur yang tumpang tindih
        $this->actingAs($this->admin)->post(route('admin.holidays.store', $newPeriod), [
            'period_id' => $newPeriod->id,
            'start_date' => '2027-03-10',
            'end_date' => '2027-03-12',
            'note' => 'Libur Nyepi',
        ])->assertRedirect();
        $this->assertSame(1, PeriodHoliday::where('period_id', $newPeriod->id)->count());

        $this->actingAs($this->admin)->post(route('admin.holidays.store', $newPeriod), [
            'period_id' => $newPeriod->id,
            'start_date' => '2027-03-11',
            'end_date' => '2027-03-13',
            'note' => 'Bentrok Libur',
        ])->assertSessionHasErrors();
    }

    public function test_user_management_crud_reset_password_roles_and_self_deactivation_guard(): void
    {
        // Tambah pengguna baru: ID duplikat ditolak
        $this->actingAs($this->admin)->post(route('admin.users.store'), [
            'id' => 'DSN01',
            'name' => 'Duplikat NIK',
            'email' => 'unik@qa.test',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ])->assertSessionHasErrors('id');

        // Tambah pengguna baru valid
        $this->actingAs($this->admin)->post(route('admin.users.store'), [
            'id' => 'DSN99',
            'study_program_id' => '72',
            'name' => 'Dosen Baru QA',
            'email' => 'dsn99@qa.test',
            'phone' => '081200990099',
            'role_id' => $this->roleDosen->id,
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ])->assertRedirect(route('admin.users.index'));

        $dsn99 = User::findOrFail('DSN99');

        // Reset password pengguna oleh non-Admin (Kalab) -> 403, oleh Admin konfirmasi salah -> error, konfirmasi cocok -> berhasil
        $this->actingAs($this->kalab)->post(route('admin.users.resetPassword', $dsn99), [
            'password' => 'resetpass88',
            'password_confirmation' => 'resetpass88',
        ])->assertForbidden();

        $this->actingAs($this->admin)->post(route('admin.users.resetPassword', $dsn99), [
            'password' => 'resetpass88',
            'password_confirmation' => 'salahketik88',
        ])->assertSessionHasErrors('password');

        $this->actingAs($this->admin)->post(route('admin.users.resetPassword', $dsn99), [
            'password' => 'resetpass88',
            'password_confirmation' => 'resetpass88',
        ])->assertRedirect();
        $this->assertTrue(Hash::check('resetpass88', $dsn99->fresh()->password));

        // Guard: Admin TIDAK BOLEH menonaktifkan akunnya sendiri
        $this->actingAs($this->admin)->post(route('admin.users.toggle', $this->admin))
            ->assertRedirect()
            ->assertSessionHas('error');
        $this->assertTrue($this->admin->fresh()->is_active);

        // Menonaktifkan pengguna lain berhasil
        $this->actingAs($this->admin)->post(route('admin.users.toggle', $dsn99))->assertRedirect();
        $this->assertFalse($dsn99->fresh()->is_active);

        // Update peran multi-role pada periode aktif
        $this->actingAs($this->kalab)->put(route('admin.period-roles.update-user', [$this->period->id, $dsn99->id]), [
            'role_ids' => [$this->roleDosen->id, $this->roleKaprodi->id],
        ])->assertRedirect();

        $assignedRoleIds = UserRole::where('user_id', $dsn99->id)
            ->where('period_id', $this->period->id)
            ->pluck('role_id')
            ->all();
        $this->assertEqualsCanonicalizing([$this->roleDosen->id, $this->roleKaprodi->id], $assignedRoleIds);
    }

    /* =========================================================================
     * 9. AVAILABILITY ENDPOINT & REGRESI SELURUH REVISI TAMPILAN UI
     * ========================================================================= */

    public function test_availability_endpoint_returns_unavailable_and_pending_ranges(): void
    {
        $date = $this->nextValidBookingDate(6);

        $approved = Booking::create([
            'user_id' => $this->dosen->id,
            'pic_name' => 'Dosen QA',
            'pic_phone' => '08123',
            'purpose' => 'Kuliah Tambahan',
            'status' => BookingStatus::APPROVED,
        ]);
        $approved->details()->create([
            'room_id' => 'ADV1',
            'start_datetime' => "{$date} 08:00:00",
            'end_datetime' => "{$date} 10:00:00",
            'status' => BookingDetailStatus::APPROVED,
        ]);

        $pending = Booking::create([
            'user_id' => $this->visitor->id,
            'pic_name' => 'Visitor QA',
            'pic_phone' => '08123',
            'purpose' => 'Rapat HIMA',
            'status' => BookingStatus::PENDING_KAPRODI,
        ]);
        $pending->details()->create([
            'room_id' => 'ADV1',
            'start_datetime' => "{$date} 13:00:00",
            'end_datetime' => "{$date} 15:00:00",
            'status' => BookingDetailStatus::PENDING,
        ]);

        // Tanpa parameter -> validasi gagal
        $this->getJson(route('availability'))->assertStatus(422);

        // Parameter valid -> JSON berisi slot unavailable dan pending
        $resp = $this->getJson(route('availability', ['room_id' => 'ADV1', 'date' => $date]));
        $resp->assertOk()
            ->assertJsonCount(1, 'unavailable')
            ->assertJsonCount(1, 'pending');
    }

    public function test_ui_revisions_are_applied_consistently_across_views(): void
    {
        // 1. Riwayat Peminjaman: tanpa footer, tanpa '-- Semua', tanpa kolom Tipe & tombol Filter
        $bookingsPage = $this->actingAs($this->admin)->get(route('bookings.index'));
        $bookingsPage->assertOk()
            ->assertSee('Semua Periode')
            ->assertSee('Semua Ruangan')
            ->assertSee('Semua Status')
            ->assertDontSee('-- Semua')
            ->assertDontSee('Gedung Widya Maranatha (GWM) Lantai 8')
            ->assertDontSee('>Filter</button>', false);

        // 2. Persetujuan Peminjaman: ada filter periode/ruangan/status sisi klien, tanpa form search atas
        $approvalsPage = $this->actingAs($this->kalab)->get(route('approvals.index'));
        $approvalsPage->assertOk()
            ->assertSee('id="approvalPeriodFilter"', false)
            ->assertSee('id="approvalRoomFilter"', false)
            ->assertSee('id="approvalStatusFilter"', false)
            ->assertDontSee('id="approvalSearchInput"', false);

        // 3. Persetujuan Akun Visitor: tanpa 4 kartu ringkasan atas, infoFiltered kosong
        $visitorPage = $this->actingAs($this->kalab)->get(route('admin.visitor-approvals.index'));
        $visitorPage->assertOk()
            ->assertDontSee('Semua Visitor')
            ->assertSee('infoFiltered: ""', false)
            ->assertSee('searchable: false, targets: [2, 3, 4, 5, 6, 7]', false);

        // 4. Kelola Pengguna: tanpa kalimat petunjuk centang peran di modal
        $usersPage = $this->actingAs($this->admin)->get(route('admin.users.index'));
        $usersPage->assertOk()
            ->assertDontSee('Centang peran yang dimiliki pengguna ini untuk periode')
            ->assertSee('targets: [3, 4, 5, 6]', false);

        // 5. Master Mata Kuliah: ada filter Program Studi & Status, tanpa 'Filter Prodi:' sejajar judul
        $coursesPage = $this->actingAs($this->admin)->get(route('master.index', 'courses'));
        $coursesPage->assertOk()
            ->assertSee('id="courseProdiFilter"', false)
            ->assertSee('id="courseStatusFilter"', false)
            ->assertDontSee('Filter Prodi:');

        // 6. Periode Semester & Edit Periode: ordering: false, Libur (0) non-link, tanpa tombol Kembali & btn-outline-info
        $periodsPage = $this->actingAs($this->admin)->get(route('admin.periods.index'));
        $periodsPage->assertOk()
            ->assertSee('ordering: false', false)
            ->assertSee('<span>Libur (0)</span>', false);

        $periodEditPage = $this->actingAs($this->admin)->get(route('admin.periods.edit', $this->period));
        $periodEditPage->assertOk()
            ->assertDontSee('>Kembali</a>', false)
            ->assertDontSee('btn-outline-info', false);

        // 7. Import Jadwal & Registrasi: tanpa alert-info hijau/biru pastel dan tanpa <code> merah
        $importPage = $this->actingAs($this->admin)->get(route('admin.schedules.import'));
        $importPage->assertOk()
            ->assertDontSee('alert-info', false)
            ->assertDontSee('<code>', false);

        // Saat sudah login, halaman register me-redirect (guest middleware), dan saat guest tampil tanpa alert-info
        $this->get(route('register'))->assertRedirect();
        auth()->logout();

        $registerPage = $this->get(route('register'));
        $registerPage->assertOk()
            ->assertDontSee('alert-info', false);
    }
}
