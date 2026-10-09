<?php

namespace App\Http\Controllers;

use App\Constants\BookingDetailStatus;
use App\Constants\BookingStatus;
use App\Constants\BookingType;
use App\Models\Booking;
use App\Models\BookingDetail;
use App\Services\ActivityLogger;
use App\Services\BookingService;
use App\Services\NotificationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class ApprovalController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();
        $isKaprodi = $user->hasRole('Kepala_Prodi');
        $isKalab = $user->hasRole('Kepala_Lab');

        $pendingBookings = Booking::with(['details.room', 'requester'])
            ->where(function ($q) use ($isKaprodi, $isKalab) {
                if ($isKaprodi && $isKalab) {
                    $q->whereIn('status', [BookingStatus::PENDING_KAPRODI, BookingStatus::PENDING_KALAB]);
                } elseif ($isKaprodi) {
                    $q->where('status', BookingStatus::PENDING_KAPRODI);
                } elseif ($isKalab) {
                    $q->where('status', BookingStatus::PENDING_KALAB);
                } else {
                    $q->whereRaw('0 = 1');
                }
            })
            ->latest('submitted_at')
            ->paginate(15);

        $recentDecisions = Booking::with(['details.room', 'requester'])
            ->whereIn('status', [BookingStatus::APPROVED, BookingStatus::REJECTED])
            ->latest('updated_at')
            ->take(10)
            ->get();

        return view('approvals.index', compact('pendingBookings', 'recentDecisions', 'isKaprodi', 'isKalab'));
    }

    /**
     * Keputusan Kaprodi (Level Booking).
     */
    public function decideKaprodi(
        Request $request,
        Booking $booking,
        ActivityLogger $logger,
        NotificationService $notifications
    ): RedirectResponse {
        abort_unless($request->user()->hasRole('Kepala_Prodi'), 403);
        abort_unless($booking->status === BookingStatus::PENDING_KAPRODI, 400, 'Pengajuan ini tidak sedang menunggu persetujuan Kaprodi.');

        $data = $request->validate([
            'status' => 'required|in:approved,rejected',
            'notes' => 'nullable|required_if:status,rejected|string',
        ]);

        DB::transaction(function () use ($data, $request, $booking, $logger) {
            if ($data['status'] === 'approved') {
                $booking->update([
                    'status' => BookingStatus::PENDING_KALAB,
                ]);

                $logger->log(
                    $request->user()->id,
                    'approve',
                    'booking',
                    (string) $booking->id,
                    'Disetujui oleh Kepala Program Studi, diteruskan ke Kepala Lab.'
                );
            } else {
                $booking->update([
                    'status' => BookingStatus::REJECTED,
                    'notes' => $data['notes'],
                ]);

                // Semua detail ditolak
                $booking->details()->update([
                    'status' => BookingDetailStatus::REJECTED,
                    'notes' => $data['notes'],
                ]);

                $logger->log(
                    $request->user()->id,
                    'reject',
                    'booking',
                    (string) $booking->id,
                    'Ditolak oleh Kepala Program Studi. Alasan: ' . $data['notes']
                );
            }
        });

        if ($data['status'] === 'approved') {
            $notifications->notifyKaprodiApproved($booking);
        } else {
            $notifications->notifyFinalResult($booking);
        }

        return back()->with('success', 'Keputusan Kaprodi berhasil disimpan.');
    }

    /**
     * Keputusan Kalab (Per Ruangan / BookingDetail).
     */
    public function decideKalabDetail(
        Request $request,
        BookingDetail $detail,
        BookingService $bookingService,
        ActivityLogger $logger,
        NotificationService $notifications
    ): RedirectResponse {
        abort_unless($request->user()->hasRole('Kepala_Lab'), 403);

        $booking = $detail->booking;
        abort_unless($booking->status === BookingStatus::PENDING_KALAB, 400, 'Pengajuan ini tidak sedang menunggu persetujuan Kepala Lab.');

        $data = $request->validate([
            'status' => 'required|in:approved,rejected',
            'notes' => 'nullable|required_if:status,rejected|string',
        ]);

        // Cek bentrok jika hendak disetujui
        if ($data['status'] === 'approved') {
            $excludeId = $booking->type === BookingType::RESCHEDULE ? $booking->parent_booking_id : null;
            if (! $bookingService->isAvailable($detail->room_id, $detail->start_datetime, $detail->end_datetime, $excludeId)) {
                throw ValidationException::withMessages([
                    'status' => 'Ruangan ini memiliki konflik jadwal atau bentrok dengan peminjaman lain yang telah disetujui.',
                ]);
            }
        }

        DB::transaction(function () use ($data, $request, $detail, $booking, $logger) {
            $detail->update([
                'status' => $data['status'] === 'approved' ? BookingDetailStatus::APPROVED : BookingDetailStatus::REJECTED,
                'notes' => $data['notes'] ?? null,
            ]);

            $logger->log(
                $request->user()->id,
                $data['status'] === 'approved' ? 'approve' : 'reject',
                'booking_detail',
                (string) $detail->id,
                ($data['status'] === 'approved' ? 'Disetujui' : 'Ditolak') . " per ruangan {$detail->room->code} oleh Kalab." . (! empty($data['notes']) ? " Alasan: {$data['notes']}" : '')
            );

            // Cek apakah semua detail untuk booking ini sudah diputuskan
            $hasPendingDetails = $booking->details()->where('status', BookingDetailStatus::PENDING)->exists();

            if (! $hasPendingDetails) {
                $hasAnyApproved = $booking->details()->where('status', BookingDetailStatus::APPROVED)->exists();

                if ($hasAnyApproved) {
                    $booking->update(['status' => BookingStatus::APPROVED]);

                    // Jika ini reschedule dan disetujui, booking lama dibatalkan
                    if ($booking->type === BookingType::RESCHEDULE && $booking->parent) {
                        $parent = $booking->parent;
                        $parent->update(['status' => BookingStatus::CANCELLED]);
                        $parent->details()->update(['status' => BookingDetailStatus::CANCELLED]);
                    }
                } else {
                    $booking->update([
                        'status' => BookingStatus::REJECTED,
                        'notes' => 'Semua ruangan ditolak oleh Kepala Lab.',
                    ]);
                }
            }
        });

        // Jika semua detail selesai diputuskan, kirim hasil akhir ke peminjam
        $booking->refresh();
        if ($booking->status === BookingStatus::APPROVED || $booking->status === BookingStatus::REJECTED) {
            $notifications->notifyFinalResult($booking);
        }

        return back()->with('success', 'Keputusan untuk ruangan berhasil disimpan.');
    }
}
