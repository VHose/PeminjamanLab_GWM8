@extends('layouts.app')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
    <div>
        <h1 class="h3 fw-bold mb-1">Daftar Peminjaman Ruangan</h1>
        <p class="text-secondary small mb-0">Lacak status permohonan peminjaman laboratorium Anda di sini.</p>
    </div>
    <div class="d-flex gap-2">
        @if(!auth()->user()->isInternal() && !auth()->user()->hasVerifiedEmail())
            <a href="{{ route('verification.notice') }}" class="btn btn-warning">Verifikasi Email</a>
        @elseif(!auth()->user()->isInternal() && (auth()->user()->status !== 'active' || !auth()->user()->is_active))
            <button class="btn btn-secondary" disabled title="Menunggu persetujuan akun dari Kepala Lab / Staf Lab">Menunggu Persetujuan</button>
        @else
            <a href="{{ route('bookings.create') }}" class="btn btn-primary">
                Ajukan Peminjaman
            </a>
        @endif
    </div>
</div>

@if(!auth()->user()->isInternal())
    @if(!auth()->user()->hasVerifiedEmail())
        <div class="alert alert-warning d-flex align-items-center justify-content-between mb-4 shadow-sm" role="alert">
            <div>
                <strong>Email Belum Terverifikasi:</strong> Silakan verifikasi alamat email Anda agar dapat mengajukan peminjaman laboratorium.
            </div>
            <a href="{{ route('verification.notice') }}" class="btn btn-sm btn-warning text-dark fw-semibold text-nowrap ms-3">Verifikasi Sekarang</a>
        </div>
    @elseif(auth()->user()->status === 'pending' || !auth()->user()->is_active)
        <div class="alert alert-info d-flex align-items-center mb-4 shadow-sm" role="alert">
            <div>
                <strong>Pendaftaran Menunggu Persetujuan:</strong> Akun Anda telah terverifikasi email dan saat ini sedang menunggu persetujuan (approval) oleh Kepala Lab / Staf Lab. Tombol pengajuan peminjaman akan otomatis aktif setelah akun Anda disetujui.
            </div>
        </div>
    @elseif(auth()->user()->status === 'rejected')
        <div class="alert alert-danger d-flex align-items-center mb-4 shadow-sm" role="alert">
            <div>
                <strong>Akun Tidak Disetujui:</strong> Pendaftaran akun visitor Anda tidak disetujui oleh pengelola laboratorium. Silakan hubungi Staf Lab atau Kepala Lab.
            </div>
        </div>
    @endif
@endif

