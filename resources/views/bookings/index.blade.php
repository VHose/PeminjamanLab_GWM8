@extends('layouts.app')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h1 class="h3 fw-bold mb-1">Daftar Peminjaman Ruangan</h1>
        <p class="text-secondary mb-0">Kelola dan pantau status pengajuan peminjaman laboratorium.</p>
    </div>
    <div class="d-flex gap-2">
        @if(auth()->user()->isInternal())
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
                        <th>Peserta</th>
                        <th>Ruangan & Jadwal</th>
                        <th>Tipe</th>
                        <th>Status</th>
                        <th class="text-end">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($bookings as $booking)
                        @php
                            $firstSlot = $booking->roomBookings->sortBy('start_datetime')->first();
                            $canCancelOrChange = $booking->user_id === auth()->id()
                                && $booking->status === 'approved'
                                && $firstSlot
                                && now()->lt(\Carbon\Carbon::parse($firstSlot->start_datetime)->subDays(2));
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
                            <td>{{ $booking->participant_count }} Orang</td>
                            <td>
                                @foreach($booking->roomBookings as $rb)
                                    <div class="small">
                                        <span class="badge bg-secondary">{{ $rb->room->code ?? '-' }}</span>
                                        {{ $rb->start_datetime->format('d/m/Y') }}:
                                        {{ $rb->start_datetime->format('H:i') }} - {{ $rb->end_datetime->format('H:i') }}
                                    </div>
                                @endforeach
                            </td>
                            <td>
                                @if($booking->type === 'change')
                                    <span class="badge text-bg-warning">Perubahan</span>
                                @else
                                    <span class="badge text-bg-info">Baru</span>
                                @endif
                            </td>
                            <td>
                                @if($booking->status === 'approved')
                                    <span class="badge text-bg-success">Disetujui</span>
                                @elseif($booking->status === 'pending')
                                    <span class="badge text-bg-warning">Pending</span>
                                @elseif($booking->status === 'rejected')
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
                                        <form method="post" action="{{ route('bookings.cancel', $booking) }}" class="d-inline" onsubmit="return confirm('Apakah Anda yakin ingin membatalkan peminjaman ini? Tindakan ini tidak dapat diulang.')">
                                            @csrf
                                            <button type="submit" class="btn btn-outline-danger">Batal</button>
                                        </form>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="text-center py-4 text-muted">Belum ada data peminjaman yang tercatat.</td>
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
