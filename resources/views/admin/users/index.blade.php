@extends('layouts.app')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
    <div>
        <h1 class="h3 fw-bold mb-1">Kelola Pengguna & Peran</h1>
        <p class="text-secondary mb-0">Kelola akun seluruh pengguna, mahasiswa/visitor, dosen, serta penugasan peran per periode semester.</p>
    </div>
    <div class="d-flex gap-2 flex-wrap">
        <button type="button" class="btn btn-outline-secondary" data-bs-toggle="modal" data-bs-target="#copyRolesModal">
            Salin Peran per Periode
        </button>
        <button type="button" class="btn btn-outline-primary" data-bs-toggle="modal" data-bs-target="#uploadStaffModal">
            Upload Staf Lab
        </button>
        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addUserModal">
            + Tambah Pengguna
        </button>
    </div>
</div>

<div class="card shadow-sm border-0 mb-4">
    <div class="card-header bg-white py-3">
        <form method="get" action="{{ route('admin.users.index') }}" class="row g-2 align-items-center">
            <div class="col-md-3">
                <label class="form-label small fw-semibold text-secondary mb-1">Periode Semester</label>
                <select name="period_id" class="form-select form-select-sm" onchange="this.form.submit()">
                    @foreach($allPeriods as $p)
                        <option value="{{ $p->id }}" {{ (int) $selectedPeriodId === (int) $p->id ? 'selected' : '' }}>
                            {{ $p->name }} {{ $p->is_active ? '(Aktif)' : '' }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label small fw-semibold text-secondary mb-1">Kategori Peran</label>
                <select name="role_id" class="form-select form-select-sm" onchange="this.form.submit()">
                    <option value="">-- Semua Peran --</option>
                    @foreach($allRoles as $r)
                        <option value="{{ $r->id }}" {{ (string) request('role_id') === (string) $r->id ? 'selected' : '' }}>
                            {{ str_replace('_', ' ', $r->name) }}
                        </option>
                    @endforeach
                    <option value="visitor" {{ request('role_id') === 'visitor' ? 'selected' : '' }}>Visitor (Tanpa Peran Khusus)</option>
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label small fw-semibold text-secondary mb-1">Program Studi</label>
                <select name="study_program_id" class="form-select form-select-sm" onchange="this.form.submit()">
                    <option value="">-- Semua Prodi --</option>
                    @foreach($studyPrograms as $sp)
                        <option value="{{ $sp->id }}" {{ request('study_program_id') === (string) $sp->id ? 'selected' : '' }}>
                            {{ $sp->name }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label small fw-semibold text-secondary mb-1">Status Akun</label>
                <select name="is_active" class="form-select form-select-sm" onchange="this.form.submit()">
                    <option value="">-- Semua Status --</option>
                    <option value="1" {{ request('is_active') === '1' ? 'selected' : '' }}>Aktif</option>
                    <option value="0" {{ request('is_active') === '0' ? 'selected' : '' }}>Nonaktif</option>
                </select>
            </div>
            <div class="col-md-2 d-flex gap-1 align-items-end pt-3">
                <button type="submit" class="btn btn-sm btn-primary flex-grow-1">Filter</button>
                @if(request()->hasAny(['role_id', 'study_program_id', 'is_active', 'search']))
                    <a href="{{ route('admin.users.index', ['period_id' => $selectedPeriodId]) }}" class="btn btn-sm btn-outline-secondary">Reset</a>
                @endif
            </div>
        </form>
    </div>
    <div class="card-body p-3">
        <div class="table-responsive">
            <table class="table table-hover align-middle text-nowrap mb-0" id="usersTable">
                <thead class="table-light">
                    <tr>
                        <th class="text-start">NIK / ID</th>
                        <th class="text-start">Nama Pengguna</th>
                        <th class="text-start">Email</th>
                        <th class="text-start">Program Studi</th>
                        <th class="text-start">Peran ({{ $selectedPeriod->name ?? 'Periode Ini' }})</th>
                        <th class="text-start">Status</th>
                        <th class="text-start" style="width: 220px;">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($users as $user)
                        @php
                            $isInvalidEmail = str_ends_with($user->email, '@belum-diisi.invalid');
                            $userPeriodRoles = $user->userRoles->filter(function($ur) use ($selectedPeriodId) {
                                return (int) $ur->period_id === (int) $selectedPeriodId;
                            });
                        @endphp
                        <tr>
                            <td class="fw-bold font-monospace text-primary text-start">{{ $user->id }}</td>
                            <td class="fw-semibold text-start">
                                {{ $user->name }}
                                @if($user->phone)
                                    <div class="small text-muted">{{ $user->phone }}</div>
                                @endif
                            </td>
                            <td class="text-start">
                                @if($isInvalidEmail)
                                    <span class="text-danger fw-semibold">{{ $user->email }}</span>
                                    <span class="badge text-bg-danger ms-1">Invalid</span>
                                @else
                                    {{ $user->email }}
                                @endif
                            </td>
                            <td class="text-start">{{ $user->studyProgram->name ?? '-' }}</td>
                            <td class="text-start">
                                @if($userPeriodRoles->isNotEmpty())
                                    @foreach($userPeriodRoles as $uRole)
                                        <span class="badge bg-primary mb-1">{{ str_replace('_', ' ', $uRole->role->name ?? '-') }}</span>
                                    @endforeach
                                @else
                                    @php
                                        $otherRoles = $user->userRoles->map(fn($ur) => $ur->role?->name)->filter()->unique();
                                    @endphp
                                    @if($otherRoles->contains('Admin'))
                                        <span class="badge bg-dark mb-1">Admin</span>
                                    @else
                                        <span class="badge bg-light text-secondary border">Visitor</span>
                                    @endif
                                @endif
                            </td>
                            <td class="text-start">
                                @if($user->is_active)
                                    <span class="badge bg-success">Aktif</span>
                                @else
                                    <span class="badge bg-danger">Nonaktif</span>
                                @endif
                            </td>
                            <td class="text-start">
                                <div class="d-inline-flex gap-1 align-items-center">
                                    <button type="button" class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#roleModal-{{ Str::slug($user->id) }}">
                                        Kelola Peran
                                    </button>
                                    <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-toggle="modal" data-bs-target="#resetPasswordModal-{{ Str::slug($user->id) }}">
                                        Reset Sandi
                                    </button>
                                    @if($user->id !== auth()->id())
                                        <form method="post" action="{{ route('admin.users.toggle', $user) }}" class="d-inline" onsubmit="return confirm('Ubah status aktif akun ini?')">
                                            @csrf
                                            <button type="submit" class="btn btn-sm {{ $user->is_active ? 'btn-outline-warning' : 'btn-outline-success' }}">
                                                {{ $user->is_active ? 'Nonaktifkan' : 'Aktifkan' }}
                                            </button>
                                        </form>
                                    @endif
                                </div>

                                <!-- Modal Kelola Peran Pengguna -->
                                <div class="modal fade text-start" id="roleModal-{{ Str::slug($user->id) }}" tabindex="-1" aria-hidden="true">
                                    <div class="modal-dialog">
                                        <div class="modal-content">
                                            <div class="modal-header">
                                                <h5 class="modal-title fw-bold">Kelola Peran: {{ $user->name }}</h5>
                                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                            </div>
                                            <form method="post" action="{{ route('admin.period-roles.update-user', [$selectedPeriodId, $user->id]) }}">
                                                @csrf
                                                @method('PUT')
                                                <div class="modal-body">
                                                    <p class="small text-secondary mb-3">
                                                        Centang peran yang dimiliki pengguna ini untuk periode <strong>{{ $selectedPeriod->name ?? 'Terpilih' }}</strong>. Jika tidak ada yang dicentang, pengguna berstatus <em>Visitor</em>.
                                                    </p>
                                                    <div class="row g-2">
                                                        @php
                                                            $currentRoleIds = $userPeriodRoles->pluck('role_id')->toArray();
                                                        @endphp
                                                        @foreach($allRoles as $role)
                                                            @if($role->name !== 'Visitor')
                                                                <div class="col-6">
                                                                    <div class="form-check p-2 border rounded bg-light">
                                                                        <input class="form-check-input ms-1" type="checkbox" name="role_ids[]" value="{{ $role->id }}" id="role_{{ Str::slug($user->id) }}_{{ $role->id }}" {{ in_array($role->id, $currentRoleIds) ? 'checked' : '' }}>
                                                                        <label class="form-check-label fw-semibold ms-2" for="role_{{ Str::slug($user->id) }}_{{ $role->id }}">
                                                                            {{ str_replace('_', ' ', $role->name) }}
                                                                        </label>
                                                                    </div>
                                                                </div>
                                                            @endif
                                                        @endforeach
                                                    </div>
                                                </div>
                                                <div class="modal-footer">
                                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button>
                                                    <button type="submit" class="btn btn-primary">Simpan Peran</button>
                                                </div>
                                            </form>
                                        </div>
                                    </div>
                                </div>

                                <!-- Modal Reset Password -->
                                <div class="modal fade text-start" id="resetPasswordModal-{{ Str::slug($user->id) }}" tabindex="-1" aria-hidden="true">
                                    <div class="modal-dialog">
                                        <div class="modal-content">
                                            <div class="modal-header">
                                                <h5 class="modal-title fw-bold">Reset Kata Sandi</h5>
                                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                            </div>
                                            <form method="post" action="{{ route('admin.users.resetPassword', $user) }}">
                                                @csrf
                                                <div class="modal-body">
                                                    <p class="small text-secondary mb-3">
                                                        Mereset kata sandi untuk <strong>{{ $user->name }}</strong> (ID: {{ $user->id }}).
                                                    </p>
                                                    <div class="mb-3">
                                                        <label class="form-label fw-semibold">Kata Sandi Baru</label>
                                                        <input type="password" name="password" class="form-control" required minlength="8" placeholder="Minimal 8 karakter">
                                                    </div>
                                                    <div class="mb-3">
                                                        <label class="form-label fw-semibold">Konfirmasi Kata Sandi Baru</label>
                                                        <input type="password" name="password_confirmation" class="form-control" required minlength="8" placeholder="Ketik ulang kata sandi baru">
                                                    </div>
                                                </div>
                                                <div class="modal-footer">
                                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                                                    <button type="submit" class="btn btn-danger">Simpan Sandi Baru</button>
                                                </div>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Modal Tambah Pengguna Baru -->
<div class="modal fade" id="addUserModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title fw-bold">Tambah Pengguna Baru</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="post" action="{{ route('admin.users.store') }}">
                @csrf
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label fw-semibold">NIK / ID Pengguna <span class="text-danger">*</span></label>
                        <input type="text" name="id" class="form-control font-monospace" required placeholder="Contoh: 720099 / ADM002 / V000005" maxlength="10">
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Nama Lengkap <span class="text-danger">*</span></label>
                        <input type="text" name="name" class="form-control" required placeholder="Nama lengkap pengguna">
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Email <span class="text-danger">*</span></label>
                        <input type="email" name="email" class="form-control" required placeholder="email@maranatha.ac.id">
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Nomor Telepon / WA</label>
                        <input type="text" name="phone" class="form-control" placeholder="Contoh: 08123456789">
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Program Studi</label>
                        <select name="study_program_id" class="form-select">
                            <option value="">-- Tanpa Prodi (Umum / Eksternal) --</option>
                            @foreach($studyPrograms as $sp)
                                <option value="{{ $sp->id }}">{{ $sp->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Peran Awal (Periode Aktif)</label>
                        <select name="role_id" class="form-select">
                            @foreach($allRoles as $role)
                                <option value="{{ $role->id }}" {{ $role->name === 'Visitor' ? 'selected' : '' }}>
                                    {{ str_replace('_', ' ', $role->name) }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Kata Sandi <span class="text-danger">*</span></label>
                        <input type="password" name="password" class="form-control" required minlength="8" placeholder="Minimal 8 karakter">
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Konfirmasi Kata Sandi <span class="text-danger">*</span></label>
                        <input type="password" name="password_confirmation" class="form-control" required minlength="8" placeholder="Ulangi kata sandi">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary">Simpan Pengguna</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Salin Peran -->
<div class="modal fade" id="copyRolesModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title fw-bold">Salin Penugasan Peran Antar Periode</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="post" action="{{ route('admin.period-roles.copy') }}">
                @csrf
                <div class="modal-body">
                    <p class="small text-secondary mb-3">
                        Fitur ini akan menyalin seluruh peran staf/dosen/kaprodi/kalab dari periode asal ke periode tujuan tanpa menimpa peran yang sudah ada.
                    </p>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Dari Periode (Sumber):</label>
                        <select name="from_period_id" class="form-select" required>
                            @foreach($allPeriods as $p)
                                <option value="{{ $p->id }}" {{ (int) $selectedPeriodId !== (int) $p->id ? 'selected' : '' }}>
                                    {{ $p->name }} {{ $p->is_active ? '(Aktif)' : '' }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Ke Periode (Tujuan):</label>
                        <select name="to_period_id" class="form-select" required>
                            @foreach($allPeriods as $p)
                                <option value="{{ $p->id }}" {{ (int) $selectedPeriodId === (int) $p->id ? 'selected' : '' }}>
                                    {{ $p->name }} {{ $p->is_active ? '(Aktif)' : '' }}
                                </option>
                            @endforeach
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

<!-- Modal Upload Staf Lab -->
<div class="modal fade" id="uploadStaffModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title fw-bold">Upload Daftar Staf Lab / Dosen (CSV/Teks)</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="post" action="{{ route('admin.period-roles.upload') }}" enctype="multipart/form-data">
                @csrf
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Pilih Periode Tujuan:</label>
                        <select name="period_id" class="form-select" required>
                            @foreach($allPeriods as $p)
                                <option value="{{ $p->id }}" {{ (int) $selectedPeriodId === (int) $p->id ? 'selected' : '' }}>
                                    {{ $p->name }} {{ $p->is_active ? '(Aktif)' : '' }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Upload Berkas File (CSV atau XLSX)</label>
                        <input type="file" name="staff_file" class="form-control" accept=".csv,.txt,.xlsx">
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Atau Tempel Teks Daftar Staf (Format: NIK, Nama, Email, Peran, KodeProdi):</label>
                        <textarea name="staff_text" rows="5" class="form-control font-monospace small" placeholder="STF001, Budi Santoso, budi@maranatha.ac.id, Staf_Lab, 72&#10;720099, Dosen Tamu, dosentamu@maranatha.ac.id, Dosen, 72"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary">Unggah & Terapkan Peran</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
$(document).ready(function() {
    if ($.fn.DataTable.isDataTable('#usersTable')) {
        $('#usersTable').DataTable().destroy();
    }
    $('#usersTable').DataTable({
        order: [[1, 'asc']],
        columnDefs: [
            { orderable: false, targets: [6] }
        ]
    });
});
</script>
@endpush
