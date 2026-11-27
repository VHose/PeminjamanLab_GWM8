<?php

namespace App\Http\Controllers;

use App\Enums\BookingDetailStatus;
use App\Enums\BookingStatus;
use App\Constants\BookingType;
use App\Models\Booking;
use App\Models\BookingDetail;
use App\Models\Period;
use App\Models\Room;
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
        $search = $request->input('search');

        $query = Booking::with(['details.room', 'requester', 'attachments'])
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
            });

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('id', 'like', "%{$search}%")
                  ->orWhere('pic_name', 'like', "%{$search}%")
                  ->orWhere('purpose', 'like', "%{$search}%")
                  ->orWhereHas('user', fn ($u) => $u->where('name', 'like', "%{$search}%")->orWhere('email', 'like', "%{$search}%"))
                  ->orWhereHas('details.room', fn ($r) => $r->where('id', 'like', "%{$search}%")->orWhere('name', 'like', "%{$search}%"));
            });
        }

        $allPeriods = Period::orderByDesc('start_date')->get();
        $rooms = Room::where('is_active', true)->orderBy('id')->get();
        $pendingBookings = $query->latest('created_at')->get();

        return view('approvals.index', compact('pendingBookings', 'isKaprodi', 'isKalab', 'search', 'allPeriods', 'rooms'));
    }

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
            'notes' => 'nullable|required_if:status,rejected|string|max:150',
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

                $booking->details()->update([
                    'status' => BookingDetailStatus::REJECTED,
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
            'notes' => 'nullable|required_if:status,rejected|string|max:150',
        ]);

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
            ]);

            if (! empty($data['notes'])) {
                $booking->update(['decision_note' => $data['notes']]);
            }

            $booking->touch();

            $logger->log(
                $request->user()->id,
                $data['status'] === 'approved' ? 'approve' : 'reject',
                'booking_detail',
                (string) $detail->booking_id,
                ($data['status'] === 'approved' ? 'Disetujui' : 'Ditolak') . " per ruangan {$detail->room_id} oleh Kalab." . (! empty($data['notes']) ? " Alasan: {$data['notes']}" : '')
            );

            $hasPendingDetails = $booking->details()->where('status', BookingDetailStatus::PENDING)->exists();

            if (! $hasPendingDetails) {
                $hasAnyApproved = $booking->details()->where('status', BookingDetailStatus::APPROVED)->exists();

                if ($hasAnyApproved) {
                    $booking->update(['status' => BookingStatus::APPROVED]);

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

        $booking->refresh();
        if ($booking->status === BookingStatus::APPROVED || $booking->status === BookingStatus::REJECTED) {
            $notifications->notifyFinalResult($booking);
        }

        return back()->with('success', 'Keputusan untuk ruangan berhasil disimpan.');
    }
}
