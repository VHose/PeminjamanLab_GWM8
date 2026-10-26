<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdateScheduleSessionRequest;
use App\Models\ActivityLog;
use App\Models\Room;
use App\Models\ScheduleSession;
use App\Services\BookingService;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class ScheduleSessionController extends Controller
{
    /**
     * Membatalkan (meliburkan) atau mengaktifkan kembali satu pertemuan kuliah langsung dari tabel jadwal.
     */
    public function toggle(
        Request $request,
        int $scheduleId,
        int $weekNo,
        BookingService $bookingService
    ): RedirectResponse {
        $session = ScheduleSession::with(['schedule.course', 'schedule.lecturer'])
            ->where('schedule_id', $scheduleId)
            ->where('week_no', $weekNo)
            ->firstOrFail();

        $courseName = $session->schedule?->course?->name ?? 'Kuliah';
        $classCode = $session->schedule?->class_code ?? '';
        $dateFormatted = $session->session_date->format('d/m/Y');

        if ($session->is_active) {
            $request->validate([
                'note' => ['nullable', 'string', 'max:100'],
            ]);

            // Aksi: Batalkan / Liburkan pertemuan kuliah
            $note = $request->input('note') ?: 'Kuliah diliburkan.';

            $session->update([
                'is_active' => false,
                'note' => $note,
            ]);

            ActivityLog::record(
                $request->user()->id,
                'cancel_session',
                'schedule_session',
                "{$scheduleId}-{$weekNo}",
                "Meliburkan pertemuan ke-{$weekNo} {$courseName} Kelas {$classCode} pada tanggal {$dateFormatted}. Catatan: {$note}",
                $request->ip()
            );

            return back()->with('success', "Pertemuan ke-{$weekNo} {$courseName} Kelas {$classCode} pada {$dateFormatted} berhasil diliburkan.");
        }

        // Aksi: Aktifkan kembali pertemuan kuliah
        // Periksa apakah slot bentrok dengan booking approved atau jadwal kuliah lain
        $conflictMessage = $bookingService->checkSessionConflict($session);

        if ($conflictMessage !== null) {
            return back()->withErrors([
                'error' => "Tidak dapat mengaktifkan pertemuan ke-{$weekNo} {$courseName} Kelas {$classCode}: {$conflictMessage}",
            ]);
        }

        $session->update([
            'is_active' => true,
            'note' => null,
        ]);

        ActivityLog::record(
            $request->user()->id,
            'activate_session',
            'schedule_session',
            "{$scheduleId}-{$weekNo}",
            "Mengaktifkan kembali pertemuan ke-{$weekNo} {$courseName} Kelas {$classCode} pada tanggal {$dateFormatted}.",
            $request->ip()
        );

        return back()->with('success', "Pertemuan ke-{$weekNo} {$courseName} Kelas {$classCode} pada {$dateFormatted} berhasil diaktifkan kembali.");
    }

    /**
     * Memperbarui override ruangan dan/atau waktu untuk satu sesi pertemuan kuliah.
     */
    public function update(
        UpdateScheduleSessionRequest $request,
        int $scheduleId,
        int $weekNo,
        BookingService $bookingService
    ): RedirectResponse {
        $session = ScheduleSession::with(['schedule.course', 'schedule.lecturer', 'schedule.room', 'room'])
            ->where('schedule_id', $scheduleId)
            ->where('week_no', $weekNo)
            ->firstOrFail();

        $courseName = $session->schedule?->course?->name ?? 'Kuliah';
        $classCode = $session->schedule?->class_code ?? '';
        $dateFormatted = $session->session_date->format('d/m/Y');

        // 1. Reset override jika clear_override == true
        if ($request->boolean('clear_override')) {
            $defaultDate = $session->defaultSessionDate() ?? $session->session_date->toDateString();

            $session->update([
                'session_date' => $defaultDate,
                'room_id'      => null,
                'start_time'   => null,
                'end_time'     => null,
                'note'         => null,
            ]);

            ActivityLog::record(
                $request->user()->id,
                'reset_session_override',
                'schedule_session',
                "{$scheduleId}-{$weekNo}",
                "Reset override pertemuan ke-{$weekNo} {$courseName} Kelas {$classCode} ({$dateFormatted}) ke jadwal induk.",
                $request->ip()
            );

            return back()->with('success', "Override pertemuan ke-{$weekNo} {$courseName} Kelas {$classCode} ({$dateFormatted}) berhasil direset ke jadwal induk.");
        }

        // 2. Ambil input override
        $targetDate = $request->filled('session_date') ? $request->input('session_date') : $session->session_date->toDateString();
        $targetRoomId = $request->filled('room_id') ? $request->input('room_id') : null;
        $targetStartTime = $request->filled('start_time') ? $request->input('start_time') : null;
        $targetEndTime = $request->filled('end_time') ? $request->input('end_time') : null;
        $note = $request->filled('note') ? $request->input('note') : null;

        // 3. Validasi konflik jadwal via BookingService
        $conflict = $bookingService->checkSessionConflict(
            $session,
            $targetRoomId,
            $targetDate,
            $targetStartTime,
            $targetEndTime
        );

        if ($conflict !== null) {
            return back()->withErrors(['error' => $conflict])->withInput();
        }

        // Format waktu ke HH:mm:ss jika ada
        $formattedStartTime = $targetStartTime ? (strlen($targetStartTime) === 5 ? $targetStartTime . ':00' : $targetStartTime) : null;
        $formattedEndTime = $targetEndTime ? (strlen($targetEndTime) === 5 ? $targetEndTime . ':00' : $targetEndTime) : null;

        $originalDateStr = $session->session_date->toDateString();

        // 4. Simpan override
        $session->update([
            'session_date' => $targetDate,
            'room_id'      => $targetRoomId,
            'start_time'   => $formattedStartTime,
            'end_time'     => $formattedEndTime,
            'note'         => $note,
        ]);

        // 5. Catat ActivityLog
        $descParts = [];
        if ($targetDate !== $originalDateStr) {
            $newDateCarbon = Carbon::parse($targetDate);
            $dayName = $newDateCarbon->locale('id')->isoFormat('dddd');
            $descParts[] = "hari/tanggal → {$dayName}, {$newDateCarbon->format('d/m/Y')}";
        }
        if ($targetRoomId) {
            $newRoomName = Room::find($targetRoomId)?->name ?? $targetRoomId;
            $descParts[] = "ruangan → {$newRoomName}";
        }
        if ($targetStartTime && $targetEndTime) {
            $descParts[] = "waktu → {$targetStartTime}–{$targetEndTime}";
        }
        if ($note) {
            $descParts[] = "Catatan: {$note}";
        }
        $changeDesc = !empty($descParts) ? implode(', ', $descParts) : 'Perubahan override disimpan.';

        ActivityLog::record(
            $request->user()->id,
            'override_session',
            'schedule_session',
            "{$scheduleId}-{$weekNo}",
            "Override pertemuan ke-{$weekNo} {$courseName} Kelas {$classCode} ({$dateFormatted}): {$changeDesc}",
            $request->ip()
        );

        return back()->with('success', "Override pertemuan ke-{$weekNo} {$courseName} Kelas {$classCode} ({$dateFormatted}) berhasil disimpan.");
    }
}
