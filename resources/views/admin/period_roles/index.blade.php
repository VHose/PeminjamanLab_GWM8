@extends('layouts.app')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h1 class="h3 fw-bold mb-1">Pengelolaan Peran Staf & Asisten Laboratorium</h1>
    </div>
</div>

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

@if($invalidEmailUsers->isNotEmpty())
    <div class="alert alert-warning border-warning shadow-sm mb-4" role="alert">
        <div class="d-flex align-items-start gap-3">
            <div class="fs-4 text-warning">⚠️</div>
            <div class="grow">
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

<div class="card shadow-sm border-0 mb-4">
    <div class="card-header bg-white py-3">
        <div class="row g-2 align-items-center justify-content-between">
            <div class="col-md-5">
                <h2 class="h6 fw-bold mb-0">
                    Daftar Staf & Dosen: <span class="text-primary">{{ $selectedPeriod?->name ?? 'Belum dipilih' }}</span>
                    <span class="badge text-bg-secondary ms-2">{{ $groupedUsers->count() }} Pengguna</span>
                </h2>
            </div>
            <div class="col-md-7 d-flex justify-content-end gap-2">
                <form method="get" action="{{ route('admin.period-roles.index') }}" class="d-flex gap-2 flex-grow-1" style="max-width: 380px;">
                    <input type="hidden" name="period_id" value="{{ $selectedPeriod?->id }}">
                    <input type="text" name="search" id="searchRoleInput" class="form-control form-control-sm" placeholder="Cari nama, NIK, peran..." value="{{ $search ?? '' }}">
                    @if(!empty($search))
                        <a href="{{ route('admin.period-roles.index', ['period_id' => $selectedPeriod?->id]) }}" class="btn btn-sm btn-outline-secondary">Reset</a>
                    @endif
                    <button type="submit" class="btn btn-sm btn-primary">Cari</button>
                </form>
                <button type="button" class="btn btn-sm btn-outline-primary" data-bs-toggle="collapse" data-bs-target="#quickAssignCollapse">
                    + Tambah Manual
                </button>
            </div>
        </div>
    </div>

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
                <button type="submit" class="btn btn-sm btn-primary w-100">Simpan Peran</button>
            </div>
        </form>
    </div>

    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle text-nowrap mb-0" id="rolesTable">
                <thead class="table-light">
                    <tr>
                        <th style="width: 120px;">NIK / NRP</th>
                        <th>Nama Pengguna</th>
                        <th>Email</th>
                        <th>Program Studi</th>
                        <th>Peran di Periode Ini</th>
                        <th style="width: 140px;">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($groupedUsers as $item)
                        @php
                            $isInvalidEmail = str_ends_with($item->user->email, '@belum-diisi.invalid');
                        @endphp
                        <tr>
                            <td class="fw-bold font-monospace text-primary">{{ $item->user->id }}</td>
                            <td class="fw-semibold">{{ $item->user->name }}</td>
                            <td>
                                @if($isInvalidEmail)
                                    <span class="text-danger fw-semibold" title="Email belum diisi">{{ $item->user->email }}</span>
                                    <span class="badge text-bg-warning ms-1">Belum Diisi</span>
                                @else
                                    <span class="text-dark">{{ $item->user->email }}</span>
                                @endif
                            </td>
                            <td>
                                @if($item->user->studyProgram)
                                    <span class="badge text-bg-light border">{{ $item->user->studyProgram->name }}</span>
                                @else
                                    <span class="text-muted">-</span>
                                @endif
                            </td>
                            <td>
                                <div class="d-flex flex-wrap gap-1">
                                    @foreach($item->roles as $role)
                                        @php
                                            $badgeClass = match($role->name) {
                                                'Admin' => 'bg-danger',
                                                'Kepala_Lab' => 'bg-success',
                                                'Kepala_Prodi' => 'bg-info text-dark',
                                                'Dosen' => 'bg-primary',
                                                'Staf_Lab' => 'bg-warning text-dark',
                                                default => 'bg-secondary',
                                            };
                                        @endphp
                                        <span class="badge {{ $badgeClass }}">
                                            {{ str_replace('_', ' ', $role->name) }}
                                        </span>
                                    @endforeach
                                </div>
                            </td>
                            <td>
                                <button type="button" class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#editUserRolesModal-{{ $item->user->id }}">
                                    Ubah Peran
                                </button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center text-muted py-4">Belum ada peran staf yang sesuai kriteria pencarian pada periode ini.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    @if($groupedUsers->hasPages())
        <div class="card-footer bg-white border-top py-3">
            {{ $groupedUsers->links() }}
        </div>
    @endif
</div>

