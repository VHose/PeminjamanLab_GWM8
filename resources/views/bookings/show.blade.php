@extends('layouts.app')

@section('content')
<div class="row justify-content-center">
    <div class="col-lg-9">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h1 class="h3 fw-bold mb-1">Detail Peminjaman Ruangan #{{ $booking->id }}</h1>
                <p class="text-secondary mb-0">Diajukan pada {{ $booking->submitted_at ? $booking->submitted_at->format('d F Y, H:i') : $booking->created_at->format('d F Y, H:i') }}</p>
            </div>
            <a class="btn btn-outline-secondary" href="{{ route('bookings.index') }}">Kembali ke Daftar</a>
        </div>

        @if($booking->type === \App\Constants\BookingType::RESCHEDULE && $booking->parent)
            <div class="alert alert-warning mb-4">
                <strong>Pengajuan Perubahan:</strong> Booking ini diajukan untuk mengubah jadwal dari booking sebelumnya 
                <a href="{{ route('bookings.show', $booking->parent) }}" class="fw-bold">#{{ $booking->parent->id }}</a>.
            </div>
        @endif

        <div class="card shadow-sm border-0 mb-4">
            <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
                <h2 class="h5 fw-bold mb-0">Informasi Peminjaman</h2>
                <div>
                    @if($booking->status === \App\Constants\BookingStatus::APPROVED)
                        <span class="badge text-bg-success fs-6">Disetujui</span>
                    @elseif($booking->status === \App\Constants\BookingStatus::PENDING_KAPRODI)
                        <span class="badge text-bg-warning fs-6">Menunggu Kaprodi</span>
                    @elseif($booking->status === \App\Constants\BookingStatus::PENDING_KALAB)
                        <span class="badge text-bg-primary fs-6">Menunggu Kalab</span>
                    @elseif($booking->status === \App\Constants\BookingStatus::REJECTED)
                        <span class="badge text-bg-danger fs-6">Ditolak</span>
                    @else
                        <span class="badge text-bg-secondary fs-6">Dibatalkan</span>
                    @endif
                </div>
            </div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-6">
                        <div class="text-secondary small">Nama Peminjam / Penanggung Jawab</div>
                        <div class="fw-bold fs-6">{{ $booking->requester_name }}</div>
                    </div>
                    <div class="col-md-6">
                        <div class="text-secondary small">Tipe Pengajuan</div>
                        <div class="fw-bold fs-6">{{ $booking->type_label }}</div>
                    </div>
                    <div class="col-12">
                        <div class="text-secondary small">Tujuan Kegiatan</div>
                        <div class="fw-normal">{{ $booking->purpose }}</div>
                    </div>
                    @if($booking->notes)
                        <div class="col-12">
                            <div class="text-secondary small">Catatan / Alasan Penolakan Booking</div>
                            <div class="alert alert-light border mb-0 text-danger fw-semibold">{{ $booking->notes }}</div>
                        </div>
                    @endif
                </div>
            </div>
        </div>

        <div class="card shadow-sm border-0 mb-4">
            <div class="card-header bg-white py-3">
                <h2 class="h5 fw-bold mb-0">Daftar Ruangan & Status Persetujuan per Ruangan</h2>
            </div>
            <div class="card-body p-0">
                <table class="table table-bordered mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Ruangan</th>
                            <th>Tanggal</th>
                            <th>Waktu Mulai</th>
                            <th>Waktu Selesai</th>
                            <th>Status Ruangan</th>
                            <th>Catatan / Alasan</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($booking->details as $dt)
                            <tr>
                                <td class="fw-bold">{{ $dt->room->code ?? '-' }} ({{ $dt->room->name ?? 'Lab' }})</td>
                                <td>{{ $dt->start_datetime->format('l, d F Y') }}</td>
                                <td>{{ $dt->start_datetime->format('H:i') }}</td>
                                <td>{{ $dt->end_datetime->format('H:i') }}</td>
                                <td>
                                    @if($dt->status === \App\Constants\BookingDetailStatus::APPROVED)
                                        <span class="badge bg-success">Disetujui</span>
                                    @elseif($dt->status === \App\Constants\BookingDetailStatus::REJECTED)
                                        <span class="badge bg-danger">Ditolak</span>
                                    @elseif($dt->status === \App\Constants\BookingDetailStatus::CANCELLED)
                                        <span class="badge bg-secondary">Dibatalkan</span>
                                    @else
                                        <span class="badge bg-warning text-dark">Menunggu</span>
                                    @endif
                                </td>
                                <td>{{ $dt->notes ?? '-' }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        @php
            $firstSlot = $booking->details->sortBy('start_datetime')->first();
            $canCancelOrChange = $booking->user_id === auth()->id()
                && $booking->status === \App\Constants\BookingStatus::APPROVED
                && $firstSlot
                && now()->lte(\Carbon\Carbon::parse($firstSlot->start_datetime)->subDays(2));
        @endphp

        @if($canCancelOrChange)
            <div class="card shadow-sm border-0 mb-5">
                <div class="card-body d-flex justify-content-end gap-2">
                    <a class="btn btn-warning" href="{{ route('bookings.change', $booking) }}">Ajukan Ubah Jadwal / Ruangan</a>
                    <form method="post" action="{{ route('bookings.cancel', $booking) }}" onsubmit="return confirm('Apakah Anda yakin ingin membatalkan peminjaman ini? Tindakan ini tidak dapat diulang.')">
                        @csrf
                        <button type="submit" class="btn btn-danger">Batalkan Peminjaman</button>
                    </form>
                </div>
            </div>
        @endif
    </div>
</div>
@endsection
