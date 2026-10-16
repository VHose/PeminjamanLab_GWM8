<?php

namespace App\Http\Controllers;

use App\Enums\BookingDetailStatus;
use App\Enums\BookingStatus;
use App\Models\ActivityLog;
use App\Models\Booking;
use App\Models\BookingDetail;
use App\Models\Room;
use App\Models\Setting;
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
            ? Booking::with(['details.room', 'user'])->latest()->paginate(15)
            : $request->user()->bookings()->with(['details.room'])->latest()->paginate(15);

        return view('bookings.index', compact('bookings'));
    }

    public function create(): View
    {
        $isConfigured = Setting::isBookingConfigured();
        $rooms = Room::where('is_active', true)->orderBy('id')->get();

        return view('bookings.create', compact('rooms', 'isConfigured'));
    }

    public function staffCreate(): View
    {
        $isConfigured = Setting::isBookingConfigured();
        $rooms = Room::where('is_active', true)->orderBy('id')->get();

        return view('bookings.staff_create', compact('rooms', 'isConfigured'));
    }

    public function show(Booking $booking): View
    {
        if (! request()->user()->isInternal() && $booking->user_id !== request()->user()->id) {
            abort(403);
        }

        $booking->load(['details.room', 'user', 'createdBy', 'attachments']);

        return view('bookings.show', compact('booking'));
    }

    public function change(Booking $booking): View
    {
        $firstDetail = $booking->details()->orderBy('booking_date')->orderBy('start_time')->first();
        $firstStart = $firstDetail ? Carbon::parse($firstDetail->booking_date->toDateString() . ' ' . $firstDetail->start_time) : null;

        $canChange = $booking->user_id === request()->user()->id
            && $booking->status === BookingStatus::APPROVED
            && $firstStart
            && $firstStart->gte(now()->addDays(2));

        abort_unless($canChange, 403, 'Perubahan jadwal hanya bisa diajukan paling lambat H-2.');

        $rooms = Room::where('is_active', true)->orderBy('id')->get();
        $isConfigured = Setting::isBookingConfigured();

        return view('bookings.change', compact('booking', 'rooms', 'isConfigured'));
    }

    public function store(
        Request $request,
        BookingService $service,
        NotificationService $notifications
    ): RedirectResponse {
        if (! Setting::isBookingConfigured()) {
            throw ValidationException::withMessages([
                'slots' => 'Peminjaman belum dibuka karena jam operasional lab dan aturan pemesanan belum ditentukan oleh Administrator.',
            ]);
        }

        $data = $request->validate([
            'pic_name' => ['required', 'string', 'max:100'],
            'pic_phone' => ['nullable', 'string', 'max:20'],
            'purpose' => ['required', 'string', 'max:500'],
            'slots' => ['required', 'array', 'min:1'],
            'slots.*.room_id' => ['required', 'exists:room,id'],
            'slots.*.date' => ['required', 'date'],
            'slots.*.start' => ['required', 'date_format:H:i'],
            'slots.*.end' => ['required', 'date_format:H:i', 'after:slots.*.start'],
        ]);

        $maxDaysAhead = (int) Setting::get('booking_max_days_ahead', 30);
        $maxDurationHours = (int) Setting::get('booking_max_duration_hours', 4);
        $minAllowedStart = now()->addDays(2);

        foreach ($data['slots'] as $slot) {
            $dateCarbon = Carbon::parse($slot['date']);

            // Tolak hari Minggu
            if ($dateCarbon->isSunday()) {
                throw ValidationException::withMessages([
                    'slots' => 'Peminjaman pada hari Minggu tidak diperkenankan.',
                ]);
            }

            $start = Carbon::parse("{$slot['date']} {$slot['start']}");
            $end = Carbon::parse("{$slot['date']} {$slot['end']}");

            // H+2 minimal
            if ($start->lt($minAllowedStart)) {
                throw ValidationException::withMessages([
                    'slots' => 'Waktu mulai peminjaman minimal 2 hari dari sekarang (H+2).',
                ]);
            }

            // Batas hari ke depan
            if ($start->gt(now()->addDays($maxDaysAhead))) {
                throw ValidationException::withMessages([
                    'slots' => "Peminjaman maksimal dapat dipesan {$maxDaysAhead} hari ke depan.",
                ]);
            }

            // Durasi maksimal
            $durationHours = $end->diffInMinutes($start) / 60;
            if ($durationHours > $maxDurationHours) {
                throw ValidationException::withMessages([
                    'slots' => "Durasi peminjaman per sesi tidak boleh melebihi {$maxDurationHours} jam.",
                ]);
            }

            // Jam operasional lab
            $labOpen = $dateCarbon->isSaturday()
                ? Setting::get('lab_open_time_saturday', Setting::get('lab_open_time', '07:00'))
                : Setting::get('lab_open_time', '07:00');
            $labClose = $dateCarbon->isSaturday()
                ? Setting::get('lab_close_time_saturday', Setting::get('lab_close_time', '21:00'))
                : Setting::get('lab_close_time', '21:00');

            if ($slot['start'] < substr($labOpen, 0, 5) || $slot['end'] > substr($labClose, 0, 5)) {
                throw ValidationException::withMessages([
                    'slots' => "Peminjaman harus berada di dalam jam operasional laboratorium ({$labOpen} - {$labClose}).",
                ]);
            }

            // Cek ketersediaan
            if (! $service->isAvailable($slot['room_id'], $start, $end)) {
                throw ValidationException::withMessages([
                    'slots' => "Ruangan {$slot['room_id']} pada tanggal {$slot['date']} ({$slot['start']} - {$slot['end']}) sudah terisi oleh jadwal kuliah atau peminjaman lain.",
                ]);
            }
        }

        $booking = DB::transaction(function () use ($request, $data) {
            $user = $request->user();

            $booking = Booking::create([
                'user_id' => $user->id,
                'created_by' => null,
                'pic_name' => $data['pic_name'],
                'pic_phone' => $data['pic_phone'] ?? null,
                'purpose' => $data['purpose'],
                'status' => BookingStatus::PENDING_PRODI,
            ]);

            foreach ($data['slots'] as $slot) {
                BookingDetail::create([
                    'booking_id' => $booking->id,
                    'room_id' => $slot['room_id'],
                    'booking_date' => $slot['date'],
                    'start_time' => $slot['start'] . ':00',
                    'end_time' => $slot['end'] . ':00',
                    'status' => BookingDetailStatus::PENDING,
                ]);
            }

            ActivityLog::record(
                $user->id,
                'create',
                'booking',
                $booking->id,
                "Mengajukan peminjaman ruangan #{$booking->id} untuk kegiatan: {$booking->purpose}.",
                $request->ip()
            );

            return $booking;
        });

        return redirect()->route('bookings.show', $booking)->with('success', 'Pengajuan peminjaman berhasil dikirim.');
    }

    public function cancel(Request $request, Booking $booking): RedirectResponse
    {
        $user = $request->user();
        if ($booking->user_id !== $user->id && ! $user->isInternal()) {
            abort(403);
        }

        $booking->update([
            'status' => BookingStatus::CANCELLED,
            'decision_note' => $request->input('reason', 'Dibatalkan oleh peminjam.'),
        ]);

        $booking->details()->update(['status' => BookingDetailStatus::CANCELLED]);

        ActivityLog::record(
            $user->id,
            'cancel',
            'booking',
            $booking->id,
            "Membatalkan peminjaman #{$booking->id}.",
            $request->ip()
        );

        return back()->with('success', 'Peminjaman berhasil dibatalkan.');
    }
}
