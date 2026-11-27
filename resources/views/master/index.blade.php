@extends('layouts.app')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
    <div>
        <h1 class="h3 fw-bold mb-1">Pengelolaan Data Master: {{ $config['label'] }}</h1>
        <p class="text-secondary mb-0">Kelola master data pendukung sistem. Sesuai ketentuan, data tidak dihapus melainkan dinonaktifkan.</p>
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
        <div class="row g-2 align-items-center justify-content-between">
            <div class="col-md-5">
                <h2 class="h5 fw-bold mb-0">Daftar {{ $config['label'] }} (Total: {{ $records->count() }})</h2>
            </div>
            @if($type === 'courses')
                <div class="col-md-7">
                    <form method="get" action="{{ route('master.index', $type) }}" class="d-flex gap-2 justify-content-md-end align-items-center">
                        <label class="form-label small fw-semibold text-secondary mb-0 text-nowrap">Filter Prodi:</label>
                        <select name="study_program_id" class="form-select form-select-sm" style="max-width: 240px;" onchange="this.form.submit()">
                            <option value="">Semua Program Studi</option>
                            @foreach($studyPrograms as $sp)
                                <option value="{{ $sp->id }}" {{ request('study_program_id') === (string) $sp->id ? 'selected' : '' }}>
                                    {{ $sp->name }}
                                </option>
                            @endforeach
                        </select>
                        @if(request('study_program_id'))
                            <a href="{{ route('master.index', $type) }}" class="btn btn-sm btn-outline-secondary">Reset</a>
                        @endif
                    </form>
                </div>
            @endif
        </div>
    </div>
    <div class="card-body p-3">
        <div class="table-responsive">
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
                        <th class="text-start" style="width: 220px;">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($records as $record)
                        <tr class="{{ !$record->is_active ? 'table-light text-muted' : '' }}">
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
                                    <a class="btn btn-sm btn-outline-primary" href="{{ route('master.edit', [$type, $record->id]) }}">
                                        Ubah
                                    </a>
                                    <form method="post" action="{{ route('master.toggle', [$type, $record->id]) }}" class="d-inline m-0">
                                        @csrf
                                        <button type="submit" class="btn btn-sm {{ $record->is_active ? 'btn-outline-danger' : 'btn-outline-success' }}" onclick="return confirm('Apakah Anda yakin ingin {{ $record->is_active ? 'menonaktifkan' : 'mengaktifkan' }} {{ $record->name }}?')">
                                            {{ $record->is_active ? 'Nonaktifkan' : 'Aktifkan' }}
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
</div>
@endsection

@push('scripts')
<script>
$(document).ready(function() {
    if ($.fn.DataTable.isDataTable('#masterTable')) {
        $('#masterTable').DataTable().destroy();
    }
    $('#masterTable').DataTable({
        order: [[0, 'asc']],
        columnDefs: [
            { orderable: false, targets: [-1] }
        ]
    });
});
</script>
@endpush
