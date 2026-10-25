@extends('layouts.app')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h1 class="h3 fw-bold mb-1">Pengelolaan Peran Staf & Asisten Laboratorium</h1>
        <p class="text-secondary mb-0">Atur penugasan peran per periode semester, unggah daftar staf awal semester, atau salin dari periode sebelumnya.</p>
    </div>
</div>

<!-- PILIH PERIODE -->
<div class="card shadow-sm border-0 mb-4">
    <div class="card-body p-3">
        <form method="get" action="{{ route('admin.period-roles.index') }}" class="row g-3 align-items-center">
            <div class="col-auto">
                <label class="form-label fw-bold mb-0">Pilih Periode Semester:</label>
            </div>
            <div class="col-md-4">
                <select name="period_id" class="form-select" onchange="this.form.submit()">
                    @foreach($allPeriods as $p)
                        <option value="{{ $p->id }}" @selected($selectedPeriod && $selectedPeriod->id == $p->id)>
                            {{ $p->name }} {{ $p->is_active ? '(Aktif)' : '' }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="col-auto ms-auto d-flex gap-2">
                <button type="button" class="btn btn-outline-primary" data-bs-toggle="modal" data-bs-target="#copyRolesModal">
                    Salin Peran dari Periode Sebelumnya
                </button>
                <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#uploadStaffModal">
                    + Upload Daftar Staf (CSV / Teks)
                </button>
            </div>
        </form>
    </div>
</div>

<!-- PERINGATAN EMAIL SEMENTARA (@belum-diisi.invalid) -->
@if($invalidEmailUsers->isNotEmpty())
    <div class="alert alert-warning border-warning shadow-sm mb-4" role="alert">
        <div class="d-flex align-items-start gap-3">
            <div class="fs-4 text-warning">⚠️</div>
            <div class="flex-grow-1">
                <h2 class="h6 fw-bold mb-1 text-dark">
                    Peringatan: Terdapat {{ $invalidEmailUsers->count() }} Pengguna dengan Email Belum Diisi!
                </h2>
                <p class="small text-secondary mb-2">
                    Pengguna berikut masih menggunakan alamat email sementara berakhiran <code>@belum-diisi.invalid</code> dari hasil impor jadwal. Segera perbarui dengan alamat email aktif agar pengguna dapat menerima pemberitahuan dan mereset kata sandi.
                </p>
                <div class="d-flex flex-wrap gap-2">
                    @foreach($invalidEmailUsers as $iu)
                        <span class="badge text-bg-light border text-dark">
                            <strong>{{ $iu->id }}</strong>: {{ $iu->name }} (<code>{{ $iu->email }}</code>)
                        </span>
                    @endforeach
                </div>
            </div>
        </div>
    </div>
@endif

<!-- TABEL DAFTAR PERAN PADA PERIODE TERPILIH -->
<div class="card shadow-sm border-0 mb-4">
    <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
        <h2 class="h6 fw-bold mb-0">
            Daftar Peran Staf & Dosen pada Periode: <span class="text-primary">{{ $selectedPeriod?->name ?? 'Belum dipilih' }}</span>
            <span class="badge text-bg-secondary ms-2">{{ $userRoles->count() }} Penugasan</span>
        </h2>
        <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-toggle="collapse" data-bs-target="#quickAssignCollapse">
            + Tambah Penugasan Manual
        </button>
    </div>

    <!-- FORM QUICK ASSIGN -->
    <div class="collapse border-bottom p-3 bg-light" id="quickAssignCollapse">
        <form method="post" action="{{ route('admin.period-roles.store') }}" class="row g-3">
            @csrf
            <input type="hidden" name="period_id" value="{{ $selectedPeriod?->id }}">
            <div class="col-md-2">
                <label class="form-label small fw-semibold">NIK/NRP</label>
                <input type="text" name="user_id" class="form-control form-control-sm" placeholder="Contoh: 720319" required maxlength="10">
            </div>
            <div class="col-md-3">
                <label class="form-label small fw-semibold">Nama Lengkap (Jika Baru)</label>
                <input type="text" name="name" class="form-control form-control-sm" placeholder="Nama dosen / staf" maxlength="80">
            </div>
            <div class="col-md-3">
                <label class="form-label small fw-semibold">Email Asli</label>
                <input type="email" name="email" class="form-control form-control-sm" placeholder="nama@maranatha.edu" maxlength="70">
            </div>
            <div class="col-md-2">
                <label class="form-label small fw-semibold">Peran (Role)</label>
                <select name="role_id" class="form-select form-control-sm" required>
                    @foreach($allRoles as $role)
                        <option value="{{ $role->id }}">{{ str_replace('_', ' ', $role->name) }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2 d-flex align-items-end">
                <button type="submit" class="btn btn-sm btn-success w-100">Simpan Peran</button>
            </div>
        </form>
    </div>

    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>NIK / NRP</th>
                        <th>Nama Pengguna</th>
                        <th>Email</th>
                        <th>Program Studi</th>
                        <th>Peran di Periode Ini</th>
                        <th class="text-end">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($userRoles as $ur)
                        @php
                            $isInvalidEmail = str_ends_with($ur->user->email, '@belum-diisi.invalid');
                        @endphp
                        <tr>
                            <td class="fw-bold">{{ $ur->user_id }}</td>
                            <td>{{ $ur->user->name }}</td>
                            <td>
                                @if($isInvalidEmail)
                                    <span class="text-danger fw-semibold" title="Email belum diisi">{{ $ur->user->email }}</span>
                                    <span class="badge text-bg-warning ms-1">Belum Diisi</span>
                                @else
                                    <span class="text-dark">{{ $ur->user->email }}</span>
                                @endif
                            </td>
                            <td>
                                @if($ur->user->studyProgram)
                                    <span class="badge text-bg-light border">{{ $ur->user->studyProgram->name }}</span>
                                @else
                                    <span class="text-muted">-</span>
                                @endif
                            </td>
                            <td>
                                <span class="badge bg-primary">
                                    {{ str_replace('_', ' ', $ur->role->name) }}
                                </span>
                            </td>
                            <td class="text-end">
                                <form method="post" action="{{ route('admin.period-roles.destroy', [$ur->period_id, $ur->user_id, $ur->role_id]) }}" class="d-inline" onsubmit="return confirm('Hapus peran {{ $ur->role->name }} untuk pengguna ini di periode {{ $selectedPeriod->name }}?')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-outline-danger">
                                        Cabut Peran
                                    </button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center text-muted py-4">Belum ada peran staf yang ditugaskan pada periode semester ini.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- MODAL SALIN PERAN -->
<div class="modal fade" id="copyRolesModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title fw-bold">Salin Peran dari Periode Sebelumnya</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="post" action="{{ route('admin.period-roles.copy') }}">
                @csrf
                <div class="modal-body">
                    <p class="text-secondary small mb-3">
                        Gunakan fitur ini di awal semester untuk menyalin seluruh penugasan staf laboratorium, asisten, dan dosen dari periode sebelumnya ke periode saat ini secara cepat.
                    </p>

                    <div class="mb-3">
                        <label class="form-label fw-semibold">Periode Asal (Sumber Data)</label>
                        <select name="from_period_id" class="form-select" required>
                            <option value="">-- Pilih Periode Asal --</option>
                            @foreach($allPeriods as $p)
                                @if(!$selectedPeriod || $p->id !== $selectedPeriod->id)
                                    <option value="{{ $p->id }}">{{ $p->name }}</option>
                                @endif
                            @endforeach
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold">Periode Tujuan (Target Salinan)</label>
                        <select name="to_period_id" class="form-select" required>
                            @if($selectedPeriod)
                                <option value="{{ $selectedPeriod->id }}" selected>{{ $selectedPeriod->name }}</option>
                            @else
                                <option value="">-- Pilih Periode Tujuan --</option>
                                @foreach($allPeriods as $p)
                                    <option value="{{ $p->id }}">{{ $p->name }}</option>
                                @endforeach
                            @endif
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary">Salin Peran Sekarang</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- MODAL UPLOAD DAFTAR STAF -->
<div class="modal fade" id="uploadStaffModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title fw-bold">Unggah Daftar Staf Awal Semester</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="post" action="{{ route('admin.period-roles.upload') }}" enctype="multipart/form-data">
                @csrf
                <input type="hidden" name="period_id" value="{{ $selectedPeriod?->id }}">
                <div class="modal-body">
                    <p class="text-secondary small mb-3">
                        Kepala Lab dapat mengunggah berkas CSV atau menempelkan daftar staf/asisten laboratorium. Akun pengguna yang belum ada akan dibuat secara otomatis dengan kata sandi acak dan kewajiban ganti kata sandi.
                    </p>

                    <div class="alert alert-info small mb-3">
                        <strong>Format Baris (CSV / Teks):</strong><br>
                        <code>NIK/NRP, Nama Lengkap, Email, Role (Staf_Lab / Dosen / Kepala_Lab / Kepala_Prodi), [KodeProdi]</code><br>
                        <em>Contoh:</em><br>
                        <code>STF001, Budi Santoso, budi@maranatha.edu, Staf_Lab</code><br>
                        <code>720319, Rossevine Artha, rossevine@maranatha.edu, Kepala_Prodi, 72</code>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold">Pilih Berkas CSV / TXT</label>
                        <input type="file" name="staff_file" class="form-control" accept=".csv,.txt">
                    </div>

                    <div class="text-center my-2 text-muted fw-bold">--- ATAU TEMPELKAN TEKS DI BAWAH ---</div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold">Tempel Teks Daftar Staf</label>
                        <textarea name="staff_text" class="form-control font-monospace" rows="6" placeholder="720001, Dosen Satu, dosen1@maranatha.edu, Dosen, 72&#10;STF002, Asisten Lab, asisten@maranatha.edu, Staf_Lab"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary">Proses & Simpan Peran Staf</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
