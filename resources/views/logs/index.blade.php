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
        <div class="row g-2 align-items-end">
            <div class="col-md-3">
                <label class="form-label small fw-semibold text-secondary mb-1">Aksi / Tindakan</label>
                <select id="logActionFilter" class="form-select form-select-sm">
                    <option value="">Semua Aksi</option>
                    @foreach($actionList as $act)
                        <option value="{{ $act }}">
                            {{ ucfirst(str_replace('_', ' ', $act)) }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label small fw-semibold text-secondary mb-1">Dari Tanggal</label>
                <input type="date" id="logDateFromFilter" class="form-control form-control-sm">
            </div>
            <div class="col-md-3">
                <label class="form-label small fw-semibold text-secondary mb-1">Sampai Tanggal</label>
                <input type="date" id="logDateToFilter" class="form-control form-control-sm">
            </div>
            <div class="col-md-3 d-flex align-items-end">
                <button type="button" id="btnResetLogFilter" class="btn btn-sm btn-outline-secondary d-none">Reset</button>
            </div>
        </div>
    </div>
    <div class="card-body p-3">
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
                    @php
                        $logDateIso = \Carbon\Carbon::parse($log->created_at)->format('Y-m-d');
                        $logSortTs = \Carbon\Carbon::parse($log->created_at)->format('Y-m-d H:i:s');
                    @endphp
                    <tr data-action="{{ $log->action }}" data-date="{{ $logDateIso }}">
                        <td class="small text-secondary text-start" data-order="{{ $logSortTs }}">{{ \Carbon\Carbon::parse($log->created_at)->format('d/m/Y H:i:s') }}</td>
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
                                <span class="badge text-bg-secondary">Ubah</span>
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
@endsection

@push('scripts')
<script>
$(document).ready(function() {
    if ($.fn.DataTable.isDataTable('#logsTable')) {
        $('#logsTable').DataTable().destroy();
    }

    $.fn.dataTable.ext.search.push(function(settings, data, dataIndex) {
        if (settings.nTable.id !== 'logsTable') {
            return true;
        }
        const rowNode = settings.aoData[dataIndex]?.nTr;
        if (!rowNode) {
            return true;
        }

        const actionVal = $('#logActionFilter').val();
        const dateFrom = $('#logDateFromFilter').val();
        const dateTo = $('#logDateToFilter').val();

        if (actionVal !== '') {
            const rowAction = rowNode.getAttribute('data-action') || '';
            if (rowAction !== actionVal) return false;
        }

        const rowDate = rowNode.getAttribute('data-date') || '';
        if (dateFrom && rowDate < dateFrom) return false;
        if (dateTo && rowDate > dateTo) return false;

        return true;
    });

    const table = $('#logsTable').DataTable({
        order: [[0, 'desc']],
        columnDefs: [
            { orderable: false, targets: [2, 3] }
        ]
    });

    function syncLogResetBtn() {
        const hasFilter = $('#logActionFilter').val() !== ''
            || $('#logDateFromFilter').val() !== ''
            || $('#logDateToFilter').val() !== '';
        $('#btnResetLogFilter').toggleClass('d-none', !hasFilter);
    }

    $('#logActionFilter, #logDateFromFilter, #logDateToFilter').on('change', function() {
        syncLogResetBtn();
        table.draw();
    });

    $('#btnResetLogFilter').on('click', function() {
        $('#logActionFilter, #logDateFromFilter, #logDateToFilter').val('');
        syncLogResetBtn();
        table.draw();
    });
});
</script>
@endpush
