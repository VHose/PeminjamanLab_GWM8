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
        $bookings = $request->user()->isInternal()
            ? Booking::with(['roomBookings.room', 'requester', 'approvals'])->latest()->paginate(15)
            : $request->user()->bookings()->with(['roomBookings.room', 'approvals'])->latest()->paginate(15);

        return view('bookings.index', compact('bookings'));
    }

    public function create(): View
    {
        $rooms = Room::where('active', true)->orderBy('code')->get();

        return view('bookings.create', compact('rooms'));
    }

    public function show(Booking $booking): View
    {
        if (! request()->user()->isInternal() && $booking->user_id !== request()->user()->id) {
            abort(403);
        }

        $booking->load(['roomBookings.room', 'approvals.approver', 'requester', 'parentBooking']);

        return view('bookings.show', compact('booking'));
    }

    public function change(Booking $booking): View
    {
        $firstStart = $booking->roomBookings()->min('start_datetime');
        $canChange = $booking->user_id === request()->user()->id
            && $booking->status === 'approved'
            && $firstStart
            && now()->lt(Carbon::parse($firstStart)->subDays(2));

        abort_unless($canChange, 403);

        $rooms = Room::where('active', true)->orderBy('code')->get();

        return view('bookings.change', compact('booking', 'rooms'));
    }

    public function store(Request $request, BookingService $service, ActivityLogger $logger): RedirectResponse
    {
        $data = $request->validate([
            'requester_name' => 'required|string|max:255',
            'purpose' => 'required|string',
            'participant_count' => 'required|integer|min:1',
            'type' => 'required|in:new,change',
            'parent_booking_id' => 'nullable|required_if:type,change|exists:booking,id',
            'notes' => 'nullable|required_if:type,change|string',
            'slots' => 'required|array|min:1',
            'slots.*.room_id' => 'required|exists:room,id',
            'slots.*.date' => 'required|date',
            'slots.*.start' => 'required|date_format:H:i',
            'slots.*.end' => 'required|date_format:H:i|after:slots.*.start',
        ]);

        $this->validateSlotCollisions($data['slots']);

        foreach ($data['slots'] as $slot) {
            $start = Carbon::parse("{$slot['date']} {$slot['start']}");
            $end = Carbon::parse("{$slot['date']} {$slot['end']}");

            if (! in_array($slot['start'], $service->startSlots(), true)
                || ! in_array($slot['end'], $service->endSlots(), true)
                || ! $service->isAvailable((int) $slot['room_id'], $start, $end)) {
                throw ValidationException::withMessages([
                    'slots' => 'Salah satu ruangan atau waktu sudah terisi jadwal atau belum dapat dipinjam pada periode ujian.',
                ]);
            }
        }

        DB::transaction(function () use ($data, $request, $logger) {
            $booking = Booking::query()->create([
                'user_id' => $request->user()->id,
                'parent_booking_id' => $data['parent_booking_id'] ?? null,
                'requester_name' => $data['requester_name'],
                'purpose' => $data['purpose'],
                'participant_count' => $data['participant_count'],
                'type' => $data['type'],
                'status' => 'pending',
                'notes' => $data['notes'] ?? null,
                'submitted_at' => now(),
            ]);

            foreach ($data['slots'] as $slot) {
                $booking->roomBookings()->create([
                    'room_id' => $slot['room_id'],
                    'start_datetime' => Carbon::parse("{$slot['date']} {$slot['start']}"),
                    'end_datetime' => Carbon::parse("{$slot['date']} {$slot['end']}"),
                ]);
            }

            $booking->approvals()->create([
                'level' => $booking->type === 'change' ? 2 : 1,
                'status' => 'pending',
            ]);

            $logger->log(
                $request->user()->id,
                'create',
                'booking',
                $booking->id,
                $booking->type === 'change' ? 'Pengajuan perubahan jadwal/ruangan.' : 'Pengajuan peminjaman baru.'
            );
        });

        return redirect()->route('bookings.index')->with('success', 'Pengajuan peminjaman berhasil dikirim.');
    }

    public function staffCreate(): View
    {
        $rooms = Room::where('active', true)->orderBy('code')->get();

        return view('bookings.staff-create', compact('rooms'));
    }

    public function staffStore(Request $request, BookingService $service, ActivityLogger $logger): RedirectResponse
    {
        $data = $request->validate([
            'requester_name' => 'required|string|max:255',
            'purpose' => 'required|string',
            'participant_count' => 'required|integer|min:1',
            'slots' => 'required|array|min:1',
            'slots.*.room_id' => 'required|exists:room,id',
            'slots.*.date' => 'required|date',
            'slots.*.start' => 'required|date_format:H:i',
            'slots.*.end' => 'required|date_format:H:i|after:slots.*.start',
        ]);

        $this->validateSlotCollisions($data['slots']);

        foreach ($data['slots'] as $slot) {
            $start = Carbon::parse("{$slot['date']} {$slot['start']}");
            $end = Carbon::parse("{$slot['date']} {$slot['end']}");

            if (! in_array($slot['start'], $service->startSlots(), true)
                || ! in_array($slot['end'], $service->endSlots(), true)
                || ! $service->isAvailable((int) $slot['room_id'], $start, $end)) {
                throw ValidationException::withMessages([
                    'slots' => 'Salah satu ruangan atau waktu sudah terisi jadwal atau belum dapat dipinjam pada periode ujian.',
                ]);
            }
        }

        DB::transaction(function () use ($data, $request, $logger) {
            $booking = Booking::query()->create([
                'user_id' => $request->user()->id,
                'requester_name' => $data['requester_name'],
                'purpose' => $data['purpose'],
                'participant_count' => $data['participant_count'],
                'type' => 'new',
                'status' => 'approved',
                'submitted_at' => now(),
            ]);

            foreach ($data['slots'] as $slot) {
                $booking->roomBookings()->create([
                    'room_id' => $slot['room_id'],
                    'start_datetime' => Carbon::parse("{$slot['date']} {$slot['start']}"),
                    'end_datetime' => Carbon::parse("{$slot['date']} {$slot['end']}"),
                ]);
            }

            $logger->log(
                $request->user()->id,
                'create',
                'booking',
                $booking->id,
                'Peminjaman diinput oleh staf atas nama dosen dan langsung disetujui.'
            );
        });

        return redirect()->route('bookings.index')->with('success', 'Peminjaman atas nama dosen berhasil dimasukkan.');
    }

    public function cancel(Request $request, Booking $booking, ActivityLogger $logger): RedirectResponse
    {
        $firstStart = $booking->roomBookings()->min('start_datetime');
        $canCancel = $booking->user_id === $request->user()->id
            && $booking->status === 'approved'
            && $firstStart
            && now()->lt(Carbon::parse($firstStart)->subDays(2));

        abort_unless($canCancel, 403);

        $booking->update(['status' => 'cancelled']);
        $logger->log($request->user()->id, 'cancel', 'booking', $booking->id, 'Dibatalkan oleh peminjam.');

        return back()->with('success', 'Peminjaman berhasil dibatalkan dan slot kembali tersedia.');
    }

    private function validateSlotCollisions(array $slots): void
    {
        $count = count($slots);
        for ($i = 0; $i < $count; $i++) {
            for ($j = $i + 1; $j < $count; $j++) {
                $slotA = $slots[$i];
                $slotB = $slots[$j];
                if ((int) $slotA['room_id'] === (int) $slotB['room_id'] && $slotA['date'] === $slotB['date']) {
                    $startA = Carbon::parse("{$slotA['date']} {$slotA['start']}");
                    $endA = Carbon::parse("{$slotA['date']} {$slotA['end']}");
                    $startB = Carbon::parse("{$slotB['date']} {$slotB['start']}");
                    $endB = Carbon::parse("{$slotB['date']} {$slotB['end']}");

                    if ($startA->lt($endB) && $endA->gt($startB)) {
                        throw ValidationException::withMessages([
                            'slots' => 'Terdapat ruangan dan waktu yang saling bertabrakan dalam pengajuan Anda.',
                        ]);
                    }
                }
            }
        }
    }
}
