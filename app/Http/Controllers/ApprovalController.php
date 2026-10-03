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

        return view('approvals.index', ['approvals' => BookingApproval::with('booking.roomBookings.room')->where('level', $level)->where('status', 'pending')->latest()->paginate(15), 'level' => $level]);
    }

    public function decide(Request $request, Booking $booking, ActivityLogger $logger): RedirectResponse
    {
        $data = $request->validate(['status' => 'required|in:approved,rejected', 'notes' => 'nullable|required_if:status,rejected|string']);
        $level = $request->user()->hasRole('Kepala_Prodi') ? 1 : 2;
        $approval = $booking->approvals()->where('level', $level)->where('status', 'pending')->firstOrFail();
        DB::transaction(function () use ($data, $request, $booking, $approval, $level, $logger) {
            $approval->update([...$data, 'approver_id' => $request->user()->id, 'decided_at' => now()]);
            if ($data['status'] === 'rejected') {
                $booking->update(['status' => 'rejected']);
            } elseif ($level === 1) {
                $booking->approvals()->create(['level' => 2]);
            } else {
                if ($booking->type === 'change') {
                    $booking->parentBooking->update(['status' => 'cancelled']);
                }$booking->update(['status' => 'approved']);
            } $logger->log($request->user()->id, $data['status'] === 'approved' ? 'approve' : 'reject', 'booking_approval', $approval->id, $data['notes'] ?? null);
        });

        return back()->with('success', 'Keputusan berhasil disimpan.');
    }
}
