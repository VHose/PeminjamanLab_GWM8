<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Models\Room;
use App\Services\ActivityLogger;
use App\Services\BookingService;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class BookingController extends Controller
{
    public function index(Request $request): View
    {
        return view('bookings.index', ['bookings' => $request->user()->isInternal() ? Booking::with('roomBookings.room', 'requester')->latest()->paginate(15) : $request->user()->bookings()->with('roomBookings.room')->latest()->paginate(15)]);
    }

    public function create(): View
    {
        return view('bookings.form', ['rooms' => Room::orderBy('name')->get(), 'booking' => null, 'changeFrom' => null]);
    }

    public function change(Booking $booking): View
    {
        abort_unless($booking->user_id === request()->user()->id && $booking->status === 'approved', 403);

        return view('bookings.form', ['rooms' => Room::orderBy('name')->get(), 'booking' => null, 'changeFrom' => $booking]);
    }

    public function store(Request $request, BookingService $service, ActivityLogger $logger): RedirectResponse
    {
        $data = $request->validate(['requester_name' => 'required|string|max:255', 'purpose' => 'required|string', 'participant_count' => 'required|integer|min:1', 'type' => 'required|in:new,change', 'parent_booking_id' => 'nullable|required_if:type,change|exists:booking,id', 'notes' => 'nullable|required_if:type,change|string', 'slots' => 'required|array|min:1', 'slots.*.room_id' => 'required|exists:room,id', 'slots.*.date' => 'required|date', 'slots.*.start' => 'required|date_format:H:i', 'slots.*.end' => 'required|date_format:H:i|after:slots.*.start']);
        foreach ($data['slots'] as $slot) {
            $start = Carbon::parse("{$slot['date']} {$slot['start']}");
            $end = Carbon::parse("{$slot['date']} {$slot['end']}");
            if (! in_array($slot['start'], $service->timeSlots(), true) || ! in_array($slot['end'], $service->timeSlots(), true) || ! $service->isAvailable($slot['room_id'], $start, $end)) {
                throw ValidationException::withMessages(['slots' => 'Salah satu ruangan atau waktu sudah tidak tersedia.']);
            }
        }
        DB::transaction(function () use ($data, $request, $logger) {
            $starts = collect($data['slots'])->map(fn ($s) => Carbon::parse("{$s['date']} {$s['start']}"));
            $ends = collect($data['slots'])->map(fn ($s) => Carbon::parse("{$s['date']} {$s['end']}"));
            $booking = Booking::query()->create(collect($data)->except('slots')->merge(['user_id' => $request->user()->id, 'status' => 'pending', 'start_datetime' => $starts->min(), 'end_datetime' => $ends->max()])->all());
            foreach ($data['slots'] as $slot) {
                $booking->roomBookings()->create(['room_id' => $slot['room_id'], 'start_datetime' => Carbon::parse("{$slot['date']} {$slot['start']}"), 'end_datetime' => Carbon::parse("{$slot['date']} {$slot['end']}")]);
            } $booking->approvals()->create(['level' => $booking->type === 'change' ? 2 : 1]);
            $logger->log($request->user()->id, 'create', 'booking', $booking->id, $booking->type === 'change' ? 'Pengajuan perubahan jadwal.' : 'Pengajuan peminjaman baru.');
        });

        return redirect()->route('bookings.index')->with('success', 'Pengajuan peminjaman berhasil dikirim.');
    }

    public function staffCreate(): View
    {
        return view('bookings.staff-form', ['rooms' => Room::orderBy('name')->get()]);
    }

    public function staffStore(Request $request, BookingService $service, ActivityLogger $logger): RedirectResponse
    {
        $request->merge(['type' => 'new']);
        $response = $this->store($request, $service, $logger);
        $booking = Booking::latest('id')->first();
        $booking->update(['status' => 'approved']);
        $booking->approvals()->delete();
        $logger->log($request->user()->id, 'create', 'booking', $booking->id, 'Dimasukkan staf atas nama dosen dan langsung disetujui.');

        return $response;
    }

    public function cancel(Request $request, Booking $booking, ActivityLogger $logger): RedirectResponse
    {
        abort_unless($booking->user_id === $request->user()->id && $booking->status === 'approved' && now()->lt($booking->start_datetime->copy()->subDays(2)), 403);
        $booking->update(['status' => 'cancelled']);
        $logger->log($request->user()->id, 'cancel', 'booking', $booking->id);

        return back()->with('success', 'Peminjaman dibatalkan dan slot kembali tersedia.');
    }
}
