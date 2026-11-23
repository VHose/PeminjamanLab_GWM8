@extends('layouts.app')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
    <div>
        <h1 class="h3 fw-bold mb-1">Log Audit & Riwayat Aktivitas</h1>
        <p class="text-secondary mb-0">Catatan riwayat perubahan data master, pengajuan, persetujuan, pembatalan, dan penolakan otomatis.</p>
    </div>
</div>

<div class="card shadow-sm border-0 mb-4">
    <div class="card-header bg-white py-3">
        <form method="get" action="{{ route('logs.index') }}" class="row g-2 align-items-center">
            <div class="col-md-3">
                <label class="form-label small fw-semibold text-secondary mb-1">Aksi / Tindakan</label>
                <select name="action" class="form-select form-select-sm" onchange="this.form.submit()">
                    <option value="">-- Semua Aksi --</option>
                    @foreach($actionList as $act)
                        <option value="{{ $act }}" {{ request('action') === $act ? 'selected' : '' }}>
                            {{ ucfirst(str_replace('_', ' ', $act)) }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label small fw-semibold text-secondary mb-1">Dari Tanggal</label>
                <input type="date" name="date_from" value="{{ request('date_from') }}" class="form-control form-control-sm" onchange="this.form.submit()">
            </div>
            <div class="col-md-3">
                <label class="form-label small fw-semibold text-secondary mb-1">Sampai Tanggal</label>
                <input type="date" name="date_to" value="{{ request('date_to') }}" class="form-control form-control-sm" onchange="this.form.submit()">
            </div>
            <div class="col-md-3 d-flex gap-1 align-items-end pt-3">
                <button type="submit" class="btn btn-sm btn-primary flex-grow-1">Filter</button>
                @if(request()->hasAny(['action', 'date_from', 'date_to', 'search']))
                    <a href="{{ route('logs.index') }}" class="btn btn-sm btn-outline-secondary">Reset</a>
                @endif
            </div>
        </form>
    </div>
    <div class="card-body p-3">
        <div class="table-responsive">
            <table class="table table-hover align-middle text-nowrap mb-0" id="logsTable">
                <thead class="table-light">
                    <tr>
                        <th class="text-start" style="width: 170px;">Waktu</th>
                        <th class="text-start" style="width: 220px;">Pengguna</th>
                        <th class="text-start" style="width: 150px;">Aksi</th>
                        <th class="text-start">Keterangan</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($logs as $log)
                        <tr>
                            <td class="small text-secondary text-start">{{ \Carbon\Carbon::parse($log->created_at)->format('d/m/Y H:i:s') }}</td>
                            <td class="text-start">
                                @if($log->user)
                                    <span class="fw-semibold">{{ $log->user->name }}</span>
                                    <span class="badge text-bg-light border ms-1">{{ str_replace('_', ' ', $log->user->roles->first()?->name ?? 'User') }}</span>
                                @else
                                    <span class="badge text-bg-secondary">Sistem Otomatis</span>
                                @endif
                            </td>
                            <td class="text-start">
                                @if($log->action === 'create')
                                    <span class="badge text-bg-primary">Tambah</span>
                                @elseif($log->action === 'update' || $log->action === 'update_roles')
                                    <span class="badge text-bg-info">Ubah</span>
                                @elseif($log->action === 'delete')
                                    <span class="badge text-bg-danger">Hapus</span>
                                @elseif($log->action === 'approve')
                                    <span class="badge text-bg-success">Setujui</span>
                                @elseif($log->action === 'reject')
                                    <span class="badge text-bg-danger">Tolak</span>
                                @elseif($log->action === 'cancel')
                                    <span class="badge text-bg-warning">Batal</span>
                                @elseif($log->action === 'reschedule')
                                    <span class="badge text-bg-warning">Ubah Jadwal</span>
                                @elseif($log->action === 'auto_reject')
                                    <span class="badge text-bg-dark">Auto Tolak H-2</span>
                                @else
                                    <span class="badge text-bg-secondary">{{ str_replace('_', ' ', $log->action) }}</span>
                                @endif
                            </td>
                            <td class="small text-start">{{ $log->description ?? '-' }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
$(document).ready(function() {
    if ($.fn.DataTable.isDataTable('#logsTable')) {
        $('#logsTable').DataTable().destroy();
    }
    $('#logsTable').DataTable({
        order: [[0, 'desc']]
    });
});
</script>
@endpush
