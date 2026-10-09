@extends('layouts.app')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h1 class="h3 fw-bold mb-1">Daftar Peminjaman Ruangan</h1>
        <p class="text-secondary mb-0">Kelola dan pantau status pengajuan peminjaman laboratorium.</p>
    </div>
    <div class="d-flex gap-2">
        @if(auth()->user()->hasRole('Staf_Lab'))
            <a class="btn btn-outline-primary" href="{{ route('staff-bookings.create') }}">Input Peminjaman Dosen</a>
        @endif
        <a class="btn btn-primary" href="{{ route('bookings.create') }}">Ajukan Peminjaman Baru</a>
    </div>
</div>

<div class="card shadow-sm border-0">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>ID</th>
                        <th>Peminjam</th>
                        <th>Tujuan Kegiatan</th>
                        <th>Ruangan & Jadwal</th>
                        <th>Tipe</th>
                        <th>Status</th>
                        <th class="text-end">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($bookings as $booking)
                        @php
                            $firstSlot = $booking->details->sortBy('start_datetime')->first();
                            $canCancelOrChange = $booking->user_id === auth()->id()
                                && $booking->status === \App\Constants\BookingStatus::APPROVED
                                && $firstSlot
                                && now()->lte(\Carbon\Carbon::parse($firstSlot->start_datetime)->subDays(2));
                        @endphp
                        <tr>
                            <td class="fw-semibold">#{{ $booking->id }}</td>
                            <td>
                                <div class="fw-bold">{{ $booking->requester_name }}</div>
                                @if(auth()->user()->isInternal() && $booking->requester)
                                    <div class="small text-muted">{{ $booking->requester->email }}</div>
                                @endif
                            </td>
                            <td>{{ Str::limit($booking->purpose, 40) }}</td>
                            <td>
                                @foreach($booking->details as $dt)
                                    <div class="small">
                                        <span class="badge bg-secondary">{{ $dt->room->code ?? '-' }}</span>
                                        {{ $dt->start_datetime->format('d/m/Y') }}:
                                        {{ $dt->start_datetime->format('H:i') }} - {{ $dt->end_datetime->format('H:i') }}
                                        <span class="badge {{ $dt->status === 1 ? 'bg-success' : ($dt->status === 2 ? 'bg-danger' : 'bg-warning text-dark') }}" style="font-size: 0.65rem;">
                                            {{ $dt->status_label }}
                                        </span>
                                    </div>
                                @endforeach
                            </td>
                            <td>
                                @if($booking->type === \App\Constants\BookingType::RESCHEDULE)
                                    <span class="badge text-bg-warning">Ubah Jadwal</span>
                                @else
                                    <span class="badge text-bg-info">Baru</span>
                                @endif
                            </td>
                            <td>
                                @if($booking->status === \App\Constants\BookingStatus::APPROVED)
                                    <span class="badge text-bg-success">Disetujui</span>
                                @elseif($booking->status === \App\Constants\BookingStatus::PENDING_KAPRODI)
                                    <span class="badge text-bg-warning">Menunggu Kaprodi</span>
                                @elseif($booking->status === \App\Constants\BookingStatus::PENDING_KALAB)
                                    <span class="badge text-bg-primary">Menunggu Kalab</span>
                                @elseif($booking->status === \App\Constants\BookingStatus::REJECTED)
                                    <span class="badge text-bg-danger">Ditolak</span>
                                @else
                                    <span class="badge text-bg-secondary">Dibatalkan</span>
                                @endif
                            </td>
                            <td class="text-end">
                                <div class="btn-group btn-group-sm">
                                    <a class="btn btn-outline-secondary" href="{{ route('bookings.show', $booking) }}">Detail</a>
                                    @if($canCancelOrChange)
                                        <a class="btn btn-outline-primary" href="{{ route('bookings.change', $booking) }}">Ubah</a>
                                        <form method="post" action="{{ route('bookings.cancel', $booking) }}" class="d-inline" onsubmit="return confirm('Apakah Anda yakin ingin membatalkan peminjaman ini? Paling lambat H-2.')">
                                            @csrf
                                            <button type="submit" class="btn btn-outline-danger">Batal</button>
                                        </form>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center py-4 text-muted">Belum ada data peminjaman yang tercatat.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    @if($bookings->hasPages())
        <div class="card-footer bg-white">
            {{ $bookings->links() }}
        </div>
    @endif
</div>
@endsection
