@extends('layouts.app')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
    <div>
        <h1 class="h3 fw-bold mb-1">Kelola Pengguna & Peran</h1>
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
        <div class="row g-2 align-items-end">
            <div class="col-md-3">
                <label class="form-label small fw-semibold text-secondary mb-1">Periode Semester</label>
                <select id="userPeriodFilter" class="form-select form-select-sm" data-default="{{ $selectedPeriodId }}">
                    @foreach($allPeriods as $p)
                        <option value="{{ $p->id }}" data-period-name="{{ $p->name }}" {{ (int) $selectedPeriodId === (int) $p->id ? 'selected' : '' }}>
                            {{ $p->name }} {{ $p->is_active ? '(Aktif)' : '' }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label small fw-semibold text-secondary mb-1">Kategori Peran</label>
                <select id="userRoleFilter" class="form-select form-select-sm">
                    <option value="">Semua Peran</option>
                    @foreach($allRoles as $r)
                        <option value="{{ $r->id }}">
                            {{ str_replace('_', ' ', $r->name) }}
                        </option>
                    @endforeach
                    <option value="visitor">Visitor (Tanpa Peran Khusus)</option>
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label small fw-semibold text-secondary mb-1">Program Studi</label>
                <select id="userProdiFilter" class="form-select form-select-sm">
                    <option value="">Semua Prodi</option>
                    @foreach($studyPrograms as $sp)
                        <option value="{{ $sp->id }}">
                            {{ $sp->name }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label small fw-semibold text-secondary mb-1">Status Akun</label>
                <select id="userStatusFilter" class="form-select form-select-sm">
                    <option value="">Semua Status</option>
                    <option value="1">Aktif</option>
                    <option value="0">Nonaktif</option>
                </select>
            </div>
            <div class="col-md-2 d-flex align-items-end">
                <button type="button" id="btnResetUserFilter" class="btn btn-sm btn-outline-secondary d-none">Reset</button>
            </div>
        </div>
    </div>
    <div class="card-body p-3">
        <table class="table table-hover align-middle text-nowrap mb-0" id="usersTable">
            <thead class="table-light">
                <tr>
                    <th class="text-start">NIK / ID</th>
                    <th class="text-start">Nama Pengguna</th>
                    <th class="text-start">Email</th>
                    <th class="text-start">Program Studi</th>
                    <th class="text-start" id="periodRoleHeader">Peran</th>
                    <th class="text-start">Status</th>
                    <th class="text-start" style="width: 200px;">Aksi</th>
                </tr>
            </thead>
            <tbody>
                @foreach($users as $user)
                    @php
                        $isInvalidEmail = str_ends_with($user->email, '@belum-diisi.invalid');
                        $userPeriodRoles = $user->userRoles->filter(function($ur) use ($selectedPeriodId) {
                            return (int) $ur->period_id === (int) $selectedPeriodId || $ur->period_id === null;
                        });
                        $otherRoles = $user->userRoles->map(fn($ur) => $ur->role?->name)->filter()->unique();
                        $isGlobalAdmin = $otherRoles->contains('Admin');

                        $rolesByPeriod = [];
                        foreach ($allPeriods as $p) {
                            $pRoles = $user->userRoles->filter(function($ur) use ($p) {
                                return (int) $ur->period_id === (int) $p->id || $ur->period_id === null;
                            })->map(fn($ur) => [
                                'id' => (string) $ur->role_id,
                                'name' => str_replace('_', ' ', $ur->role->name ?? '-'),
                                'raw' => $ur->role->name ?? '',
                            ])->values()->all();
                            $rolesByPeriod[(string) $p->id] = $pRoles;
                        }
                    @endphp
                    <tr
                        data-user-slug="{{ Str::slug($user->id) }}"
                        data-prodi="{{ $user->study_program_id ?? '' }}"
                        data-status="{{ $user->is_active ? '1' : '0' }}"
                        data-global-admin="{{ $isGlobalAdmin ? '1' : '0' }}"
                        data-roles-by-period='@json($rolesByPeriod)'
                    >
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
                        <td class="text-start user-period-roles-cell">
                            @if($userPeriodRoles->isNotEmpty())
                                @foreach($userPeriodRoles as $uRole)
                                    <span class="badge bg-primary mb-1">{{ str_replace('_', ' ', $uRole->role->name ?? '-') }}</span>
                                @endforeach
                            @elseif($isGlobalAdmin)
                                <span class="badge bg-dark mb-1">Admin</span>
                            @else
                                <span class="badge bg-light text-secondary border">Visitor</span>
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
                                    <form method="post" action="{{ route('admin.users.toggle', $user) }}" class="d-inline m-0" onsubmit="return confirm('Ubah status aktif akun ini?')">
                                        @csrf
                                        <button type="submit" class="btn btn-sm {{ $user->is_active ? 'btn-outline-danger' : 'btn-outline-success' }}" title="{{ $user->is_active ? 'Nonaktifkan' : 'Aktifkan' }}" aria-label="{{ $user->is_active ? 'Nonaktifkan' : 'Aktifkan' }}">
                                            @if($user->is_active)
                                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 16 16" aria-hidden="true">
                                                    <path d="M2.146 2.854a.5.5 0 1 1 .708-.708L8 7.293l5.146-5.147a.5.5 0 0 1 .708.708L8.707 8l5.147 5.146a.5.5 0 0 1-.708.708L8 8.707l-5.146 5.147a.5.5 0 0 1-.708-.708L7.293 8z"/>
                                                </svg>
                                            @else
                                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 16 16" aria-hidden="true">
                                                    <path d="M12.736 3.97a.733.733 0 0 1 1.047 0c.286.289.29.756.01 1.05L7.88 12.01a.733.733 0 0 1-1.065.02L3.217 8.384a.757.757 0 0 1 0-1.06.733.733 0 0 1 1.047 0l3.052 3.093 5.4-6.425z"/>
                                                </svg>
                                            @endif
                                        </button>
                                    </form>
                                @endif
                            </div>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>

@foreach($users as $user)
    @php
        $userPeriodRoles = $user->userRoles->filter(function($ur) use ($selectedPeriodId) {
            return (int) $ur->period_id === (int) $selectedPeriodId || $ur->period_id === null;
        });
        $currentRoleIds = $userPeriodRoles->pluck('role_id')->toArray();
    @endphp
    <!-- Modal Kelola Peran Pengguna -->
    <div class="modal fade text-start" id="roleModal-{{ Str::slug($user->id) }}" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title fw-bold">Kelola Peran: {{ $user->name }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="post" action="{{ route('admin.period-roles.update-user', [$selectedPeriodId, $user->id]) }}" data-action-template="{{ route('admin.period-roles.update-user', ['__PERIOD__', $user->id]) }}">
                    @csrf
                    @method('PUT')
                    <div class="modal-body">
                        <div class="row g-2">
                            @foreach($allRoles as $role)
                                @if($role->name !== 'Visitor')
                                    <div class="col-6">
                                        <div class="form-check p-2 border rounded bg-light">
                                            <input class="form-check-input ms-1 user-role-checkbox" type="checkbox" name="role_ids[]" value="{{ $role->id }}" id="role_{{ Str::slug($user->id) }}_{{ $role->id }}" {{ in_array($role->id, $currentRoleIds) ? 'checked' : '' }}>
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
@endforeach

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

    $.fn.dataTable.ext.search.push(function(settings, data, dataIndex) {
        if (settings.nTable.id !== 'usersTable') {
            return true;
        }

        const rowNode = settings.aoData[dataIndex]?.nTr;
        if (!rowNode) {
            return true;
        }

        const periodId = String($('#userPeriodFilter').val() || '');
        const roleVal = $('#userRoleFilter').val();
        const prodiVal = $('#userProdiFilter').val();
        const statusVal = $('#userStatusFilter').val();

        if (roleVal) {
            let rolesByPeriod = {};
            try {
                rolesByPeriod = JSON.parse(rowNode.getAttribute('data-roles-by-period') || '{}');
            } catch (e) {}
            const pRoles = rolesByPeriod[periodId] || [];

            if (roleVal === 'visitor') {
                const hasSpecialRole = pRoles.some(function(r) { return r.raw !== 'Visitor'; });
                if (hasSpecialRole) return false;
            } else {
                const hasRole = pRoles.some(function(r) { return String(r.id) === String(roleVal); });
                if (!hasRole) return false;
            }
        }

        if (prodiVal !== '') {
            const rowProdi = rowNode.getAttribute('data-prodi') || '';
            if (rowProdi !== prodiVal) return false;
        }

        if (statusVal !== '') {
            const rowStatus = rowNode.getAttribute('data-status') || '';
            if (rowStatus !== statusVal) return false;
        }

        return true;
    });

    const table = $('#usersTable').DataTable({
        order: [[1, 'asc']],
        columnDefs: [
            { orderable: false, targets: [3, 4, 5, 6] },
            { searchable: false, targets: [6] }
        ]
    });

    function escapeHtml(str) {
        return String(str)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#39;');
    }

    function updatePeriodDisplay() {
        const periodId = String($('#userPeriodFilter').val() || '');
        const periodName = $('#userPeriodFilter option:selected').data('period-name') || 'Periode Ini';
        $('#periodRoleHeader').text('Peran (' + periodName + ')');

        table.rows().nodes().each(function(rowNode) {
            let rolesByPeriod = {};
            try {
                rolesByPeriod = JSON.parse(rowNode.getAttribute('data-roles-by-period') || '{}');
            } catch (e) {}
            const pRoles = rolesByPeriod[periodId] || [];
            const isGlobalAdmin = rowNode.getAttribute('data-global-admin') === '1';
            const slug = rowNode.getAttribute('data-user-slug');
            const cell = rowNode.querySelector('.user-period-roles-cell');

            if (cell) {
                if (pRoles.length > 0) {
                    cell.innerHTML = pRoles.map(function(r) {
                        return '<span class="badge bg-primary mb-1 me-1">' + escapeHtml(r.name) + '</span>';
                    }).join('');
                } else if (isGlobalAdmin) {
                    cell.innerHTML = '<span class="badge bg-dark mb-1">Admin</span>';
                } else {
                    cell.innerHTML = '<span class="badge bg-light text-secondary border">Visitor</span>';
                }
            }

            const modal = document.getElementById('roleModal-' + slug);
            if (modal) {
                const form = modal.querySelector('form[data-action-template]');
                if (form) {
                    form.action = form.getAttribute('data-action-template').replace('__PERIOD__', encodeURIComponent(periodId));
                }
                const activeIds = pRoles.map(function(r) { return String(r.id); });
                modal.querySelectorAll('.user-role-checkbox').forEach(function(cb) {
                    cb.checked = activeIds.includes(String(cb.value));
                });
            }
        });
    }

    function syncUserResetBtn() {
        const defaultPeriod = String($('#userPeriodFilter').data('default') || '');
        const hasFilter = String($('#userPeriodFilter').val()) !== defaultPeriod
            || $('#userRoleFilter').val() !== ''
            || $('#userProdiFilter').val() !== ''
            || $('#userStatusFilter').val() !== '';
        $('#btnResetUserFilter').toggleClass('d-none', !hasFilter);
    }

    $('#userPeriodFilter').on('change', function() {
        updatePeriodDisplay();
        syncUserResetBtn();
        table.draw();
    });

    $('#userRoleFilter, #userProdiFilter, #userStatusFilter').on('change', function() {
        syncUserResetBtn();
        table.draw();
    });

    $('#btnResetUserFilter').on('click', function() {
        const defaultPeriod = String($('#userPeriodFilter').data('default') || '');
        $('#userPeriodFilter').val(defaultPeriod);
        $('#userRoleFilter, #userProdiFilter, #userStatusFilter').val('');
        updatePeriodDisplay();
        syncUserResetBtn();
        table.draw();
    });
});
</script>
@endpush
