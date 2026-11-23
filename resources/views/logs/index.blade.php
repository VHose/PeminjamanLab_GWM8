@extends('layouts.app')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h1 class="h3 fw-bold mb-1">Log Audit & Riwayat Aktivitas</h1>
        <p class="text-secondary mb-0">Catatan riwayat perubahan data master, pengajuan, persetujuan, pembatalan, dan penolakan otomatis.</p>
    </div>
</div>

<div class="card shadow-sm border-0 mb-4">
    <div class="card-header bg-white py-3">
        <div class="row g-2 align-items-center justify-content-between">
            <div class="col-md-5">
                <h2 class="h5 fw-bold mb-0">Catatan Aktivitas Sistem (Total: {{ $logs->total() }})</h2>
            </div>
            <div class="col-md-5">
                <form method="get" action="{{ route('logs.index') }}" class="d-flex gap-2">
                    <input type="text" name="search" id="searchLogInput" class="form-control form-control-sm" placeholder="Cari pengguna, aksi, atau keterangan..." value="{{ $search ?? '' }}">
                    @if(!empty($search))
                        <a href="{{ route('logs.index') }}" class="btn btn-sm btn-outline-secondary">Reset</a>
                    @endif
                    <button type="submit" class="btn btn-sm btn-primary">Cari</button>
                </form>
            </div>
        </div>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0" id="logsTable">
                <thead class="table-light">
                    <tr>
                        <th style="width: 170px;">Waktu</th>
                        <th style="width: 220px;">Pengguna</th>
                        <th style="width: 150px;">Aksi</th>
                        <th>Keterangan</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($logs as $log)
                        <tr>
                            <td class="small text-secondary">{{ \Carbon\Carbon::parse($log->created_at)->format('d/m/Y H:i:s') }}</td>
                            <td>
                                @if($log->user)
                                    <span class="fw-semibold">{{ $log->user->name }}</span>
                                    <span class="badge text-bg-light border ms-1">{{ str_replace('_', ' ', $log->user->roles->first()?->name ?? 'User') }}</span>
                                @else
                                    <span class="badge text-bg-secondary">Sistem Otomatis</span>
                                @endif
                            </td>
                            <td>
                                @if($log->action === 'create')
                                    <span class="badge text-bg-primary">Tambah</span>
                                @elseif($log->action === 'update')
                                    <span class="badge text-bg-info">Ubah</span>
                                @elseif($log->action === 'delete')
                                    <span class="badge text-bg-danger">Hapus</span>
                                @elseif($log->action === 'approve')
                                    <span class="badge text-bg-success">Setujui</span>
                                @elseif($log->action === 'reject')
                                    <span class="badge text-bg-danger">Tolak</span>
                                @elseif($log->action === 'cancel')
                                    <span class="badge text-bg-warning">Batal</span>
                                @elseif($log->action === 'auto_reject')
                                    <span class="badge text-bg-dark">Auto Tolak H-2</span>
                                @else
                                    <span class="badge text-bg-secondary">{{ $log->action }}</span>
                                @endif
                            </td>
                            <td class="small">{{ $log->description ?? '-' }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="text-center py-4 text-muted">Belum ada catatan aktivitas yang sesuai.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    @if($logs->hasPages())
        <div class="card-footer bg-white">
            {{ $logs->links() }}
        </div>
    @endif
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const input = document.getElementById('searchLogInput');
    const table = document.getElementById('logsTable');
    if (input && table) {
        input.addEventListener('keyup', function() {
            const filter = input.value.toLowerCase();
            const rows = table.querySelectorAll('tbody tr');
            rows.forEach(row => {
                const text = row.textContent.toLowerCase();
                row.style.display = text.includes(filter) ? '' : 'none';
            });
        });
    }
});
</script>
@endpush
@endsection
