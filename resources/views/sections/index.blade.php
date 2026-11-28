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
            <table class="table table-hover align-middle text-nowrap mb-0">
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
                        <th>Aksi</th>
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
                            <td>
                                <div class="d-inline-flex gap-1 align-items-center">
                                    <a class="btn btn-sm btn-outline-primary" href="{{ route('sections.edit', $section) }}" title="Ubah" aria-label="Ubah">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" fill="currentColor" viewBox="0 0 16 16" aria-hidden="true">
                                            <path d="M12.146.146a.5.5 0 0 1 .708 0l3 3a.5.5 0 0 1 0 .708l-10 10a.5.5 0 0 1-.168.11l-5 2a.5.5 0 0 1-.65-.65l2-5a.5.5 0 0 1 .11-.168zM11.207 2.5 13.5 4.793 14.793 3.5 12.5 1.207zm1.586 3L10.5 3.207 4 9.707V10h.5a.5.5 0 0 1 .5.5v.5h.5a.5.5 0 0 1 .5.5v.5h.293zm-9.761 5.175-.106.106-1.528 3.821 3.821-1.528.106-.106A.5.5 0 0 1 5 12.5V12h-.5a.5.5 0 0 1-.5-.5V11h-.5a.5.5 0 0 1-.468-.325"/>
                                        </svg>
                                    </a>
                                    <form method="post" action="{{ route('sections.destroy', $section) }}" class="d-inline" data-confirm="Apakah Anda yakin ingin menghapus jadwal ini?">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-outline-danger">Hapus</button>
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
