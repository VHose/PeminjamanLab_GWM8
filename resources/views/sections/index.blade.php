@extends('layouts.app')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h1 class="h3 fw-bold mb-1">Jadwal Kelas Reguler</h1>
        <p class="text-secondary mb-0">Kelola jadwal perkuliahan rutin mingguan di laboratorium GWM Lantai 8.</p>
    </div>
    <a class="btn btn-primary" href="{{ route('sections.create') }}">Tambah Jadwal Kelas</a>
</div>

<div class="card shadow-sm border-0">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Periode</th>
                        <th>Prodi</th>
                        <th>Mata Kuliah</th>
                        <th>Ruangan</th>
                        <th>Hari</th>
                        <th>Waktu</th>
                        <th>Dosen / Pengajar</th>
                        <th>Kelas</th>
                        <th class="text-end">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @php
                        $dayNames = [1 => 'Senin', 2 => 'Selasa', 3 => 'Rabu', 4 => 'Kamis', 5 => 'Jumat', 6 => 'Sabtu'];
                    @endphp
                    @forelse($sections as $section)
                        <tr>
                            <td>{{ $section->period->name ?? '-' }}</td>
                            <td>
                                <span class="badge" style="background-color: {{ $section->course->studyProgram->color_hex ?? '#ccc' }}; color: #000;">
                                    {{ $section->course->studyProgram->code ?? '-' }}
                                </span>
                            </td>
                            <td>
                                <div class="fw-bold">{{ $section->course->name ?? '-' }}</div>
                                <div class="small text-muted">{{ $section->course->code ?? '-' }}</div>
                            </td>
                            <td class="fw-bold">{{ $section->room->code ?? '-' }}</td>
                            <td>{{ $dayNames[$section->day_of_week] ?? $section->day_of_week }}</td>
                            <td>{{ substr($section->start_time, 0, 5) }} - {{ substr($section->end_time, 0, 5) }}</td>
                            <td>{{ $section->lecturer?->name ?? '-' }}</td>
                            <td>
                                <span class="badge text-bg-light border">{{ $section->class_code }}</span>
                                @if($section->schedule_type === 1)
                                    <span class="badge bg-warning text-dark">Ujian</span>
                                @else
                                    <span class="badge bg-secondary">Reguler</span>
                                @endif
                            </td>
                            <td class="text-end">
                                <div class="btn-group btn-group-sm">
                                    <a class="btn btn-outline-primary" href="{{ route('sections.edit', $section) }}">Ubah</a>
                                    <form method="post" action="{{ route('sections.destroy', $section) }}" class="d-inline" onsubmit="return confirm('Apakah Anda yakin ingin menghapus jadwal ini?')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-outline-danger">Hapus</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="text-center py-4 text-muted">Belum ada jadwal kelas yang terdaftar.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    @if($sections->hasPages())
        <div class="card-footer bg-white">
            {{ $sections->links() }}
        </div>
    @endif
</div>
@endsection
