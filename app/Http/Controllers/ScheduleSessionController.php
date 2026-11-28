<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdateScheduleSessionRequest;
use App\Models\ActivityLog;
use App\Models\Room;
use App\Models\ScheduleSession;
use App\Services\BookingService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class ScheduleSessionController extends Controller
{
    /**
     * Membatalkan (meliburkan) atau mengaktifkan kembali satu pertemuan kuliah langsung dari tabel jadwal.
     * Jika jadwal merupakan kelas gabungan (Gabung), seluruh jadwal gabungan di slot tersebut ikut disinkronkan.
     */
    public function toggle(
        Request $request,
        int $scheduleId,
        int $weekNo,
        BookingService $bookingService
    ): RedirectResponse|JsonResponse {
        $session = ScheduleSession::with(['schedule.course', 'schedule.lecturer'])
            ->where('schedule_id', $scheduleId)
            ->where('week_no', $weekNo)
            ->firstOrFail();

        $siblings = $session->combinedSessions();

        abort_unless(
            $request->user()?->canManageSchedule($session->schedule, $siblings->pluck('schedule')),
            403,
            'Anda hanya dapat mengubah jadwal mata kuliah yang Anda ajar sendiri.'
        );

        // Pilih nama mata kuliah utama (utamakan yang bukan label Gabung)
        $primarySession = $siblings->sortBy([
            fn ($s) => stripos($s->schedule?->course?->name ?? '', 'Gabung') !== false ? 1 : 0,
            fn ($s) => $s->schedule_id,
        ])->first() ?? $session;

        $courseName = $primarySession->schedule?->course?->name ?? 'Kuliah';
        $classCode = $primarySession->schedule?->class_code ?? '';
        $dateFormatted = $session->session_date->format('d/m/Y');

        // Jika salah satu dari kelas gabungan masih aktif (atau sesi yang diklik aktif), aksi adalah meliburkan semuanya.
        // Namun jika sesi yang diklik berstatus nonaktif, pengguna berniat mengaktifkannya kembali.
        $targetActive = $request->has('is_active')
            ? $request->boolean('is_active')
            : (! $session->is_active);

        if (! $targetActive) {
            $request->validate([
                'note' => ['nullable', 'string', 'max:100'],
            ]);

            $note = $request->input('note') ?: 'Kuliah diliburkan.';

            foreach ($siblings as $sib) {
                $sib->update([
                    'is_active' => false,
                    'note' => $note,
                ]);
            }

            ActivityLog::record(
                $request->user()->id,
                'cancel_session',
                'schedule_session',
                "{$scheduleId}-{$weekNo}",
                "Meliburkan pertemuan ke-{$weekNo} {$courseName} Kelas {$classCode} pada tanggal {$dateFormatted}. Catatan: {$note}",
                $request->ip()
            );

            $msg = "Pertemuan ke-{$weekNo} {$courseName} Kelas {$classCode} pada {$dateFormatted} berhasil diliburkan.";
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => true,
                    'is_active' => false,
                    'note' => $note,
                    'schedule_ids' => $siblings->pluck('schedule_id')->values(),
                    'week_no' => $weekNo,
                    'message' => $msg,
                ]);
            }

            return back()->with('success', $msg);
        }

        // Aksi: Aktifkan kembali pertemuan kuliah (cek bentrok di luar sesama kelas gabungan)
        $conflictMessage = $bookingService->checkSessionConflict($session);

        if ($conflictMessage !== null) {
            $errMsg = "Tidak dapat mengaktifkan pertemuan ke-{$weekNo} {$courseName} Kelas {$classCode}: {$conflictMessage}";
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'is_active' => false,
                    'message' => $errMsg,
                ], 422);
            }

            return back()->withErrors(['error' => $errMsg]);
        }

        foreach ($siblings as $sib) {
            $sib->update([
                'is_active' => true,
                'note' => null,
            ]);
        }

        ActivityLog::record(
            $request->user()->id,
            'activate_session',
            'schedule_session',
            "{$scheduleId}-{$weekNo}",
            "Mengaktifkan kembali pertemuan ke-{$weekNo} {$courseName} Kelas {$classCode} pada tanggal {$dateFormatted}.",
            $request->ip()
        );

        $msg = "Pertemuan ke-{$weekNo} {$courseName} Kelas {$classCode} pada {$dateFormatted} berhasil diaktifkan kembali.";
        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'is_active' => true,
                'note' => null,
                'schedule_ids' => $siblings->pluck('schedule_id')->values(),
                'week_no' => $weekNo,
                'message' => $msg,
            ]);
        }

        return back()->with('success', $msg);
    }

    /**
     * Memperbarui override ruangan dan/atau waktu untuk satu sesi pertemuan kuliah (beserta kelas gabungannya).
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

        $siblings = $session->combinedSessions();

        abort_unless(
            $request->user()?->canManageSchedule($session->schedule, $siblings->pluck('schedule')),
            403,
            'Anda hanya dapat mengubah jadwal mata kuliah yang Anda ajar sendiri.'
        );

        $primarySession = $siblings->sortBy([
            fn ($s) => stripos($s->schedule?->course?->name ?? '', 'Gabung') !== false ? 1 : 0,
            fn ($s) => $s->schedule_id,
        ])->first() ?? $session;

        $courseName = $primarySession->schedule?->course?->name ?? 'Kuliah';
        $classCode = $primarySession->schedule?->class_code ?? '';
        $dateFormatted = $session->session_date->format('d/m/Y');

        // 1. Reset override jika clear_override == true
        if ($request->boolean('clear_override')) {
            foreach ($siblings as $sib) {
                $defaultDate = $sib->defaultSessionDate() ?? $sib->session_date->toDateString();
                $sib->update([
                    'session_date' => $defaultDate,
                    'room_id'      => $sib->schedule->room_id,
                    'start_time'   => $sib->schedule->start_time,
                    'end_time'     => $sib->schedule->end_time,
                    'note'         => null,
                ]);
            }

            ActivityLog::record(
                $request->user()->id,
                'reset_override',
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

        // 3. Validasi konflik jadwal via BookingService (mengecualikan sesama kelas gabungan)
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

        // 4. Simpan override ke seluruh sesi dalam kelompok gabungan
        foreach ($siblings as $sib) {
            $sib->update([
                'session_date' => $targetDate,
                'room_id'      => $targetRoomId ?? $sib->room_id,
                'start_time'   => $formattedStartTime ?? $sib->start_time,
                'end_time'     => $formattedEndTime ?? $sib->end_time,
                'note'         => $note,
            ]);
        }

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
