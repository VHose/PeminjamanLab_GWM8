@extends('layouts.app')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h1 class="h3 fw-bold mb-1">Persetujuan Peminjaman Laboratorium</h1>
        <p class="text-secondary mb-0">
            Peran Aktif: 
            @if($isKaprodi)
                <span class="badge bg-primary">Kepala Program Studi (Tahap 1 - Pengajuan)</span>
            @endif
            @if($isKalab)
                <span class="badge bg-success">Kepala Laboratorium (Tahap 2 - Per Ruangan)</span>
            @endif
        </p>
    </div>
</div>

<div class="card shadow-sm border-0 mb-4">
    <div class="card-header bg-white py-3">
        <h2 class="h5 fw-bold mb-0">Daftar Pengajuan Menunggu Persetujuan ({{ $pendingBookings->total() }})</h2>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>ID</th>
                        <th>Tipe</th>
                        <th>Status Booking</th>
                        <th>Pemohon</th>
                        <th>Tujuan</th>
                        <th>Ruangan & Jadwal</th>
                        <th class="text-center" style="width: 320px;">Aksi Keputusan</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($pendingBookings as $booking)
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
                                @if($booking->status === \App\Constants\BookingStatus::PENDING_KAPRODI)
                                    <span class="badge bg-secondary">Menunggu Kaprodi</span>
                                @elseif($booking->status === \App\Constants\BookingStatus::PENDING_KALAB)
                                    <span class="badge bg-primary">Menunggu Kalab</span>
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
                            </td>
                            <td>
                                @foreach($booking->details as $dt)
                                    <div class="small mb-1 p-1 rounded bg-light border">
                                        <span class="badge bg-dark">{{ $dt->room->code ?? '-' }}</span>
                                        {{ $dt->start_datetime->format('d/m/Y') }}:
                                        {{ $dt->start_datetime->format('H:i') }} - {{ $dt->end_datetime->format('H:i') }}
                                        <div>
                                            Status:
                                            @if($dt->status === \App\Constants\BookingDetailStatus::APPROVED)
                                                <span class="badge bg-success">Disetujui</span>
                                            @elseif($dt->status === \App\Constants\BookingDetailStatus::REJECTED)
                                                <span class="badge bg-danger">Ditolak</span>
                                            @elseif($dt->status === \App\Constants\BookingDetailStatus::CANCELLED)
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
                                {{-- Jika menunggu Kaprodi dan user punya role Kaprodi --}}
                                @if($booking->status === \App\Constants\BookingStatus::PENDING_KAPRODI && $isKaprodi)
                                    <div class="d-flex flex-column gap-2 border p-2 rounded bg-light">
                                        <div class="small fw-bold text-primary">Keputusan Kaprodi (Level Booking)</div>
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
                                @elseif($booking->status === \App\Constants\BookingStatus::PENDING_KALAB && $isKalab)
                                    {{-- Keputusan Kalab per Ruangan --}}
                                    <div class="d-flex flex-column gap-2">
                                        <div class="small fw-bold text-success">Keputusan Kalab (Per Ruangan):</div>
                                        @foreach($booking->details as $dt)
                                            @if($dt->status === \App\Constants\BookingDetailStatus::PENDING)
                                                <div class="border p-2 rounded bg-white small mb-1">
                                                    <div class="fw-bold mb-1">{{ $dt->room->code }} ({{ $dt->start_datetime->format('H:i') }}-{{ $dt->end_datetime->format('H:i') }})</div>
                                                    <div class="d-flex gap-1">
                                                        <form method="post" action="{{ route('approvals.decideKalabDetail', $dt) }}" class="flex-grow-1">
                                                            @csrf
                                                            <input type="hidden" name="status" value="approved">
                                                            <button type="submit" class="btn btn-sm btn-success w-100 py-0" style="font-size: 0.75rem;">Setujui</button>
                                                        </form>
                                                        <button type="button" class="btn btn-sm btn-outline-danger py-0" style="font-size: 0.75rem;" data-bs-toggle="collapse" data-bs-target="#rejectDetail-{{ $dt->id }}">
                                                            Tolak...
                                                        </button>
                                                    </div>
                                                    <div class="collapse mt-2" id="rejectDetail-{{ $dt->id }}">
                                                        <form method="post" action="{{ route('approvals.decideKalabDetail', $dt) }}">
                                                            @csrf
                                                            <input type="hidden" name="status" value="rejected">
                                                            <textarea class="form-control form-control-sm mb-1" name="notes" rows="1" placeholder="Alasan tolak..." required style="font-size: 0.75rem;"></textarea>
                                                            <button type="submit" class="btn btn-danger btn-sm w-100 py-0" style="font-size: 0.75rem;">Kirim Tolak</button>
                                                        </form>
                                                    </div>
                                                </div>
                                            @else
                                                <div class="small text-muted mb-1">{{ $dt->room->code }}: Sudah diputuskan</div>
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
                        <th>Status Booking</th>
                        <th>Catatan</th>
                        <th>Waktu Update</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($recentDecisions as $dec)
                        <tr>
                            <td>#{{ $dec->id }}</td>
                            <td>{{ $dec->requester_name }}</td>
                            <td>{{ Str::limit($dec->purpose, 30) }}</td>
                            <td>
                                @if($dec->status === \App\Constants\BookingStatus::APPROVED)
                                    <span class="badge text-bg-success">Disetujui</span>
                                @elseif($dec->status === \App\Constants\BookingStatus::REJECTED)
                                    <span class="badge text-bg-danger">Ditolak</span>
                                @else
                                    <span class="badge text-bg-secondary">{{ $dec->status_label }}</span>
                                @endif
                            </td>
                            <td class="small">{{ $dec->notes ?? '-' }}</td>
                            <td class="small">{{ $dec->updated_at ? $dec->updated_at->format('d/m/Y H:i') : '-' }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center py-3 text-muted">Belum ada riwayat persetujuan.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
