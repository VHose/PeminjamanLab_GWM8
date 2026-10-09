<?php

namespace App\Http\Controllers;

use App\Constants\BookingDetailStatus;
use App\Constants\BookingStatus;
use App\Constants\BookingType;
use App\Models\Booking;
use App\Models\BookingDetail;
use App\Models\Room;
use App\Services\ActivityLogger;
use App\Services\BookingService;
use App\Services\NotificationService;
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
            ? Booking::with(['details.room', 'requester'])->latest()->paginate(15)
            : $request->user()->bookings()->with(['details.room'])->latest()->paginate(15);

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

        $booking->load(['details.room', 'requester', 'parent', 'children']);

        return view('bookings.show', compact('booking'));
    }

    public function change(Booking $booking): View
    {
        $firstStart = $booking->details()->min('start_datetime');
        $canChange = $booking->user_id === request()->user()->id
            && $booking->status === BookingStatus::APPROVED
            && $firstStart
            && Carbon::parse($firstStart)->gte(now()->addDays(2));

        abort_unless($canChange, 403, 'Perubahan jadwal hanya bisa diajukan paling lambat H-2.');

        $rooms = Room::where('active', true)->orderBy('code')->get();

        return view('bookings.change', compact('booking', 'rooms'));
    }

    public function store(Request $request, BookingService $service, ActivityLogger $logger, NotificationService $notifications): RedirectResponse
    {
        $data = $request->validate([
            'requester_name' => 'required|string|max:100',
            'purpose' => 'required|string',
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

        $isChange = $data['type'] === 'change';
        $parentBookingId = $isChange ? (int) $data['parent_booking_id'] : null;

        // Validasi waktu mulai harus > 2 hari dari sekarang (H+2)
        $minAllowedStart = now()->addDays(2);

        foreach ($data['slots'] as $slot) {
            $start = Carbon::parse("{$slot['date']} {$slot['start']}");
            $end = Carbon::parse("{$slot['date']} {$slot['end']}");

            if ($start->lt($minAllowedStart)) {
                throw ValidationException::withMessages([
                    'slots' => 'Waktu mulai peminjaman harus lebih dari 2 hari dari sekarang (minimal H+2).',
                ]);
            }

            if (! in_array($slot['start'], $service->startSlots(), true)
                || ! in_array($slot['end'], $service->endSlots(), true)
                || ! $service->isAvailable((int) $slot['room_id'], $start, $end, $parentBookingId)) {
                throw ValidationException::withMessages([
                    'slots' => 'Salah satu ruangan atau waktu sudah terisi jadwal atau belum dapat dipinjam pada periode ujian.',
                ]);
            }
        }

        $booking = DB::transaction(function () use ($data, $request, $isChange, $parentBookingId, $logger) {
            $booking = Booking::create([
                'user_id' => $request->user()->id,
                'parent_booking_id' => $parentBookingId,
                'requester_name' => $data['requester_name'],
                'purpose' => $data['purpose'],
                'type' => $isChange ? BookingType::RESCHEDULE : BookingType::NEW_BOOKING,
                'status' => $isChange ? BookingStatus::PENDING_KALAB : BookingStatus::PENDING_KAPRODI,
                'notes' => $isChange ? ($data['notes'] ?? null) : null,
                'submitted_at' => now(),
            ]);

            foreach ($data['slots'] as $slot) {
                $booking->details()->create([
                    'room_id' => $slot['room_id'],
                    'start_datetime' => Carbon::parse("{$slot['date']} {$slot['start']}"),
                    'end_datetime' => Carbon::parse("{$slot['date']} {$slot['end']}"),
                    'status' => BookingDetailStatus::PENDING,
                    'notes' => null,
                ]);
            }

            $logger->log(
                $request->user()->id,
                'create',
                'booking',
                (string) $booking->id,
                $isChange ? 'Pengajuan permohonan perubahan jadwal/ruangan.' : 'Pengajuan peminjaman laboratorium baru.',
                ['type' => $booking->type, 'slots_count' => count($data['slots'])]
            );

            return $booking;
        });

        // Email notifications
        if ($isChange) {
            $notifications->notifyRescheduleSubmitted($booking);
        } else {
            $notifications->notifyNewBooking($booking);
        }

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
            'requester_name' => 'required|string|max:100',
            'purpose' => 'required|string',
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
            $booking = Booking::create([
                'user_id' => $request->user()->id,
                'parent_booking_id' => null,
                'requester_name' => $data['requester_name'],
                'purpose' => $data['purpose'],
                'type' => BookingType::NEW_BOOKING,
                'status' => BookingStatus::APPROVED,
                'notes' => 'Diinput oleh staf atas nama dosen.',
                'submitted_at' => now(),
            ]);

            foreach ($data['slots'] as $slot) {
                $booking->details()->create([
                    'room_id' => $slot['room_id'],
                    'start_datetime' => Carbon::parse("{$slot['date']} {$slot['start']}"),
                    'end_datetime' => Carbon::parse("{$slot['date']} {$slot['end']}"),
                    'status' => BookingDetailStatus::APPROVED,
                    'notes' => null,
                ]);
            }

            $logger->log(
                $request->user()->id,
                'create',
                'booking',
                (string) $booking->id,
                'Peminjaman diinput oleh staf atas nama dosen dan langsung disetujui.',
                ['requester_name' => $data['requester_name']]
            );
        });

        // Booking staf tidak mengirim email
        return redirect()->route('bookings.index')->with('success', 'Peminjaman atas nama dosen berhasil dimasukkan.');
    }

    public function cancel(Request $request, Booking $booking, ActivityLogger $logger): RedirectResponse
    {
        $firstStart = $booking->details()->min('start_datetime');
        $canCancel = $booking->user_id === $request->user()->id
            && $booking->status === BookingStatus::APPROVED
            && $firstStart
            && Carbon::parse($firstStart)->gte(now()->addDays(2));

        abort_unless($canCancel, 403, 'Pembatalan hanya dapat dilakukan paling lambat H-2.');

        DB::transaction(function () use ($booking, $request, $logger) {
            $booking->update(['status' => BookingStatus::CANCELLED]);
            $booking->details()->update(['status' => BookingDetailStatus::CANCELLED]);

            $logger->log(
                $request->user()->id,
                'cancel',
                'booking',
                (string) $booking->id,
                'Peminjaman dibatalkan oleh peminjam.'
            );
        });

        // Pembatalan tidak mengirim email
        return back()->with('success', 'Peminjaman berhasil dibatalkan.');
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
