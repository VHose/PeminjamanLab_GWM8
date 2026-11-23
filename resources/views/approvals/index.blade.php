@extends('layouts.app')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h1 class="h3 fw-bold mb-1">Persetujuan Peminjaman Laboratorium</h1>
    </div>
</div>

<div class="card shadow-sm border-0 mb-4">
    <div class="card-header bg-white py-3">
        <div class="row g-2 align-items-center justify-content-between">
            <div class="col-md-5">
                <h2 class="h5 fw-bold mb-0">Daftar Pengajuan Menunggu Persetujuan ({{ $pendingBookings->total() }})</h2>
            </div>
            <div class="col-md-7">
                <form method="get" action="{{ route('approvals.index') }}" class="d-flex gap-2 justify-content-md-end">
                    <input type="text" id="approvalSearchInput" name="search" value="{{ request('search') }}" class="form-control form-control-sm" style="max-width: 280px;" placeholder="Cari pemohon, ID, ruangan...">
                    <button type="submit" class="btn btn-sm btn-outline-primary">Cari</button>
                    @if(request('search'))
                        <a href="{{ route('approvals.index') }}" class="btn btn-sm btn-outline-secondary">Reset</a>
                    @endif
                </form>
            </div>
        </div>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle text-nowrap mb-0" id="pendingBookingsTable">
                <thead class="table-light">
                    <tr>
                        <th class="text-start">ID</th>
                        <th class="text-start">Tipe</th>
                        <th class="text-start">Status Booking</th>
                        <th class="text-start">Pemohon</th>
                        <th class="text-start">Tujuan</th>
                        <th class="text-start">Ruangan & Jadwal</th>
                        <th class="text-start" style="width: 320px;">Aksi Keputusan</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($pendingBookings as $booking)
                        @php
                            $bStatusVal = $booking->status instanceof \App\Enums\BookingStatus ? $booking->status->value : (int) $booking->status;
                        @endphp
                        <tr>
                            <td class="fw-semibold">#{{ $booking->id }}</td>
                            <td>
                                @if($booking->type === \App\Constants\BookingType::RESCHEDULE)
                                    <span class="badge text-bg-warning">Ubah Jadwal</span>
                                @else
                                    <span class="badge text-bg-info">Baru</span>
                                @endif
                            </td>
                            <td>
                                @if($bStatusVal === 0)
                                    <span class="badge bg-secondary">Menunggu Kaprodi</span>
                                @elseif($bStatusVal === 1)
                                    <span class="badge bg-primary">Menunggu Kalab</span>
                                @else
                                    <span class="badge bg-secondary">{{ $booking->status_label }}</span>
                                @endif
                            </td>
                            <td>
                                <div class="fw-bold">{{ $booking->requester_name }}</div>
                                @if($booking->requester)
                                    <div class="small text-muted">{{ $booking->requester->email }}</div>
                                @endif
                            </td>
                            <td>
                                <div>{{ $booking->purpose }}</div>
                                @if($booking->type === \App\Constants\BookingType::RESCHEDULE && $booking->notes)
                                    <div class="small text-danger mt-1">Alasan: {{ $booking->notes }}</div>
                                @endif
                                @if($booking->attachments && $booking->attachments->isNotEmpty())
                                    <div class="mt-2 pt-2 border-top">
                                        <div class="small fw-semibold text-secondary mb-1">
                                            Berkas Lampiran ({{ $booking->attachments->count() }}):
                                        </div>
                                        @foreach($booking->attachments as $att)
                                            <a href="{{ route('bookings.attachments.show', [$booking, $att]) }}" target="_blank" class="d-inline-flex align-items-center gap-1 small text-decoration-none badge text-bg-light border text-dark me-1 mb-1 p-1 px-2" title="Buka {{ $att->original_name }}">
                                                <span class="text-truncate" style="max-width: 140px;">{{ $att->original_name }}</span>
                                                <span class="text-muted">({{ $att->formatted_size }})</span>
                                            </a>
                                        @endforeach
                                    </div>
                                @endif
                            </td>
                            <td>
                                @foreach($booking->details as $dt)
                                    @php
                                        $dStatusVal = $dt->status instanceof \App\Enums\BookingDetailStatus ? $dt->status->value : (int) $dt->status;
                                    @endphp
                                    <div class="small mb-1 p-1 rounded bg-light border">
                                        <span class="badge bg-dark">{{ $dt->room->name ?? $dt->room->id ?? $dt->room_id ?? '-' }}</span>
                                        {{ $dt->start_datetime->format('d/m/Y') }}:
                                        {{ $dt->start_datetime->format('H:i') }} - {{ $dt->end_datetime->format('H:i') }}
                                        <div>
                                            Status:
                                            @if($dStatusVal === 1)
                                                <span class="badge bg-success">Disetujui</span>
                                            @elseif($dStatusVal === 2)
                                                <span class="badge bg-danger">Ditolak</span>
                                            @elseif($dStatusVal === 3)
                                                <span class="badge bg-secondary">Dibatalkan</span>
                                            @else
                                                <span class="badge bg-warning text-dark">Menunggu</span>
                                            @endif
                                            @if($dt->notes)
                                                <span class="text-danger">({{ $dt->notes }})</span>
                                            @endif
                                        </div>
                                    </div>
                                @endforeach
                            </td>
                            <td>
                                @if($bStatusVal === 0 && $isKaprodi)
                                    <div class="d-flex flex-column gap-2 border p-2 rounded bg-light">
                                        
                                        <form method="post" action="{{ route('approvals.decideKaprodi', $booking) }}">
                                            @csrf
                                            <input type="hidden" name="status" value="approved">
                                            <button type="submit" class="btn btn-sm btn-success w-100" onclick="return confirm('Setujui pengajuan ini dan teruskan ke Kepala Lab?')">
                                                Setujui (Lanjut ke Kalab)
                                            </button>
                                        </form>

                                        <button type="button" class="btn btn-sm btn-outline-danger w-100" data-bs-toggle="collapse" data-bs-target="#rejectKaprodi-{{ $booking->id }}">
                                            Tolak Pengajuan...
                                        </button>

                                        <div class="collapse mt-2" id="rejectKaprodi-{{ $booking->id }}">
                                            <form method="post" action="{{ route('approvals.decideKaprodi', $booking) }}">
                                                @csrf
                                                <input type="hidden" name="status" value="rejected">
                                                <label class="form-label small fw-bold text-danger mb-1">Alasan Penolakan (Wajib):</label>
                                                <textarea class="form-control form-control-sm mb-2" name="notes" rows="2" placeholder="Tuliskan alasan penolakan..." required></textarea>
                                                <button type="submit" class="btn btn-danger btn-sm w-100">Tolak Booking</button>
                                            </form>
                                        </div>
                                    </div>
                                @elseif($bStatusVal === 1 && $isKalab)
                                    <div class="d-flex flex-column gap-2">
                                        
                                        @foreach($booking->details as $dtIndex => $dt)
                                            @php
                                                $dStatusVal = $dt->status instanceof \App\Enums\BookingDetailStatus ? $dt->status->value : (int) $dt->status;
                                            @endphp
                                            @if($dStatusVal === 0)
                                                <div class="border p-2 rounded bg-white small mb-1">
                                                    <div class="fw-bold mb-1">{{ $dt->room->name ?? $dt->room->id ?? $dt->room_id }} ({{ $dt->start_datetime->format('H:i') }}-{{ $dt->end_datetime->format('H:i') }})</div>
                                                    <div class="d-flex gap-1">
                                                        <form method="post" action="{{ route('approvals.decideKalabDetail', $dt) }}" class="flex-grow-1">
                                                            @csrf
                                                            <input type="hidden" name="status" value="approved">
                                                            <button type="submit" class="btn btn-sm btn-success w-100 py-0" style="font-size: 0.75rem;">Setujui</button>
                                                        </form>
                                                        <button type="button" class="btn btn-sm btn-outline-danger py-0" style="font-size: 0.75rem;" data-bs-toggle="collapse" data-bs-target="#rejectDetail-{{ $booking->id }}-{{ $dtIndex }}">
                                                            Tolak...
                                                        </button>
                                                    </div>
                                                    <div class="collapse mt-2" id="rejectDetail-{{ $booking->id }}-{{ $dtIndex }}">
                                                        <form method="post" action="{{ route('approvals.decideKalabDetail', $dt) }}">
                                                            @csrf
                                                            <input type="hidden" name="status" value="rejected">
                                                            <textarea class="form-control form-control-sm mb-1" name="notes" rows="1" placeholder="Alasan tolak..." required style="font-size: 0.75rem;"></textarea>
                                                            <button type="submit" class="btn btn-danger btn-sm w-100 py-0" style="font-size: 0.75rem;">Kirim Tolak</button>
                                                        </form>
                                                    </div>
                                                </div>
                                            @else
                                                <div class="small text-muted mb-1">{{ $dt->room->name ?? $dt->room->id ?? $dt->room_id }}: Sudah diputuskan</div>
                                            @endif
                                        @endforeach
                                    </div>
                                @else
                                    <span class="small text-muted">Menunggu tahap lain / Tidak ada aksi</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center py-4 text-muted">Tidak ada pengajuan yang sedang menunggu persetujuan Anda saat ini.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    @if($pendingBookings->hasPages())
        <div class="card-footer bg-white">
            {{ $pendingBookings->links() }}
        </div>
    @endif
</div>

@push('scripts')
<script>
$(document).ready(function() {
    if ($.fn.DataTable.isDataTable('#pendingBookingsTable')) {
        $('#pendingBookingsTable').DataTable().destroy();
    }
    $('#pendingBookingsTable').DataTable({
        order: [[0, 'desc']],
        columnDefs: [
            { orderable: false, targets: [6] }
        ]
    });
});
</script>
@endpush
@endsection
