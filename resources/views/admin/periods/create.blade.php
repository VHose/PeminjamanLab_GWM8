@extends('layouts.app')

@section('content')
<div class="row justify-content-center">
    <div class="col-lg-8">
        <div class="card shadow-sm border-0">
            <div class="card-header bg-white py-3">
                <div class="d-flex justify-content-between align-items-center">
                    <h1 class="h5 fw-bold mb-0">Tambah Periode Semester Akademik</h1>
                    <a href="{{ route('admin.periods.index') }}" class="btn btn-sm btn-outline-secondary">Kembali</a>
                </div>
            </div>
            <div class="card-body p-4">
                <div class="alert alert-info small mb-4">
                    <strong>Ketentuan Periode Akademik:</strong>
                    <ul class="mb-0 ps-3">
                        <li>Tanggal mulai (start_date) <strong>wajib hari Senin</strong> pertama kuliah.</li>
                        <li>Rentang UTS dan UAS harus berada di dalam rentang periode.</li>
                        <li>Rentang UAS harus dimulai setelah rentang UTS berakhir.</li>
                        <li>Jika status "Jadikan Periode Aktif" dicentang, periode lain otomatis dinonaktifkan.</li>
                    </ul>
                </div>

                <form method="post" action="{{ route('admin.periods.store') }}">
                    @csrf
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Nama Periode <span class="text-danger">*</span></label>
                        <input type="text" name="name" class="form-control @error('name') is-invalid @enderror" value="{{ old('name') }}" placeholder="Contoh: 2026/2027 Ganjil" required maxlength="20">
                        @error('name')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Tanggal Mulai Kuliah (Wajib Senin) <span class="text-danger">*</span></label>
                            <input type="date" name="start_date" class="form-control" value="{{ old('start_date') }}" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Tanggal Akhir UAS (end_date) <span class="text-danger">*</span></label>
                            <input type="date" name="end_date" class="form-control" value="{{ old('end_date') }}" required>
                        </div>
                    </div>

                    <div class="p-3 bg-light rounded border mb-3">
                        <h2 class="h6 fw-bold mb-2">Rentang Minggu UTS (Ujian Tengah Semester)</h2>
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label small">Mulai UTS</label>
                                <input type="date" name="uts_start" class="form-control" value="{{ old('uts_start') }}">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label small">Selesai UTS</label>
                                <input type="date" name="uts_end" class="form-control" value="{{ old('uts_end') }}">
                            </div>
                        </div>
                    </div>

                    <div class="p-3 bg-light rounded border mb-4">
                        <h2 class="h6 fw-bold mb-2">Rentang Minggu UAS (Ujian Akhir Semester)</h2>
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label small">Mulai UAS</label>
                                <input type="date" name="uas_start" class="form-control" value="{{ old('uas_start') }}">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label small">Selesai UAS</label>
                                <input type="date" name="uas_end" class="form-control" value="{{ old('uas_end') }}">
                            </div>
                        </div>
                    </div>

                    <div class="form-check form-switch mb-4">
                        <input class="form-check-input" type="checkbox" name="is_active" value="1" id="isActiveCheck" @checked(old('is_active', true))>
                        <label class="form-check-label fw-semibold" for="isActiveCheck">Jadikan Periode Aktif Saat Ini</label>
                        <div class="form-text">Mengaktifkan periode ini akan otomatis menonaktifkan periode aktif yang lain.</div>
                    </div>

                    <div class="d-flex justify-content-end gap-2">
                        <a href="{{ route('admin.periods.index') }}" class="btn btn-outline-secondary">Batal</a>
                        <button type="submit" class="btn btn-primary px-4">Simpan Periode</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
