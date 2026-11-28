@extends('layouts.app')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
    <div>
        <h1 class="h3 fw-bold mb-1">Pengelolaan Data Master: {{ $config['label'] }}</h1>
    </div>
    <a class="btn btn-primary" href="{{ route('master.create', $type) }}">+ Tambah {{ $config['label'] }}</a>
</div>

<ul class="nav nav-pills mb-4 bg-white p-2 rounded shadow-sm border">
    <li class="nav-item">
        <a class="nav-link {{ $type === 'rooms' ? 'active' : '' }}" href="{{ route('master.index', 'rooms') }}">Ruangan Lab</a>
    </li>
    <li class="nav-item">
        <a class="nav-link {{ $type === 'courses' ? 'active' : '' }}" href="{{ route('master.index', 'courses') }}">Mata Kuliah</a>
    </li>
    <li class="nav-item">
        <a class="nav-link {{ $type === 'study-programs' ? 'active' : '' }}" href="{{ route('master.index', 'study-programs') }}">Program Studi</a>
    </li>
</ul>

<div class="card shadow-sm border-0 mb-4">
    <div class="card-header bg-white py-3">
        <div class="{{ $type === 'courses' ? 'mb-3' : '' }}">
            <h2 class="h5 fw-bold mb-0">Daftar {{ $config['label'] }} (Total: {{ $records->count() }})</h2>
        </div>
        @if($type === 'courses')
            <div class="row g-2 align-items-end">
                <div class="col-md-4">
                    <label class="form-label small fw-semibold text-secondary mb-1">Program Studi</label>
                    <select id="courseProdiFilter" class="form-select form-select-sm">
                        <option value="">Semua Program Studi</option>
                        @foreach($studyPrograms as $sp)
                            <option value="{{ $sp->id }}">
                                {{ $sp->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label small fw-semibold text-secondary mb-1">Status</label>
                    <select id="courseStatusFilter" class="form-select form-select-sm">
                        <option value="">Semua Status</option>
                        <option value="1">Aktif</option>
                        <option value="0">Nonaktif</option>
                    </select>
                </div>
                <div class="col-md-4 d-flex align-items-end">
                    <button type="button" id="btnResetCourseFilter" class="btn btn-sm btn-outline-secondary d-none">Reset</button>
                </div>
            </div>
        @endif
    </div>
    <div class="card-body p-3">
        <table class="table table-hover align-middle text-nowrap mb-0" id="masterTable">
            <thead class="table-light">
                <tr>
                    <th class="text-start" style="width: 160px;">{{ $config['pk_label'] }}</th>
                    <th class="text-start">Nama</th>
                    @if($type === 'rooms')
                        <th class="text-start">Kapasitas</th>
                    @elseif($type === 'courses')
                        <th class="text-start">Program Studi</th>
                    @endif
                    <th class="text-start">Status</th>
                    <th class="text-start" style="width: 120px;">Aksi</th>
                </tr>
            </thead>
            <tbody>
                @foreach($records as $record)
                    <tr class="{{ !$record->is_active ? 'table-light text-muted' : '' }}" data-prodi="{{ $record->study_program_id ?? '' }}" data-status="{{ $record->is_active ? '1' : '0' }}">
                        <td class="fw-bold font-monospace text-primary text-start">{{ $record->id }}</td>
                        <td class="fw-semibold text-start">{{ $record->name }}</td>
                        @if($type === 'rooms')
                            <td class="text-start">{{ $record->capacity ? $record->capacity . ' orang' : '-' }}</td>
                        @elseif($type === 'courses')
                            <td class="text-start">{{ $record->studyProgram->name ?? $record->study_program_id }}</td>
                        @endif
                        <td class="text-start">
                            @if($record->is_active)
                                <span class="badge bg-success">Aktif</span>
                            @else
                                <span class="badge bg-secondary">Nonaktif</span>
                            @endif
                        </td>
                        <td class="text-start">
                            <div class="d-inline-flex gap-1 align-items-center">
                                <a class="btn btn-sm btn-outline-primary" href="{{ route('master.edit', [$type, $record->id]) }}" title="Ubah" aria-label="Ubah">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" fill="currentColor" viewBox="0 0 16 16" aria-hidden="true">
                                        <path d="M12.146.146a.5.5 0 0 1 .708 0l3 3a.5.5 0 0 1 0 .708l-10 10a.5.5 0 0 1-.168.11l-5 2a.5.5 0 0 1-.65-.65l2-5a.5.5 0 0 1 .11-.168zM11.207 2.5 13.5 4.793 14.793 3.5 12.5 1.207zm1.586 3L10.5 3.207 4 9.707V10h.5a.5.5 0 0 1 .5.5v.5h.5a.5.5 0 0 1 .5.5v.5h.293zm-9.761 5.175-.106.106-1.528 3.821 3.821-1.528.106-.106A.5.5 0 0 1 5 12.5V12h-.5a.5.5 0 0 1-.5-.5V11h-.5a.5.5 0 0 1-.468-.325"/>
                                    </svg>
                                </a>
                                <form method="post" action="{{ route('master.toggle', [$type, $record->id]) }}" class="d-inline m-0">
                                    @csrf
                                    <button type="submit" class="btn btn-sm {{ $record->is_active ? 'btn-outline-danger' : 'btn-outline-success' }}" title="{{ $record->is_active ? 'Nonaktifkan' : 'Aktifkan' }}" aria-label="{{ $record->is_active ? 'Nonaktifkan' : 'Aktifkan' }}" onclick="return confirm('Apakah Anda yakin ingin {{ $record->is_active ? 'menonaktifkan' : 'mengaktifkan' }} {{ $record->name }}?')">
                                        @if($record->is_active)
                                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 16 16" aria-hidden="true">
                                                <path d="M2.146 2.854a.5.5 0 1 1 .708-.708L8 7.293l5.146-5.147a.5.5 0 0 1 .708.708L8.707 8l5.147 5.146a.5.5 0 0 1-.708.708L8 8.707l-5.146 5.147a.5.5 0 0 1-.708-.708L7.293 8z"/>
                                            </svg>
                                        @else
                                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 16 16" aria-hidden="true">
                                                <path d="M12.736 3.97a.733.733 0 0 1 1.047 0c.286.289.29.756.01 1.05L7.88 12.01a.733.733 0 0 1-1.065.02L3.217 8.384a.757.757 0 0 1 0-1.06.733.733 0 0 1 1.047 0l3.052 3.093 5.4-6.425z"/>
                                            </svg>
                                        @endif
                                    </button>
                                </form>
                            </div>
                        </td>
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
    if ($.fn.DataTable.isDataTable('#masterTable')) {
        $('#masterTable').DataTable().destroy();
    }

    @if($type === 'courses')
    $.fn.dataTable.ext.search.push(function(settings, data, dataIndex) {
        if (settings.nTable.id !== 'masterTable') {
            return true;
        }
        const rowNode = settings.aoData[dataIndex]?.nTr;
        if (!rowNode) {
            return true;
        }
        const prodiVal = $('#courseProdiFilter').val();
        const statusVal = $('#courseStatusFilter').val();

        if (prodiVal !== '') {
            const rowProdi = rowNode.getAttribute('data-prodi') || '';
            if (rowProdi !== prodiVal) return false;
        }
        if (statusVal !== '') {
            const rowStatus = rowNode.getAttribute('data-status') || '';
            if (rowStatus !== statusVal) return false;
        }
        return true;
    });
    @endif

    const table = $('#masterTable').DataTable({
        order: [[0, 'asc']],
        columnDefs: [
            @if($type === 'courses')
                { orderable: false, targets: [2, 3, 4] }
            @else
                { orderable: false, targets: [-2, -1] }
            @endif
        ]
    });

    @if($type === 'courses')
    function syncCourseResetBtn() {
        const hasFilter = $('#courseProdiFilter').val() !== '' || $('#courseStatusFilter').val() !== '';
        $('#btnResetCourseFilter').toggleClass('d-none', !hasFilter);
    }

    $('#courseProdiFilter, #courseStatusFilter').on('change', function() {
        syncCourseResetBtn();
        table.draw();
    });

    $('#btnResetCourseFilter').on('click', function() {
        $('#courseProdiFilter, #courseStatusFilter').val('');
        syncCourseResetBtn();
        table.draw();
    });
    @endif
});
</script>
@endpush
