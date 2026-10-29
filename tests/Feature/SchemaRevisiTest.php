<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\BookingAttachment;
use App\Models\BookingDetail;
use App\Models\Course;
use App\Models\Period;
use App\Models\Room;
use App\Models\Schedule;
use App\Models\ScheduleSession;
use App\Models\StudyProgram;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class SchemaRevisiTest extends TestCase
{
    use RefreshDatabase;

    private Period $period;
    private Room $room;
    private Course $course;
    private User $dosen;

    protected function setUp(): void
    {
        parent::setUp();

        StudyProgram::firstOrCreate(
            ['id' => '00'],
            [
                'name' => 'Undefined',
                'color_hex' => '#E2E3E5',
                'is_active' => true,
            ]
        );

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

        $this->room = Room::create([
            'id' => 'ADV1',
            'name' => 'Lab Advance 1',
            'capacity' => 40,
            'is_active' => true,
        ]);

        $this->course = Course::create([
            'id' => 'IF101',
            'study_program_id' => '00',
            'name' => 'Pemrograman Dasar',
            'is_active' => true,
        ]);

        $this->dosen = User::create([
            'id' => 'DSN001',
            'study_program_id' => '00',
            'name' => str_repeat('A', 100),
            'email' => 'dosen@example.com',
            'password' => Hash::make('password'),
            'is_active' => true,
        ]);
    }

    public function test_user_schema_revisions(): void
    {
        $this->assertEquals(100, strlen($this->dosen->name));
        $this->assertEquals('00', $this->dosen->study_program_id);

        // Foreign key study_program_id tidak boleh null
        $this->expectException(\Throwable::class);
        DB::table('user')->insert([
            'id' => 'ERR001',
            'study_program_id' => null,
            'name' => 'Error Null Prodi',
            'email' => 'error@example.com',
            'password' => 'secret',
            'is_active' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function test_schedule_notes_and_sks(): void
    {
        $schedule = Schedule::create([
            'period_id' => $this->period->id,
            'course_id' => $this->course->id,
            'class_code' => 'A',
            'type' => 0,
            'day_of_week' => 1,
            'user_id' => $this->dosen->id,
            'room_id' => $this->room->id,
            'start_time' => '08:00:00',
            'end_time' => '10:00:00',
            'notes' => 'Kelas gabungan Teknik Informatika dan Sistem Informasi',
            'sks' => '3+1',
        ]);

        $schedule->refresh();
        $this->assertEquals('Kelas gabungan Teknik Informatika dan Sistem Informasi', $schedule->notes);
        $this->assertEquals('3+1', $schedule->sks);
    }

    public function test_schedule_session_columns_not_null(): void
    {
        $schedule = Schedule::create([
            'period_id' => $this->period->id,
            'course_id' => $this->course->id,
            'class_code' => 'A',
            'type' => 0,
            'day_of_week' => 1,
            'user_id' => $this->dosen->id,
            'room_id' => $this->room->id,
            'start_time' => '08:00:00',
            'end_time' => '10:00:00',
        ]);

        $session = ScheduleSession::create([
            'schedule_id' => $schedule->id,
            'week_no' => 1,
            'session_date' => '2026-09-14',
            'is_active' => true,
            'room_id' => $schedule->room_id,
            'start_time' => $schedule->start_time,
            'end_time' => $schedule->end_time,
            'note' => 'Pertemuan perdana',
        ]);

        $session->refresh();
        $this->assertNotNull($session->room_id);
        $this->assertNotNull($session->start_time);
        $this->assertNotNull($session->end_time);
        $this->assertEquals('ADV1', $session->room_id);
        $this->assertEquals('08:00:00', $session->start_time);
        $this->assertEquals('10:00:00', $session->end_time);
        $this->assertEquals('Pertemuan perdana', $session->note);
    }

    public function test_booking_detail_composite_primary_key_without_room_id(): void
    {
        $booking = Booking::create([
            'user_id' => $this->dosen->id,
            'pic_name' => 'PIC Acara',
            'pic_phone' => '081234567890',
            'purpose' => 'Seminar Teknologi',
        ]);

        $detail = BookingDetail::create([
            'booking_id' => $booking->id,
            'room_id' => $this->room->id,
            'booking_date' => '2026-09-15',
            'start_time' => '09:00:00',
            'end_time' => '11:00:00',
        ]);

        $this->assertDatabaseHas('booking_detail', [
            'booking_id' => $booking->id,
            'start_time' => '09:00:00',
            'room_id' => 'ADV1',
        ]);

        $found = BookingDetail::where('booking_id', $booking->id)
            ->where('start_time', '09:00:00')
            ->first();
        $this->assertNotNull($found);
        $this->assertEquals('ADV1', $found->room->id);
        $this->assertEquals('2026-09-15', $found->booking_date->toDateString());
    }

    public function test_booking_attachment_weak_entity_composite_pk(): void
    {
        $booking = Booking::create([
            'user_id' => $this->dosen->id,
            'pic_name' => 'PIC Acara',
            'pic_phone' => '081234567890',
            'purpose' => 'Seminar Teknologi',
        ]);

        $att1 = BookingAttachment::create([
            'booking_id' => $booking->id,
            'original_name' => 'Proposal.pdf',
        ]);

        $att2 = BookingAttachment::create([
            'booking_id' => $booking->id,
            'original_name' => 'Surat_Izin.pdf',
        ]);

        $this->assertDatabaseHas('booking_attachment', [
            'booking_id' => $booking->id,
            'original_name' => 'Proposal.pdf',
        ]);
        $this->assertDatabaseHas('booking_attachment', [
            'booking_id' => $booking->id,
            'original_name' => 'Surat_Izin.pdf',
        ]);

        $this->assertCount(2, $booking->attachments);
    }

    public function test_booking_created_by_column_removed(): void
    {
        $booking = Booking::create([
            'user_id' => $this->dosen->id,
            'pic_name' => 'PIC Acara',
            'pic_phone' => '081234567890',
            'purpose' => 'Seminar Teknologi',
        ]);

        $this->assertArrayNotHasKey('created_by', $booking->getAttributes());
        $this->assertEquals($this->dosen->id, $booking->user->id);
    }
}
