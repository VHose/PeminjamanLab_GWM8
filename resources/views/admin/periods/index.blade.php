@extends('layouts.app')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h1 class="h3 fw-bold mb-1">Periode Semester & Kalender Akademik</h1>
        <p class="text-secondary mb-0">Kelola periode semester perkuliahan, rentang UTS/UAS, dan kalender hari libur.</p>
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
                <p class="text-secondary small mb-0">Hanya satu periode yang boleh aktif dalam satu waktu. Tanggal mulai periode wajib hari Senin.</p>
            </div>
        </div>

        <div class="table-responsive">
            <table class="table table-hover align-middle border">
                <thead class="table-light">
                    <tr>
                        <th>Nama Periode</th>
                        <th>Rentang Kuliah (start - end)</th>
                        <th>Rentang UTS</th>
                        <th>Rentang UAS</th>
                        <th>Hari Libur</th>
                        <th>Jadwal Kuliah</th>
                        <th>Status</th>
                        <th class="text-end">Aksi & Regenerate</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($periods as $p)
                        <tr class="{{ $p->is_active ? 'table-success bg-opacity-10' : '' }}">
                            <td>
                                <span class="fw-bold">{{ $p->name }}</span>
                                @if($p->is_active)
                                    <span class="badge bg-success ms-1">Aktif</span>
                                @endif
                            </td>
                            <td>
                                {{ $p->start_date->format('d/m/Y') }} - {{ $p->end_date->format('d/m/Y') }}
                                <div class="small text-secondary">{{ $p->start_date->translatedFormat('l') }}</div>
                            </td>
                            <td>
                                @if($p->uts_start && $p->uts_end)
                                    {{ $p->uts_start->format('d/m/Y') }} - {{ $p->uts_end->format('d/m/Y') }}
                                @else
                                    <span class="text-muted">-</span>
                                @endif
                            </td>
                            <td>
                                @if($p->uas_start && $p->uas_end)
                                    {{ $p->uas_start->format('d/m/Y') }} - {{ $p->uas_end->format('d/m/Y') }}
                                @else
                                    <span class="text-muted">-</span>
                                @endif
                            </td>
                            <td>
                                <a href="{{ route('admin.holidays.index', $p) }}" class="btn btn-sm btn-outline-info">
                                    Libur ({{ $p->holidays_count }})
                                </a>
                            </td>
                            <td>
                                <span class="badge text-bg-light border">{{ $p->schedules_count }} jadwal</span>
                            </td>
                            <td>
                                <form method="post" action="{{ route('admin.periods.toggle', $p) }}" class="d-inline">
                                    @csrf
                                    <button type="submit" class="btn btn-sm {{ $p->is_active ? 'btn-success' : 'btn-outline-secondary' }}" title="Klik untuk beralih status">
                                        {{ $p->is_active ? 'Aktif' : 'Nonaktif' }}
                                    </button>
                                </form>
                            </td>
                            <td class="text-end">
                                <div class="btn-group">
                                    <form method="post" action="{{ route('admin.periods.regenerate', $p) }}" class="d-inline" onsubmit="return confirm('Regenerate sesi pertemuan untuk periode ini? Sesi manual yang sudah ada tidak akan ditimpa.')">
                                        @csrf
                                        <button type="submit" class="btn btn-sm btn-outline-primary" title="Generate atau regenerate schedule_session">
                                            Regenerate Sesi
                                        </button>
                                    </form>
                                    <a href="{{ route('admin.periods.edit', $p) }}" class="btn btn-sm btn-outline-secondary">
                                        Ubah
                                    </a>
                                    @if($p->is_active)
                                        <form method="post" action="{{ route('admin.periods.destroy', $p) }}" class="d-inline" onsubmit="return confirm('Nonaktifkan periode semester ini?')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn-outline-danger">
                                                Nonaktifkan
                                            </button>
                                        </form>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="text-center text-muted py-4">Belum ada periode semester yang terdaftar.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
