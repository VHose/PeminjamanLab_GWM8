@extends('layouts.app')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h1 class="h3 fw-bold mb-1">Pengelolaan Data Master</h1>
        <p class="text-secondary mb-0">Kelola master data pendukung sistem peminjaman laboratorium.</p>
    </div>
    <a class="btn btn-primary" href="{{ route('master.create', $type) }}">Tambah Data {{ $config['label'] }}</a>
</div>

<ul class="nav nav-pills mb-4 bg-white p-2 rounded shadow-sm border">
    <li class="nav-item">
        <a class="nav-link {{ $type === 'rooms' ? 'active' : '' }}" href="{{ route('master.index', 'rooms') }}">Ruangan</a>
    </li>
    <li class="nav-item">
        <a class="nav-link {{ $type === 'lecturers' ? 'active' : '' }}" href="{{ route('master.index', 'lecturers') }}">Dosen</a>
    </li>
    <li class="nav-item">
        <a class="nav-link {{ $type === 'courses' ? 'active' : '' }}" href="{{ route('master.index', 'courses') }}">Mata Kuliah</a>
    </li>
    <li class="nav-item">
        <a class="nav-link {{ $type === 'study-programs' ? 'active' : '' }}" href="{{ route('master.index', 'study-programs') }}">Program Studi</a>
    </li>
    <li class="nav-item">
        <a class="nav-link {{ $type === 'periods' ? 'active' : '' }}" href="{{ route('master.index', 'periods') }}">Periode Semester</a>
    </li>
</ul>

<div class="card shadow-sm border-0">
    <div class="card-header bg-white py-3">
        <h2 class="h5 fw-bold mb-0">Daftar {{ $config['label'] }}</h2>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        @foreach($config['fields'] as $key => $label)
                            <th>{{ $label }}</th>
                        @endforeach
                        <th class="text-end">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($records as $record)
                        <tr>
                            @foreach(array_keys($config['fields']) as $field)
                                <td>
                                    @if($field === 'color_hex')
                                        <span class="d-inline-flex align-items-center gap-2">
                                            <span class="d-inline-block border rounded" style="width: 20px; height: 20px; background-color: {{ $record->$field }};"></span>
                                            <code>{{ $record->$field }}</code>
                                        </span>
                                    @elseif($field === 'active')
                                        @if($record->$field)
                                            <span class="badge text-bg-success">Aktif</span>
                                        @else
                                            <span class="badge text-bg-secondary">Nonaktif</span>
                                        @endif
                                    @elseif($field === 'semester')
                                        <span class="badge text-bg-light border">{{ $record->$field === 'odd' ? 'Ganjil' : 'Genap' }}</span>
                                    @elseif($field === 'study_program_id')
                                        <span class="fw-semibold">{{ $record->studyProgram->name ?? $record->$field }}</span>
                                    @else
                                        {{ $record->$field ?? '-' }}
                                    @endif
                                </td>
                            @endforeach
                            <td class="text-end">
                                <div class="btn-group btn-group-sm">
                                    <a class="btn btn-outline-primary" href="{{ route('master.edit', [$type, $record->getKey()]) }}">Ubah</a>
                                    <form method="post" action="{{ route('master.destroy', [$type, $record->getKey()]) }}" class="d-inline" onsubmit="return confirm('Apakah Anda yakin ingin menghapus data ini?')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-outline-danger">Hapus</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="{{ count($config['fields']) + 1 }}" class="text-center py-4 text-muted">Belum ada data {{ $config['label'] }}.</td>
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
