@extends('layouts.app')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h1 class="h3 fw-bold mb-1">Hari Libur Periode: {{ $period->name }}</h1>
        <p class="text-secondary mb-0">
            Rentang Periode: <span class="fw-semibold text-dark">{{ $period->start_date->format('d/m/Y') }} s/d {{ $period->end_date->format('d/m/Y') }}</span>.
            Hari libur yang menutup Senin s/d Jumat penuh akan membuat minggu tersebut dilewati dari penomoran kuliah.
        </p>
    </div>
    <a href="{{ route('admin.periods.index') }}" class="btn btn-outline-secondary">
        &larr; Kembali ke Periode Semester
    </a>
</div>

<div class="row g-4">
    <div class="col-lg-5">
        <div class="card shadow-sm border-0">
            <div class="card-header bg-white py-3">
                <h2 class="h6 fw-bold mb-0">Tambah Hari Libur Baru</h2>
            </div>
            <div class="card-body p-4">
                <form method="post" action="{{ route('admin.holidays.store', $period) }}">
                    @csrf
                    <input type="hidden" name="period_id" value="{{ $period->id }}">

                    <div class="mb-3">
                        <label class="form-label fw-semibold">Tanggal Mulai Libur <span class="text-danger">*</span></label>
                        <input type="date" name="start_date" class="form-control" value="{{ old('start_date') }}" min="{{ $period->start_date->toDateString() }}" max="{{ $period->end_date->toDateString() }}" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold">Tanggal Selesai Libur (Opsional)</label>
                        <input type="date" name="end_date" class="form-control" value="{{ old('end_date') }}" min="{{ $period->start_date->toDateString() }}" max="{{ $period->end_date->toDateString() }}">
                        <div class="form-text">Biarkan kosong jika hanya libur satu hari (hari tunggal: start = end).</div>
                    </div>

                    <div class="mb-4">
                        <label class="form-label fw-semibold">Keterangan / Catatan Hari Libur</label>
                        <input type="text" name="note" class="form-control @error('note') is-invalid @enderror" value="{{ old('note') }}" placeholder="Contoh: NATARU (Natal dan Tahun Baru), Isra' Mi'raj" maxlength="60">
                        @error('note')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <button type="submit" class="btn btn-primary w-100">
                        Simpan Hari Libur
                    </button>
                </form>
            </div>
        </div>
    </div>

    <div class="col-lg-7">
        <div class="card shadow-sm border-0">
            <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
                <h2 class="h6 fw-bold mb-0">Daftar Hari Libur Terdaftar ({{ $holidays->count() }})</h2>
                <form method="post" action="{{ route('admin.periods.regenerate', $period) }}" class="d-inline" onsubmit="return confirm('Regenerate sesi pertemuan setelah mengubah hari libur? Sesi manual tidak akan ditimpa.')">
                    @csrf
                    <button type="submit" class="btn btn-sm btn-outline-primary">
                        Sinkronkan ke Sesi Kuliah
                    </button>
                </form>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Rentang Tanggal Libur</th>
                                <th>Durasi</th>
                                <th>Keterangan</th>
                                <th class="text-end">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($holidays as $h)
                                @php
                                    $s = \Carbon\Carbon::parse($h->start_date);
                                    $e = \Carbon\Carbon::parse($h->end_date);
                                    $daysCount = $e->diffInDays($s) + 1;
                                    $isSingle = $h->start_date->toDateString() === $h->end_date->toDateString();
                                @endphp
                                <tr>
                                    <td>
                                        <div class="fw-semibold">
                                            @if($isSingle)
                                                {{ $s->translatedFormat('d F Y') }}
                                            @else
                                                {{ $s->translatedFormat('d M Y') }} - {{ $e->translatedFormat('d M Y') }}
                                            @endif
                                        </div>
                                        <div class="small text-secondary">{{ $s->translatedFormat('l') }} @if(!$isSingle) s/d {{ $e->translatedFormat('l') }} @endif</div>
                                    </td>
                                    <td>
                                        <span class="badge text-bg-secondary">{{ $daysCount }} hari</span>
                                    </td>
                                    <td>
                                        <span class="text-dark">{{ $h->note ?? 'Libur' }}</span>
                                    </td>
                                    <td class="text-end">
                                        <form method="post" action="{{ route('admin.holidays.destroy', [$period, $h->start_date->toDateString()]) }}" class="d-inline" onsubmit="return confirm('Hapus hari libur ini?')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn-outline-danger">
                                                Hapus
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="text-center text-muted py-4">Belum ada hari libur yang ditambahkan pada periode ini.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
