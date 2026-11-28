@extends('layouts.app')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h1 class="h3 fw-bold mb-1">Periode Semester & Kalender Akademik</h1>
    </div>
    <a href="{{ route('admin.periods.create') }}" class="btn btn-primary">
        + Tambah Periode
    </a>
</div>

<div class="card shadow-sm border-0 mb-4">
    <div class="card-body p-4">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <div>
                <h2 class="h6 fw-bold mb-1">Daftar Periode Semester Akademik</h2>
            </div>
        </div>

        <table class="table table-hover align-middle border text-nowrap mb-0" id="periodsTable">
            <thead class="table-light">
                <tr>
                    <th class="text-start">Nama Periode</th>
                    <th class="text-start">Rentang Kuliah (start - end)</th>
                    <th class="text-start">Rentang UTS</th>
                    <th class="text-start">Rentang UAS</th>
                    <th class="text-start">Hari Libur</th>
                    <th class="text-start">Jadwal Kuliah</th>
                    <th class="text-start">Status</th>
                    <th class="text-start">Aksi & Regenerate</th>
                </tr>
            </thead>
            <tbody>
                @foreach($periods as $p)
                    <tr class="{{ $p->is_active ? 'table-success bg-opacity-10' : '' }}">
                        <td>
                            <span class="fw-bold">{{ $p->name }}</span>
                        </td>
                        <td>
                            {{ $p->start_date->format('d/m/Y') }} - {{ $p->end_date->format('d/m/Y') }}
                            <div class="small text-secondary">{{ $p->start_date->translatedFormat('l') }}</div>
                        </td>
                        <td>
                            @if($p->uts_start && $p->uts_end)
                                <div class="d-inline-block text-center lh-sm">
                                    <div>{{ $p->uts_start->format('d/m/Y') }}</div>
                                    <div class="text-secondary">-</div>
                                    <div>{{ $p->uts_end->format('d/m/Y') }}</div>
                                </div>
                            @else
                                <span class="text-muted">-</span>
                            @endif
                        </td>
                        <td>
                            @if($p->uas_start && $p->uas_end)
                                <div class="d-inline-block text-center lh-sm">
                                    <div>{{ $p->uas_start->format('d/m/Y') }}</div>
                                    <div class="text-secondary">-</div>
                                    <div>{{ $p->uas_end->format('d/m/Y') }}</div>
                                </div>
                            @else
                                <span class="text-muted">-</span>
                            @endif
                        </td>
                        <td>
                            <span>Libur ({{ $p->holidays_count }})</span>
                        </td>
                        <td>
                            <span class="badge text-bg-light border">{{ $p->schedules_count }} Jadwal</span>
                        </td>
                        <td>
                            @if($p->is_active)
                                <span class="badge bg-success">Aktif</span>
                            @else
                                <span class="badge bg-secondary">Nonaktif</span>
                            @endif
                        </td>
                        <td>
                            <div class="d-inline-flex gap-1 align-items-center">
                                <form method="post" action="{{ route('admin.periods.regenerate', $p) }}" class="d-inline m-0" data-confirm="Regenerate sesi pertemuan untuk periode ini? Sesi manual yang sudah ada tidak akan ditimpa.">
                                    @csrf
                                    <button type="submit" class="btn btn-sm btn-outline-primary" title="Generate atau regenerate schedule_session">
                                        Regenerate Sesi
                                    </button>
                                </form>
                                <a href="{{ route('admin.periods.edit', $p) }}" class="btn btn-sm btn-outline-secondary" title="Ubah" aria-label="Ubah">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" fill="currentColor" viewBox="0 0 16 16" aria-hidden="true">
                                        <path d="M12.146.146a.5.5 0 0 1 .708 0l3 3a.5.5 0 0 1 0 .708l-10 10a.5.5 0 0 1-.168.11l-5 2a.5.5 0 0 1-.65-.65l2-5a.5.5 0 0 1 .11-.168zM11.207 2.5 13.5 4.793 14.793 3.5 12.5 1.207zm1.586 3L10.5 3.207 4 9.707V10h.5a.5.5 0 0 1 .5.5v.5h.5a.5.5 0 0 1 .5.5v.5h.293zm-9.761 5.175-.106.106-1.528 3.821 3.821-1.528.106-.106A.5.5 0 0 1 5 12.5V12h-.5a.5.5 0 0 1-.5-.5V11h-.5a.5.5 0 0 1-.468-.325"/>
                                    </svg>
                                </a>
                                @if($p->is_active)
                                    <form method="post" action="{{ route('admin.periods.destroy', $p) }}" class="d-inline m-0" data-confirm="Nonaktifkan periode semester ini?">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-outline-danger" title="Nonaktifkan" aria-label="Nonaktifkan">
                                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 16 16" aria-hidden="true">
                                                <path d="M2.146 2.854a.5.5 0 1 1 .708-.708L8 7.293l5.146-5.147a.5.5 0 0 1 .708.708L8.707 8l5.147 5.146a.5.5 0 0 1-.708.708L8 8.707l-5.146 5.147a.5.5 0 0 1-.708-.708L7.293 8z"/>
                                            </svg>
                                        </button>
                                    </form>
                                @endif
                            </div>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
        @if(method_exists($periods, 'hasPages') && $periods->hasPages())
            <div class="mt-3">
                {{ $periods->links() }}
            </div>
        @endif
    </div>
</div>

@push('scripts')
<script>
$(document).ready(function() {
    if ($.fn.DataTable.isDataTable('#periodsTable')) {
        $('#periodsTable').DataTable().destroy();
    }
    $('#periodsTable').DataTable({
        ordering: false
    });
});
</script>
@endpush
@endsection
