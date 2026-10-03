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

        @if($booking->type === 'change' && $booking->parentBooking)
            <div class="alert alert-warning mb-4">
                <strong>Pengajuan Perubahan:</strong> Booking ini diajukan untuk mengubah jadwal dari booking sebelumnya 
                <a href="{{ route('bookings.show', $booking->parentBooking) }}" class="fw-bold">#{{ $booking->parentBooking->id }}</a>.
            </div>
        @endif

        <div class="card shadow-sm border-0 mb-4">
            <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
                <h2 class="h5 fw-bold mb-0">Informasi Peminjaman</h2>
                <div>
                    @if($booking->status === 'approved')
                        <span class="badge text-bg-success fs-6">Disetujui</span>
                    @elseif($booking->status === 'pending')
                        <span class="badge text-bg-warning fs-6">Menunggu Persetujuan</span>
                    @elseif($booking->status === 'rejected')
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
                        <div class="text-secondary small">Jumlah Peserta</div>
                        <div class="fw-bold fs-6">{{ $booking->participant_count }} Orang</div>
                    </div>
                    <div class="col-12">
                        <div class="text-secondary small">Tujuan Kegiatan</div>
                        <div class="fw-normal">{{ $booking->purpose }}</div>
                    </div>
                    @if($booking->notes)
                        <div class="col-12">
                            <div class="text-secondary small">Catatan / Keterangan</div>
                            <div class="alert alert-light border mb-0">{{ $booking->notes }}</div>
                        </div>
                    @endif
                </div>
            </div>
        </div>

        <div class="card shadow-sm border-0 mb-4">
            <div class="card-header bg-white py-3">
                <h2 class="h5 fw-bold mb-0">Daftar Ruangan & Waktu</h2>
            </div>
            <div class="card-body p-0">
                <table class="table table-bordered mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Ruangan</th>
                            <th>Tanggal</th>
                            <th>Waktu Mulai</th>
                            <th>Waktu Selesai</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($booking->roomBookings as $rb)
                            <tr>
                                <td class="fw-bold">{{ $rb->room->code ?? '-' }} ({{ $rb->room->name ?? 'Lab' }})</td>
                                <td>{{ $rb->start_datetime->format('l, d F Y') }}</td>
                                <td>{{ $rb->start_datetime->format('H:i') }}</td>
                                <td>{{ $rb->end_datetime->format('H:i') }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        <div class="card shadow-sm border-0 mb-4">
            <div class="card-header bg-white py-3">
                <h2 class="h5 fw-bold mb-0">Tahapan Persetujuan (Approval)</h2>
            </div>
            <div class="card-body">
                @if($booking->approvals->isEmpty())
                    <p class="text-muted mb-0">Peminjaman ini disetujui langsung tanpa melalui tahapan persetujuan (peminjaman internal staf/dosen).</p>
                @else
                    <div class="row g-3">
                        @foreach($booking->approvals->sortBy('level') as $appr)
                            <div class="col-md-6">
                                <div class="border rounded p-3 h-100 bg-light">
                                    <div class="d-flex justify-content-between align-items-center mb-2">
                                        <div class="fw-bold">
                                            Tahap {{ $appr->level }}: {{ $appr->level === 1 ? 'Kepala Prodi' : 'Kepala Lab' }}
                                        </div>
                                        <div>
                                            @if($appr->status === 'approved')
                                                <span class="badge text-bg-success">Setuju</span>
                                            @elseif($appr->status === 'rejected')
                                                <span class="badge text-bg-danger">Tolak</span>
                                            @else
                                                <span class="badge text-bg-warning">Pending</span>
                                            @endif
                                        </div>
                                    </div>
                                    <div class="small text-secondary mb-1">
                                        Pemeriksa: <span class="text-dark">{{ $appr->approver?->name ?? 'Belum diproses' }}</span>
                                    </div>
                                    <div class="small text-secondary mb-2">
                                        Waktu: <span class="text-dark">{{ $appr->decided_at ? $appr->decided_at->format('d/m/Y H:i') : '-' }}</span>
                                    </div>
                                    @if($appr->notes)
                                        <div class="small text-danger fw-semibold">Catatan: {{ $appr->notes }}</div>
                                    @endif
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>
        </div>

        @php
            $firstSlot = $booking->roomBookings->sortBy('start_datetime')->first();
            $canCancelOrChange = $booking->user_id === auth()->id()
                && $booking->status === 'approved'
                && $firstSlot
                && now()->lt(\Carbon\Carbon::parse($firstSlot->start_datetime)->subDays(2));
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
