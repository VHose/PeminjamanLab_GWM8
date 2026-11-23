@extends('layouts.app')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h1 class="h3 fw-bold mb-1">Daftar Peminjaman Ruangan</h1>
        <p class="text-secondary small mb-0">Lacak status permohonan peminjaman laboratorium Anda di sini.</p>
    </div>
    <div class="d-flex gap-2">
        @if(auth()->user()->hasRole('Staf_Lab'))
            <a class="btn btn-outline-primary" href="{{ route('staff-bookings.create') }}">Input Peminjaman Dosen</a>
        @endif
        <a href="{{ route('bookings.create') }}" class="btn btn-primary">
            Ajukan Peminjaman
        </a>
    </div>
</div>

<div class="card shadow-sm border-0 mb-4">
    <div class="card-header bg-white py-3">
        <form method="get" action="{{ route('bookings.index') }}" class="row g-2 align-items-center">
            <div class="col-md-5">
                <input type="text" id="bookingSearchInput" name="search" value="{{ request('search') }}" class="form-control" placeholder="Cari nama peminjam, ID booking, tujuan...">
            </div>
            <div class="col-md-3">
                <select name="status" class="form-select" id="bookingStatusFilter" onchange="this.form.submit()">
                    <option value="">Semua Status</option>
                    <option value="0" @selected(request('status') === '0')>Menunggu Kaprodi</option>
                    <option value="1" @selected(request('status') === '1')>Menunggu Kalab</option>
                    <option value="2" @selected(request('status') === '2')>Disetujui</option>
                    <option value="3" @selected(request('status') === '3')>Ditolak</option>
                    <option value="4" @selected(request('status') === '4')>Dibatalkan</option>
                </select>
            </div>
            <div class="col-md-4 d-flex gap-1 justify-content-md-end">
                <button type="submit" class="btn btn-outline-primary">Cari</button>
                @if(request('search') || request('status') !== null)
                    <a href="{{ route('bookings.index') }}" class="btn btn-outline-secondary">Reset</a>
                @endif
            </div>
        </form>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0" id="bookingsTable">
                <thead class="table-light">
                    <tr>
                        <th style="width: 70px;">ID</th>
                        <th>Peminjam</th>
                        <th>Tujuan Kegiatan</th>
                        <th>Ruangan & Jadwal</th>
                        <th>Tipe</th>
                        <th>Status</th>
                        <th class="text-end" style="width: 160px;">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($bookings as $booking)
                        @php
                            $firstSlot = $booking->details->sortBy('start_datetime')->first();
                            $statusVal = $booking->status instanceof \App\Enums\BookingStatus ? $booking->status->value : (int) $booking->status;
                            $canCancelOrChange = $booking->user_id === auth()->id()
                                && $statusVal === 2
                                && $firstSlot
                                && now()->lte(\Carbon\Carbon::parse($firstSlot->start_datetime)->subDays(2));
                        @endphp
                        <tr>
                            <td class="fw-semibold">#{{ $booking->id }}</td>
                            <td>
                                <div class="fw-bold">{{ $booking->pic_name ?? $booking->requester_name }}</div>
                                @if(auth()->user()->isInternal() && $booking->user)
                                    <div class="small text-muted">{{ $booking->user->email }}</div>
                                @endif
                            </td>
                            <td>{{ Str::limit($booking->purpose, 40) }}</td>
                            <td>
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
                            <td>
                                @if($booking->type === \App\Constants\BookingType::RESCHEDULE)
                                    <span class="badge text-bg-warning">Ubah Jadwal</span>
                                @else
                                    <span class="badge text-bg-info">Baru</span>
                                @endif
                            </td>
                            <td>
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
                            <td class="text-end">
                                <div class="d-inline-flex gap-1 align-items-center justify-content-end">
                                    <a class="btn btn-sm btn-outline-primary" href="{{ route('bookings.show', $booking) }}">Detail</a>
                                    @if($canCancelOrChange)
                                        <a class="btn btn-sm btn-outline-warning" href="{{ route('bookings.change', $booking) }}">Ubah</a>
                                        <form method="post" action="{{ route('bookings.cancel', $booking) }}" class="d-inline m-0 p-0" onsubmit="return confirm('Apakah Anda yakin ingin membatalkan peminjaman ini? Paling lambat H-2.')">
                                            @csrf
                                            <button type="submit" class="btn btn-sm btn-outline-danger">Batal</button>
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

<script>
document.addEventListener('DOMContentLoaded', function () {
    const searchInput = document.getElementById('bookingSearchInput');
    const tableBody = document.querySelector('#bookingsTable tbody');
    if (searchInput && tableBody) {
        searchInput.addEventListener('keyup', function () {
            const filter = this.value.toLowerCase().trim();
            const rows = tableBody.querySelectorAll('tr');
            rows.forEach(function (row) {
                if (row.cells.length > 1) {
                    const text = row.textContent.toLowerCase();
                    row.style.display = text.includes(filter) ? '' : 'none';
                }
            });
        });
    }
});
</script>
@endsection