<div class="card shadow-sm border-0 mb-4">
    <div class="card-header bg-white py-3">
        <form method="get" action="{{ route('bookings.index') }}" class="row g-2 align-items-center">
            <div class="col-md-3">
                <label class="form-label small fw-semibold text-secondary mb-1">Periode Semester</label>
                <select name="period_id" class="form-select form-select-sm" onchange="this.form.submit()">
                    <option value="">-- Semua Periode --</option>
                    @foreach($allPeriods as $p)
                        <option value="{{ $p->id }}" {{ (string) request('period_id') === (string) $p->id ? 'selected' : '' }}>
                            {{ $p->name }} {{ $p->is_active ? '(Aktif)' : '' }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label small fw-semibold text-secondary mb-1">Ruangan Lab</label>
                <select name="room_id" class="form-select form-select-sm" onchange="this.form.submit()">
                    <option value="">-- Semua Ruangan --</option>
                    @foreach($rooms as $r)
                        <option value="{{ $r->id }}" {{ request('room_id') === $r->id ? 'selected' : '' }}>
                            {{ $r->code ?? $r->id }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label small fw-semibold text-secondary mb-1">Status</label>
                <select name="status" class="form-select form-select-sm" id="bookingStatusFilter" onchange="this.form.submit()">
                    <option value="">-- Semua Status --</option>
                    <option value="0" @selected(request('status') === '0')>Menunggu Kaprodi</option>
                    <option value="1" @selected(request('status') === '1')>Menunggu Kalab</option>
                    <option value="2" @selected(request('status') === '2')>Disetujui</option>
                    <option value="3" @selected(request('status') === '3')>Ditolak</option>
                    <option value="4" @selected(request('status') === '4')>Dibatalkan</option>
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label small fw-semibold text-secondary mb-1">Tipe</label>
                <select name="type" class="form-select form-select-sm" onchange="this.form.submit()">
                    <option value="">-- Semua Tipe --</option>
                    <option value="0" {{ request('type') === '0' ? 'selected' : '' }}>Baru</option>
                    <option value="1" {{ request('type') === '1' ? 'selected' : '' }}>Ubah Jadwal</option>
                </select>
            </div>
            <div class="col-md-3 d-flex gap-1 align-items-end pt-3">
                <button type="submit" class="btn btn-sm btn-primary flex-grow-1">Filter</button>
                @if(request()->hasAny(['period_id', 'room_id', 'status', 'type', 'search', 'date_from', 'date_to']))
                    <a href="{{ route('bookings.index') }}" class="btn btn-sm btn-outline-secondary">Reset</a>
                @endif
            </div>
        </form>
    </div>
    <div class="card-body p-3">
        <div class="table-responsive">
            <table class="table table-hover align-middle text-nowrap mb-0" id="bookingsTable">
                <thead class="table-light">
                    <tr>
                        <th class="text-start" style="width: 70px;">ID</th>
                        <th class="text-start">Peminjam</th>
                        <th class="text-start">Tujuan Kegiatan</th>
                        <th class="text-start">Ruangan & Jadwal</th>
                        <th class="text-start">Tipe</th>
                        <th class="text-start">Status</th>
                        <th class="text-start" style="width: 160px;">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($bookings as $booking)
                        @php
                            $firstSlot = $booking->details->sortBy('start_datetime')->first();
                            $statusVal = $booking->status instanceof \App\Enums\BookingStatus ? $booking->status->value : (int) $booking->status;
                            $isOwner = (string) $booking->user_id === (string) auth()->id();
                            $hasNotStarted = $firstSlot ? now()->lt(\Carbon\Carbon::parse($firstSlot->start_datetime)) : true;
                            $isHMinus2OrMore = $firstSlot ? now()->lte(\Carbon\Carbon::parse($firstSlot->start_datetime)->subDays(2)) : true;

                            $canCancel = $isOwner 
                                && in_array($statusVal, [0, 1, 2])
                                && ($statusVal === 2 ? $isHMinus2OrMore : $hasNotStarted);

                            $canChange = $isOwner
                                && $statusVal === 2
                                && $isHMinus2OrMore;
                        @endphp
                        <tr>
                            <td class="fw-semibold text-start">#{{ $booking->id }}</td>
                            <td class="text-start">
                                <div class="fw-bold">{{ $booking->pic_name ?? $booking->requester_name }}</div>
                                @if(auth()->user()->isInternal() && $booking->user)
                                    <div class="small text-muted">{{ $booking->user->email }}</div>
                                @endif
                            </td>
                            <td class="text-start">{{ Str::limit($booking->purpose, 40) }}</td>
                            <td class="text-start">
                                @foreach($booking->details as $dt)
                                    <div class="small mb-1">
                                        <span class="badge bg-secondary">{{ $dt->room->name ?? $dt->room->id ?? $dt->room_id ?? '' }}</span>
                                        {{ $dt->start_datetime->format('d/m/Y') }}:
                                        {{ $dt->start_datetime->format('H:i') }} - {{ $dt->end_datetime->format('H:i') }}
                                        <span class="badge {{ $dt->status?->value === 1 || $dt->status === 1 ? 'bg-success' : ($dt->status?->value === 2 || $dt->status === 2 ? 'bg-danger' : 'bg-warning text-dark') }}" style="font-size: 0.65rem;">
                                            {{ $dt->status_label }}
                                        </span>
                                    </div>
                                @endforeach
                            </td>
                            <td class="text-start">
                                @if($booking->type === \App\Constants\BookingType::RESCHEDULE)
                                    <span class="badge text-bg-warning">Ubah Jadwal</span>
                                @else
                                    <span class="badge text-bg-info">Baru</span>
                                @endif
                            </td>
                            <td class="text-start">
                                @if($statusVal === 2)
                                    <span class="badge text-bg-success">Disetujui</span>
                                @elseif($statusVal === 0)
                                    <span class="badge text-bg-warning">Menunggu Kaprodi</span>
                                @elseif($statusVal === 1)
                                    <span class="badge text-bg-primary">Menunggu Kalab</span>
                                @elseif($statusVal === 3)
                                    <span class="badge text-bg-danger">Ditolak</span>
                                @else
                                    <span class="badge text-bg-secondary">Dibatalkan</span>
                                @endif
                            </td>
                            <td class="text-start">
                                <div class="d-inline-flex gap-1 align-items-center">
                                    <a class="btn btn-sm btn-outline-primary" href="{{ route('bookings.show', $booking) }}">Detail</a>
                                    @if($canChange)
                                        <a class="btn btn-sm btn-outline-warning" href="{{ route('bookings.change', $booking) }}">Ubah</a>
                                    @endif
                                    @if($canCancel)
                                        <form method="post" action="{{ route('bookings.cancel', $booking) }}" class="d-inline m-0 p-0" onsubmit="return confirm('Apakah Anda yakin ingin membatalkan peminjaman ini?')">
                                            @csrf
                                            <button type="submit" class="btn btn-sm btn-outline-danger">Batal</button>
                                        </form>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
$(document).ready(function() {
    if ($.fn.DataTable.isDataTable('#bookingsTable')) {
        $('#bookingsTable').DataTable().destroy();
    }
    $('#bookingsTable').DataTable({
        order: [[0, 'desc']],
        columnDefs: [
            { orderable: false, targets: [6] }
        ]
    });
});
</script>
@endpush
