<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\ScheduleSession;
use App\Services\BookingService;
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
}
