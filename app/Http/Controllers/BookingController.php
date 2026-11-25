<?php

namespace App\Http\Controllers;

use App\Constants\BookingType;
use App\Enums\BookingDetailStatus;
use App\Enums\BookingStatus;
use App\Models\ActivityLog;
use App\Models\Booking;
use App\Models\BookingAttachment;
use App\Models\BookingDetail;
use App\Models\Period;
use App\Models\Room;
use App\Services\BookingService;
use App\Services\NotificationService;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class BookingController extends Controller
{
    public function index(Request $request): View
    {
        $allPeriods = Period::orderByDesc('start_date')->get();
        $rooms = Room::where('is_active', true)->orderBy('id')->get();

        $search = $request->input('search');
        $status = $request->input('status');
        $periodId = $request->input('period_id');
        $roomId = $request->input('room_id');
        $type = $request->input('type');
        $dateFrom = $request->input('date_from');
        $dateTo = $request->input('date_to');

        $query = $request->user()->isInternal()
            ? Booking::with(['details.room', 'user'])
            : $request->user()->bookings()->with(['details.room']);

        if ($periodId) {
            $period = Period::find($periodId);
            if ($period) {
                $query->whereHas('details', function ($dq) use ($period) {
                    $dq->whereBetween('booking_date', [$period->start_date, $period->end_date]);
                });
            }
        }

        if ($roomId) {
            $query->whereHas('details', function ($dq) use ($roomId) {
                $dq->where('room_id', $roomId);
            });
        }

        if ($type !== null && $type !== '') {
            $query->where('type', (int) $type);
        }

        if ($dateFrom) {
            $query->whereHas('details', function ($dq) use ($dateFrom) {
                $dq->where('booking_date', '>=', $dateFrom);
            });
        }

        if ($dateTo) {
            $query->whereHas('details', function ($dq) use ($dateTo) {
                $dq->where('booking_date', '<=', $dateTo);
            });
        }

        if ($status !== null && $status !== '') {
            $query->where('status', (int) $status);
        }

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('id', 'like', "%{$search}%")
                  ->orWhere('pic_name', 'like', "%{$search}%")
                  ->orWhere('purpose', 'like', "%{$search}%")
                  ->orWhereHas('user', function ($uq) use ($search) {
                      $uq->where('name', 'like', "%{$search}%")
                         ->orWhere('email', 'like', "%{$search}%");
                  })
                  ->orWhereHas('details.room', function ($rq) use ($search) {
                      $rq->where('name', 'like', "%{$search}%")
                         ->orWhere('id', 'like', "%{$search}%");
                  });
            });
        }

        $bookings = $query->latest('id')->get();

        return view('bookings.index', compact(
            'bookings',
            'allPeriods',
            'rooms',
            'periodId',
            'roomId',
            'type',
            'status',
            'dateFrom',
            'dateTo',
            'search'
        ));
    }

    public function create(): View|RedirectResponse
    {
        $user = request()->user();
        if ($user && ! $user->isInternal()) {
            if (! $user->hasVerifiedEmail()) {
                return redirect()->route('verification.notice')->with('warning', 'Harap verifikasi email Anda terlebih dahulu.');
            }
            if ($user->status === 'rejected') {
                return redirect()->route('bookings.index')->with('error', 'Akun pendaftaran Anda tidak disetujui / ditolak oleh pengelola laboratorium.');
            }
            if ($user->status !== 'active' || ! $user->is_active) {
                return redirect()->route('bookings.index')->with('warning', 'Akun Anda saat ini masih berstatus "Menunggu Persetujuan" dari Kepala Lab / Staf Lab. Anda belum dapat mengajukan peminjaman laboratorium sampai akun Anda disetujui.');
            }
        }

        $rooms = Room::where('is_active', true)->orderBy('id')->get();

        return view('bookings.create', compact('rooms'));
    }

    public function show(Booking $booking): View
    {
        if (! request()->user()->isInternal() && $booking->user_id !== request()->user()->id) {
            abort(403);
        }

        $booking->load(['details.room', 'user', 'attachments']);

        return view('bookings.show', compact('booking'));
    }

    public function change(Booking $booking): View|RedirectResponse
    {
        $user = request()->user();
        if ($user && ! $user->isInternal()) {
            if (! $user->hasVerifiedEmail()) {
                return redirect()->route('verification.notice')->with('warning', 'Harap verifikasi email Anda terlebih dahulu.');
            }
            if ($user->status === 'rejected') {
                return redirect()->route('bookings.index')->with('error', 'Akun pendaftaran Anda tidak disetujui / ditolak oleh pengelola laboratorium.');
            }
            if ($user->status !== 'active' || ! $user->is_active) {
                return redirect()->route('bookings.index')->with('warning', 'Akun Anda saat ini masih berstatus "Menunggu Persetujuan" dari Kepala Lab / Staf Lab.');
            }
        }

        $firstDetail = $booking->details()->orderBy('booking_date')->orderBy('start_time')->first();
        $firstStart = $firstDetail ? Carbon::parse($firstDetail->booking_date->toDateString() . ' ' . $firstDetail->start_time) : null;

        $canChange = $booking->user_id === request()->user()->id
            && $booking->status === BookingStatus::APPROVED
            && $firstStart
            && $firstStart->gte(now()->addDays(2));

        abort_unless($canChange, 403, 'Perubahan jadwal hanya bisa diajukan paling lambat H-2.');

        $rooms = Room::where('is_active', true)->orderBy('id')->get();

        return view('bookings.change', compact('booking', 'rooms'));
    }

    public function store(
        Request $request,
        BookingService $service,
        NotificationService $notifications
    ): RedirectResponse {
        $user = $request->user();
        if ($user && ! $user->isInternal()) {
            if (! $user->hasVerifiedEmail()) {
                return redirect()->route('verification.notice')->with('warning', 'Harap verifikasi email Anda terlebih dahulu.');
            }
            if ($user->status === 'rejected') {
                return redirect()->route('bookings.index')->with('error', 'Akun pendaftaran Anda tidak disetujui / ditolak oleh pengelola laboratorium.');
            }
            if ($user->status !== 'active' || ! $user->is_active) {
                return redirect()->route('bookings.index')->with('warning', 'Akun Anda saat ini masih berstatus "Menunggu Persetujuan" dari Kepala Lab / Staf Lab. Anda belum dapat mengajukan peminjaman laboratorium sampai akun Anda disetujui.');
            }
        }
        $isChange = $request->input('type') === 'change' || $request->filled('parent_booking_id');
        $parentBooking = null;

        if ($isChange) {
            $parentBooking = Booking::findOrFail($request->input('parent_booking_id'));
            abort_unless($parentBooking->user_id === $request->user()->id || $request->user()->isInternal(), 403);

            if (! $request->filled('pic_name') && $request->filled('requester_name')) {
                $request->merge(['pic_name' => $request->input('requester_name')]);
            }
            if (! $request->filled('pic_name')) {
                $request->merge(['pic_name' => $parentBooking->pic_name]);
            }
            if (! $request->filled('pic_phone')) {
                $request->merge(['pic_phone' => $parentBooking->pic_phone]);
            }
            if (! $request->filled('purpose')) {
                $request->merge(['purpose' => $parentBooking->purpose]);
            }
        }

        $rules = [
            'pic_name' => ['required', 'string', 'max:70'],
            'pic_phone' => ['required', 'string', 'max:15'],
            'purpose' => ['required', 'string', 'max:255'],
            'slots' => ['required', 'array', 'min:1'],
            'slots.*.room_id' => ['required', 'exists:room,id'],
            'slots.*.date' => ['required', 'date'],
            'slots.*.start' => ['required', 'date_format:H:i'],
            'slots.*.end' => ['required', 'date_format:H:i', 'after:slots.*.start'],
            'attachments' => ['nullable', 'array', 'max:5'],
            'attachments.*' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png,doc,docx', 'max:5120'],
            'attachment' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png,doc,docx', 'max:5120'],
        ];

        if ($isChange) {
            $rules['notes'] = ['required', 'string', 'max:500'];
        }

        $data = $request->validate($rules, [
            'purpose.max' => 'Informasi terlalu panjang. Jika memerlukan deskripsi yang lebih panjang, mohon lampirkan melalui berkas pendukung (file).',
            'pic_name.max' => 'Nama peminjam / PIC tidak boleh lebih dari :max karakter.',
            'pic_phone.max' => 'Nomor telepon PIC tidak boleh lebih dari :max karakter.',
            'notes.required' => 'Alasan mengubah jadwal atau ruangan wajib diisi.',
            'notes.max' => 'Alasan perubahan maksimal :max karakter.',
        ]);

        $minAllowedStart = now()->addDays(2);
        $excludeBookingId = $parentBooking ? $parentBooking->id : null;

        foreach ($data['slots'] as $slot) {
            $dateCarbon = Carbon::parse($slot['date']);

            if ($dateCarbon->isSunday()) {
                throw ValidationException::withMessages([
                    'slots' => 'Peminjaman pada hari Minggu tidak diperkenankan.',
                ]);
            }

            $start = Carbon::parse("{$slot['date']} {$slot['start']}");
            $end = Carbon::parse("{$slot['date']} {$slot['end']}");

            if ($start->lt($minAllowedStart)) {
                throw ValidationException::withMessages([
                    'slots' => 'Waktu mulai peminjaman minimal 2 hari dari sekarang (H+2).',
                ]);
            }

            $labOpen = '07:00';
            $labClose = '22:00';

            if ($slot['start'] < $labOpen || $slot['end'] > $labClose) {
                throw ValidationException::withMessages([
                    'slots' => "Peminjaman harus berada di dalam jam operasional laboratorium ({$labOpen} - {$labClose}).",
                ]);
            }

            if (! $service->isAvailable($slot['room_id'], $start, $end, $excludeBookingId)) {
                throw ValidationException::withMessages([
                    'slots' => "Ruangan {$slot['room_id']} pada tanggal {$slot['date']} ({$slot['start']} - {$slot['end']}) sudah terisi oleh jadwal kuliah atau peminjaman lain.",
                ]);
            }
        }

        $booking = DB::transaction(function () use ($request, $data, $isChange, $parentBooking) {
            $user = $request->user();

            $booking = Booking::create([
                'user_id' => $user->id,
                'parent_booking_id' => $parentBooking ? $parentBooking->id : null,
                'type' => $isChange ? BookingType::RESCHEDULE : BookingType::NEW_BOOKING,
                'created_by' => null,
                'pic_name' => $data['pic_name'],
                'pic_phone' => $data['pic_phone'] ?? null,
                'purpose' => $data['purpose'],
                'status' => $isChange ? BookingStatus::PENDING_KALAB : BookingStatus::PENDING_PRODI,
                'decision_note' => $isChange ? ($data['notes'] ?? null) : null,
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

            $uploadedFiles = [];
            if ($request->hasFile('attachments')) {
                $rawFiles = $request->file('attachments');
                $uploadedFiles = is_array($rawFiles) ? $rawFiles : [$rawFiles];
            } elseif ($request->hasFile('attachment')) {
                $uploadedFiles = [$request->file('attachment')];
            }

            foreach ($uploadedFiles as $file) {
                if ($file && $file->isValid()) {
                    $originalName = mb_substr($file->getClientOriginalName(), 0, 100);
                    $file->storeAs("booking_attachments/{$booking->id}", $originalName, 'local');
                    BookingAttachment::updateOrCreate(
                        ['booking_id' => $booking->id, 'original_name' => $originalName],
                        ['created_at' => now()]
                    );
                }
            }

            $attCount = count($uploadedFiles);
            $attNote = $attCount > 0 ? " dengan {$attCount} berkas lampiran." : ".";

            if ($isChange) {
                ActivityLog::record(
                    $user->id,
                    'reschedule',
                    'booking',
                    $booking->id,
                    "Mengajukan perubahan jadwal/ruangan untuk peminjaman sebelumnya #{$parentBooking->id}. Alasan: {$data['notes']}",
                    $request->ip()
                );
            } else {
                ActivityLog::record(
                    $user->id,
                    'create',
                    'booking',
                    $booking->id,
                    "Mengajukan peminjaman ruangan #{$booking->id} untuk kegiatan: {$booking->purpose}{$attNote}",
                    $request->ip()
                );
            }

            return $booking;
        });

        if ($isChange) {
            $notifications->notifyRescheduleSubmitted($booking);
        } else {
            $notifications->notifyNewBooking($booking);
        }

        $successMsg = $isChange 
            ? 'Pengajuan perubahan jadwal berhasil dikirim dan langsung menunggu persetujuan Kepala Lab.' 
            : 'Pengajuan peminjaman berhasil dikirim.';

        return redirect()->route('bookings.show', $booking)->with('success', $successMsg);
    }

    public function cancel(Request $request, Booking $booking): RedirectResponse
    {
        $user = $request->user();
        if ((string) $booking->user_id !== (string) $user->id) {
            abort(403, 'Hanya peminjam yang bersangkutan yang dapat membatalkan peminjaman ini.');
        }

        $firstDetail = $booking->details()->orderBy('booking_date')->orderBy('start_time')->first();
        if ($firstDetail) {
            $firstStart = Carbon::parse($firstDetail->booking_date->toDateString() . ' ' . $firstDetail->start_time);
            if ($booking->status === BookingStatus::APPROVED && $firstStart->lt(now()->addDays(2))) {
                abort(403, 'Pembatalan mandiri untuk peminjaman yang disetujui hanya diperbolehkan paling lambat H-2.');
            }
            if ($firstStart->lt(now())) {
                abort(403, 'Peminjaman tidak dapat dibatalkan karena waktu kegiatan telah dimulai.');
            }
        }

        $request->validate([
            'reason' => ['nullable', 'string', 'max:150'],
        ]);

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

    public function showAttachment(Request $request, Booking $booking, BookingAttachment $attachment)
    {
        $user = $request->user();
        if ($booking->user_id !== $user->id && ! $user->isInternal()) {
            abort(403, 'Anda tidak memiliki hak akses untuk melihat berkas ini.');
        }

        if ((int) $attachment->booking_id !== (int) $booking->id) {
            abort(404);
        }

        if (! Storage::disk('local')->exists($attachment->path)) {
            abort(404, 'Berkas fisik tidak ditemukan di server.');
        }

        if ($request->boolean('download')) {
            return Storage::disk('local')->download($attachment->path, $attachment->original_name);
        }

        return Storage::disk('local')->response($attachment->path, $attachment->original_name);
    }
}
