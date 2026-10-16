@extends('layouts.app')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
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

<div class="card shadow-sm border-0">
    <div class="card-header bg-white py-3">
        <h2 class="h5 fw-bold mb-0">Daftar {{ $config['label'] }} (Total: {{ $records->total() }})</h2>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th style="width: 160px;">{{ $config['pk_label'] }}</th>
                        <th>Nama</th>
                        @if($type === 'rooms')
                            <th>Kapasitas</th>
                        @elseif($type === 'courses')
                            <th>Program Studi</th>
                        @endif
                        <th>Status</th>
                        <th class="text-end" style="width: 200px;">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($records as $record)
                        <tr class="{{ !$record->is_active ? 'table-light text-muted' : '' }}">
                            <td class="fw-bold font-monospace text-primary">{{ $record->id }}</td>
                            <td class="fw-semibold">{{ $record->name }}</td>
                            @if($type === 'rooms')
                                <td>{{ $record->capacity ? $record->capacity . ' orang' : '-' }}</td>
                            @elseif($type === 'courses')
                                <td>{{ $record->studyProgram->name ?? $record->study_program_id }}</td>
                            @endif
                            <td>
                                @if($record->is_active)
                                    <span class="badge bg-success">Aktif</span>
                                @else
                                    <span class="badge bg-secondary">Nonaktif</span>
                                @endif
                            </td>
                            <td class="text-end">
                                <div class="btn-group btn-group-sm">
                                    <a class="btn btn-outline-secondary" href="{{ route('master.edit', [$type, $record->id]) }}">Ubah</a>
                                    <form method="post" action="{{ route('master.toggle', [$type, $record->id]) }}" class="d-inline">
                                        @csrf
                                        <button type="submit" class="btn {{ $record->is_active ? 'btn-outline-warning' : 'btn-outline-success' }}">
                                            {{ $record->is_active ? 'Nonaktifkan' : 'Aktifkan' }}
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="text-center py-4 text-muted">Belum ada data {{ $config['label'] }}.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    @if($records->hasPages())
        <div class="card-footer bg-white">
            {{ $records->links() }}
        </div>
    @endif
</div>
@endsection