{{-- Modal Ubah Peran untuk Setiap Pengguna --}}
@foreach($groupedUsers as $item)
    <div class="modal fade" id="editUserRolesModal-{{ $item->user->id }}" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <form method="post" action="{{ route('admin.period-roles.update-user', [$selectedPeriod->id, $item->user->id]) }}">
                @csrf
                @method('PUT')
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title fw-bold">Ubah Peran Pengguna</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="bg-light p-3 rounded mb-3 border">
                            <div class="fw-bold fs-6">{{ $item->user->name }}</div>
                            <div class="small text-secondary">NIK / NRP: <strong>{{ $item->user->id }}</strong> &bull; {{ $item->user->email }}</div>
                            <div class="small text-secondary">Periode: <strong>{{ $selectedPeriod?->name }}</strong></div>
                        </div>

                        <label class="form-label fw-bold mb-2">Pilih Peran yang Diberikan (Dapat Lebih dari 1 Peran):</label>
                        <div class="list-group mb-3">
                            @foreach($allRoles as $r)
                                @php
                                    $hasRole = $item->roles->contains('id', $r->id);
                                @endphp
                                <label class="list-group-item d-flex gap-3 align-items-center cursor-pointer">
                                    <input class="form-check-input flex-shrink-0 m-0" type="checkbox" name="role_ids[]" value="{{ $r->id }}" @checked($hasRole)>
                                    <div class="flex-grow-1">
                                        <div class="fw-semibold">{{ str_replace('_', ' ', $r->name) }}</div>
                                        <small class="text-muted d-block">
                                            @if($r->name === 'Dosen') Hak pengajaran dan peminjaman atas nama dosen.
                                            @elseif($r->name === 'Kepala_Prodi') Persetujuan peminjaman tahap 1 lingkup program studi.
                                            @elseif($r->name === 'Kepala_Lab') Persetujuan peminjaman tahap 2 per ruangan lab.
                                            @elseif($r->name === 'Staf_Lab') Input peminjaman dosen dan operasional lab.
                                            @elseif($r->name === 'Admin') Akses penuh seluruh pengaturan sistem.
                                            @else Hak akses peminjaman umum.
                                            @endif
                                        </small>
                                    </div>
                                </label>
                            @endforeach
                        </div>
                        <div class="small text-muted">
                            <i class="bi bi-info-circle me-1"></i>Hapus centang semua peran jika ingin mencabut pengguna sepenuhnya dari periode ini.
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-outline-secondary btn-sm" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-primary btn-sm">Simpan Perubahan Peran</button>
                    </div>
                </div>
            </form>
        </div>
    </div>
@endforeach

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
                    <p class="small text-secondary">
                        Fitur ini menyalin seluruh penugasan staf laboratorium dan dosen dari semester lalu ke semester saat ini. Penugasan yang sudah ada tidak akan diduplikasi.
                    </p>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Salin DARI Periode (Sumber):</label>
                        <select name="from_period_id" class="form-select" required>
                            @foreach($allPeriods as $p)
                                @if(!$selectedPeriod || $p->id !== $selectedPeriod->id)
                                    <option value="{{ $p->id }}">{{ $p->name }}</option>
                                @endif
                            @endforeach
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Salin KE Periode (Tujuan):</label>
                        <input type="hidden" name="to_period_id" value="{{ $selectedPeriod?->id }}">
                        <input type="text" class="form-control" value="{{ $selectedPeriod?->name }}" disabled>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary btn-sm" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary btn-sm">Mulai Salin Peran</button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="modal fade" id="uploadStaffModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title fw-bold">Upload Daftar Staf & Asisten Laboratorium</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="post" action="{{ route('admin.period-roles.upload') }}" enctype="multipart/form-data">
                @csrf
                <input type="hidden" name="period_id" value="{{ $selectedPeriod?->id }}">
                <div class="modal-body">
                    <p class="small text-secondary mb-3">
                        Unggah daftar staf untuk periode <strong>{{ $selectedPeriod?->name }}</strong>. Jika NIK belum terdaftar sebagai akun pengguna, akun akan otomatis dibuatkan dan pengguna diwajibkan mengganti kata sandi awal saat login.
                    </p>

                    <ul class="nav nav-tabs mb-3" role="tablist">
                        <li class="nav-item">
                            <button class="nav-link active" data-bs-toggle="tab" data-bs-target="#tabCsv" type="button">Upload File (CSV / TXT)</button>
                        </li>
                        <li class="nav-item">
                            <button class="nav-link" data-bs-toggle="tab" data-bs-target="#tabPaste" type="button">Tempel Teks (Paste)</button>
                        </li>
                    </ul>

                    <div class="tab-content">
                        <div class="tab-pane fade show active" id="tabCsv">
                            <div class="mb-3">
                                <label class="form-label fw-semibold">Pilih File Berkas</label>
                                <input type="file" name="staff_file" class="form-control" accept=".csv,.txt">
                                <div class="form-text">Format: <code>NIK, Nama Lengkap, Email, Peran (Opsional), Kode Prodi (Opsional)</code></div>
                            </div>
                        </div>
                        <div class="tab-pane fade" id="tabPaste">
                            <div class="mb-3">
                                <label class="form-label fw-semibold">Tempel Baris Data Staf</label>
                                <textarea name="staff_text" class="form-control font-monospace" rows="6" placeholder="72001, Budi Santoso, budi@maranatha.edu, Staf_Lab, 72&#10;72002, Siti Aminah, siti@maranatha.edu, Staf_Lab, 72"></textarea>
                                <div class="form-text">Pisahkan tiap kolom dengan tanda koma (<code>,</code>) dan tiap data staf per baris baru.</div>
                            </div>
                        </div>
                    </div>

                    <div class="alert alert-info py-2 px-3 small mb-0">
                        <strong>Peran yang valid:</strong> Staf_Lab, Dosen, Kepala_Prodi, Kepala_Lab, Admin, Visitor.
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary btn-sm" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary btn-sm">Unggah dan Proses Data</button>
                </div>
            </form>
        </div>
    </div>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const input = document.getElementById('searchRoleInput');
    const table = document.getElementById('rolesTable');
    if (input && table) {
        input.addEventListener('keyup', function() {
            const filter = input.value.toLowerCase();
            const rows = table.querySelectorAll('tbody tr');
            rows.forEach(row => {
                const text = row.textContent.toLowerCase();
                row.style.display = text.includes(filter) ? '' : 'none';
            });
        });
    }
});
</script>
@endpush
@endsection
