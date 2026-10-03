@extends('layouts.app')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h1 class="h3 fw-bold mb-1">Persetujuan Peminjaman Laboratorium</h1>
        <p class="text-secondary mb-0">
            Peran: <span class="badge bg-primary">{{ $level === 1 ? 'Kepala Program Studi (Tahap 1)' : 'Kepala Laboratorium (Tahap 2)' }}</span>
        </p>
    </div>
</div>

<div class="card shadow-sm border-0 mb-4">
    <div class="card-header bg-white py-3">
        <h2 class="h5 fw-bold mb-0">Daftar Pengajuan Menunggu Persetujuan ({{ $pendingApprovals->total() }})</h2>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>ID</th>
                        <th>Tipe</th>
                        <th>Pemohon</th>
                        <th>Tujuan Kegiatan</th>
                        <th>Peserta</th>
                        <th>Ruangan & Jadwal</th>
                        <th class="text-center" style="width: 250px;">Keputusan</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($pendingApprovals as $approval)
                        @php
                            $booking = $approval->booking;
                        @endphp
                        <tr>
                            <td class="fw-semibold">#{{ $booking->id }}</td>
                            <td>
                                @if($booking->type === 'change')
                                    <span class="badge text-bg-warning">Perubahan</span>
                                @else
                                    <span class="badge text-bg-info">Baru</span>
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
                                @if($booking->type === 'change' && $booking->notes)
                                    <div class="small text-danger mt-1">Alasan Perubahan: {{ $booking->notes }}</div>
                                @endif
                            </td>
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
                                <div class="d-flex flex-column gap-2">
                                    <form method="post" action="{{ route('approvals.decide', $booking) }}">
                                        @csrf
                                        <input type="hidden" name="status" value="approved">
                                        <button type="submit" class="btn btn-sm btn-success w-100" onclick="return confirm('Setujui pengajuan peminjaman ini?')">
                                            Setujui
                                        </button>
                                    </form>

                                    <button type="button" class="btn btn-sm btn-outline-danger w-100" data-bs-toggle="collapse" data-bs-target="#rejectBox-{{ $approval->id }}">
                                        Tolak...
                                    </button>

                                    <div class="collapse mt-2" id="rejectBox-{{ $approval->id }}">
                                        <form method="post" action="{{ route('approvals.decide', $booking) }}" class="border p-2 rounded bg-light">
                                            @csrf
                                            <input type="hidden" name="status" value="rejected">
                                            <label class="form-label small fw-bold text-danger mb-1">Alasan Penolakan (Wajib):</label>
                                            <textarea class="form-control form-control-sm mb-2" name="notes" rows="2" placeholder="Tuliskan alasan penolakan..." required></textarea>
                                            <button type="submit" class="btn btn-danger btn-sm w-100">Kirim Penolakan</button>
                                        </form>
                                    </div>
                                </div>
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
    @if($pendingApprovals->hasPages())
        <div class="card-footer bg-white">
            {{ $pendingApprovals->links() }}
        </div>
    @endif
</div>

<div class="card shadow-sm border-0 mb-5">
    <div class="card-header bg-white py-3">
        <h2 class="h5 fw-bold mb-0">Riwayat Keputusan Terbaru</h2>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0 text-sm">
                <thead class="table-light">
                    <tr>
                        <th>ID</th>
                        <th>Pemohon</th>
                        <th>Tujuan</th>
                        <th>Ruangan</th>
                        <th>Keputusan</th>
                        <th>Catatan</th>
                        <th>Waktu Keputusan</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($recentDecisions as $dec)
                        <tr>
                            <td>#{{ $dec->booking->id }}</td>
                            <td>{{ $dec->booking->requester_name }}</td>
                            <td>{{ Str::limit($dec->booking->purpose, 30) }}</td>
                            <td>
                                @foreach($dec->booking->roomBookings as $rb)
                                    <span class="badge bg-secondary">{{ $rb->room->code ?? '-' }}</span>
                                @endforeach
                            </td>
                            <td>
                                @if($dec->status === 'approved')
                                    <span class="badge text-bg-success">Disetujui</span>
                                @else
                                    <span class="badge text-bg-danger">Ditolak</span>
                                @endif
                            </td>
                            <td class="small">{{ $dec->notes ?? '-' }}</td>
                            <td class="small">{{ $dec->decided_at ? $dec->decided_at->format('d/m/Y H:i') : '-' }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center py-3 text-muted">Belum ada riwayat persetujuan.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
