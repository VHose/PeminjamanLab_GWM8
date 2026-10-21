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
                    @php
                        $statusVal = $booking->status instanceof \App\Enums\BookingStatus ? $booking->status->value : (int) $booking->status;
                    @endphp
                    @if($statusVal === 2)
                        <span class="badge text-bg-success fs-6">Disetujui</span>
                    @elseif($statusVal === 0)
                        <span class="badge text-bg-warning fs-6">Menunggu Kaprodi</span>
                    @elseif($statusVal === 1)
                        <span class="badge text-bg-primary fs-6">Menunggu Kalab</span>
                    @elseif($statusVal === 3)
                        <span class="badge text-bg-danger fs-6">Ditolak</span>
                    @else
                        <span class="badge text-bg-secondary fs-6">{{ $booking->status_label }}</span>
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
                        <div class="text-secondary small">Kontak PIC (Telepon / WA)</div>
                        <div class="fw-bold fs-6">{{ $booking->pic_phone ?: '-' }}</div>
                    </div>
                    <div class="col-12">
                        <div class="text-secondary small">Tujuan Kegiatan</div>
                        <div class="fw-normal">{{ $booking->purpose }}</div>
                    </div>
                    @if($booking->decision_note ?? $booking->notes ?? null)
                        <div class="col-12">
                            <div class="text-secondary small">Catatan / Alasan Keputusan</div>
                            <div class="alert alert-light border mb-0 text-danger fw-semibold">{{ $booking->decision_note ?? $booking->notes }}</div>
                        </div>
                    @endif
                </div>
            </div>
        </div>

        <div class="card shadow-sm border-0 mb-4">
            <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
                <h2 class="h5 fw-bold mb-0">
                    <i class="bi bi-paperclip me-1 text-primary"></i> Berkas Pendukung Kegiatan
                </h2>
                <span class="badge text-bg-light border text-secondary">{{ $booking->attachments->count() }} Berkas</span>
            </div>
            <div class="card-body">
                @if($booking->attachments->isEmpty())
                    <p class="text-secondary mb-0 fst-italic">
                        <i class="bi bi-info-circle me-1"></i> Tidak ada berkas lampiran (surat izin, poster, atau proposal) yang diunggah untuk peminjaman ini.
                    </p>
                @else
                    <div class="list-group list-group-flush border rounded">
                        @foreach($booking->attachments as $att)
                            <div class="list-group-item d-flex justify-content-between align-items-center py-3 flex-wrap gap-2">
                                <div class="d-flex align-items-center gap-3">
                                    <div class="fs-2 text-center" style="width: 40px;">
                                        <i class="bi {{ $att->icon_class }}"></i>
                                    </div>
                                    <div>
                                        <div class="fw-semibold text-dark">{{ $att->original_name }}</div>
                                        <div class="small text-secondary">
                                            {{ $att->formatted_size }} &bull; Diunggah {{ $att->created_at ? $att->created_at->translatedFormat('d F Y, H:i') : '-' }}
                                        </div>
                                    </div>
                                </div>
                                <div class="d-flex gap-2">
                                    <a href="{{ route('bookings.attachments.show', [$booking, $att]) }}" target="_blank" class="btn btn-sm btn-outline-primary">
                                        <i class="bi bi-eye"></i> Buka / Pratinjau
                                    </a>
                                    <a href="{{ route('bookings.attachments.show', [$booking, $att, 'download' => 1]) }}" class="btn btn-sm btn-primary">
                                        <i class="bi bi-download"></i> Unduh
                                    </a>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif
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
                                <td class="fw-bold">{{ $dt->room->id ?? '-' }} ({{ $dt->room->name ?? 'Lab' }})</td>
                                <td>{{ $dt->start_datetime->format('l, d F Y') }}</td>
                                <td>{{ $dt->start_datetime->format('H:i') }}</td>
                                <td>{{ $dt->end_datetime->format('H:i') }}</td>
                                <td>
                                    @php
                                        $dStatusVal = $dt->status instanceof \App\Enums\BookingDetailStatus ? $dt->status->value : (int) $dt->status;
                                    @endphp
                                    @if($dStatusVal === 1)
                                        <span class="badge bg-success">Disetujui</span>
                                    @elseif($dStatusVal === 2)
                                        <span class="badge bg-danger">Ditolak</span>
                                    @elseif($dStatusVal === 3)
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
                && $statusVal === 2
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
