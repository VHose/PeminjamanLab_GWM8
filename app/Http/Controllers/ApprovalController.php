<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Models\BookingApproval;
use App\Services\ActivityLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class ApprovalController extends Controller
{
    public function index(Request $request): View
    {
        $level = $request->user()->hasRole('Kepala_Prodi') ? 1 : 2;

        $pendingApprovals = BookingApproval::with(['booking.roomBookings.room', 'booking.requester'])
            ->where('level', $level)
            ->where('status', 'pending')
            ->whereHas('booking', fn ($q) => $q->where('status', 'pending'))
            ->latest()
            ->paginate(15);

        $recentDecisions = BookingApproval::with(['booking.roomBookings.room', 'booking.requester', 'approver'])
            ->where('level', $level)
            ->whereIn('status', ['approved', 'rejected'])
            ->latest('decided_at')
            ->take(10)
            ->get();

        return view('approvals.index', compact('pendingApprovals', 'recentDecisions', 'level'));
    }

    public function decide(Request $request, Booking $booking, ActivityLogger $logger): RedirectResponse
    {
        $data = $request->validate([
            'status' => 'required|in:approved,rejected',
            'notes' => 'nullable|required_if:status,rejected|string',
        ]);

        $level = $request->user()->hasRole('Kepala_Prodi') ? 1 : 2;
        $approval = $booking->approvals()->where('level', $level)->where('status', 'pending')->firstOrFail();

        DB::transaction(function () use ($data, $request, $booking, $approval, $level, $logger) {
            $approval->update([
                'status' => $data['status'],
                'notes' => $data['notes'] ?? null,
                'approver_id' => $request->user()->id,
                'decided_at' => now(),
            ]);

            if ($data['status'] === 'rejected') {
                $booking->update([
                    'status' => 'rejected',
                    'notes' => $data['notes'] ?? null,
                ]);
            } elseif ($level === 1) {
                $booking->approvals()->create([
                    'level' => 2,
                    'status' => 'pending',
                ]);
            } else {
                if ($booking->type === 'change' && $booking->parentBooking) {
                    $booking->parentBooking->update(['status' => 'cancelled']);
                }
                $booking->update(['status' => 'approved']);
            }

            $logger->log(
                $request->user()->id,
                $data['status'] === 'approved' ? 'approve' : 'reject',
                'booking_approval',
                $approval->id,
                $data['notes'] ?? null
            );
        });

        return back()->with('success', 'Keputusan approval berhasil disimpan.');
    }
}
