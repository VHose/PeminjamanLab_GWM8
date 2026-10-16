@extends('layouts.app')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h1 class="h3 fw-bold mb-1">Kelola Pengguna Sistem</h1>
        <p class="text-secondary mb-0">Kelola akun administrator, staf, dosen, dan reset kata sandi pengguna.</p>
    </div>
    <div class="d-flex gap-2">
        <a href="{{ route('admin.period-roles.index') }}" class="btn btn-outline-primary">
            Kelola Peran per Periode
        </a>
        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addAdminModal">
            + Tambah Admin Baru
        </button>
    </div>
</div>

<div class="card shadow-sm border-0 mb-4">
    <div class="card-header bg-white py-3">
        <h2 class="h5 fw-bold mb-0">Daftar Akun Pengguna (Total: {{ $users->total() }})</h2>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>NIK / ID</th>
                        <th>Nama Pengguna</th>
                        <th>Email</th>
                        <th>Program Studi</th>
                        <th>Peran (Periode Aktif)</th>
                        <th>Status</th>
                        <th class="text-end" style="width: 200px;">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($users as $user)
                        @php
                            $isInvalidEmail = str_ends_with($user->email, '@belum-diisi.invalid');
                        @endphp
                        <tr>
                            <td class="fw-bold font-monospace text-primary">{{ $user->id }}</td>
                            <td class="fw-semibold">
                                {{ $user->name }}
                                @if($user->must_change_password)
                                    <span class="badge text-bg-warning ms-1" title="Wajib ganti password saat login berikutnya">Ganti Sandi Awal</span>
                                @endif
                            </td>
                            <td>
                                @if($isInvalidEmail)
                                    <span class="text-danger fw-semibold">{{ $user->email }}</span>
                                    <span class="badge text-bg-danger ms-1">Invalid</span>
                                @else
                                    {{ $user->email }}
                                @endif
                            </td>
                            <td>{{ $user->studyProgram->name ?? '-' }}</td>
                            <td>
                                @forelse($user->roles as $r)
                                    <span class="badge bg-secondary mb-1">{{ str_replace('_', ' ', $r->name) }}</span>
                                @empty
                                    <span class="badge bg-light text-secondary border">Visitor</span>
                                @endforelse
                            </td>
                            <td>
                                @if($user->is_active)
                                    <span class="badge bg-success">Aktif</span>
                                @else
                                    <span class="badge bg-danger">Nonaktif</span>
                                @endif
                            </td>
                            <td class="text-end">
                                <div class="btn-group">
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

                                <!-- Modal Reset Password -->
                                <div class="modal fade text-start" id="resetPasswordModal-{{ Str::slug($user->id) }}" tabindex="-1" aria-hidden="true">
                                    <div class="modal-dialog">
                                        <div class="modal-content">
                                            <form method="post" action="{{ route('admin.users.resetPassword', $user) }}">
                                                @csrf
                                                <div class="modal-header">
                                                    <h5 class="modal-title fw-bold">Reset Kata Sandi: {{ $user->name }}</h5>
                                                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                </div>
                                                <div class="modal-body">
                                                    <p class="small text-secondary mb-3">
                                                        Masukkan kata sandi baru untuk pengguna <strong>{{ $user->id }}</strong> ({{ $user->name }}).
                                                    </p>
                                                    <div class="mb-3">
                                                        <label class="form-label fw-semibold">Kata Sandi Baru <span class="text-danger">*</span></label>
                                                        <input type="password" name="password" class="form-control" required minlength="8" autocomplete="new-password">
                                                    </div>
                                                    <div class="mb-3">
                                                        <label class="form-label fw-semibold">Konfirmasi Kata Sandi Baru <span class="text-danger">*</span></label>
                                                        <input type="password" name="password_confirmation" class="form-control" required minlength="8" autocomplete="new-password">
                                                    </div>
                                                    <div class="form-check form-switch mb-0">
                                                        <input class="form-check-input" type="checkbox" name="must_change_password" value="1" id="mcp-{{ Str::slug($user->id) }}" checked>
                                                        <label class="form-check-label" for="mcp-{{ Str::slug($user->id) }}">
                                                            Paksa pengguna mengganti kata sandi saat login pertama kali
                                                        </label>
                                                    </div>
                                                </div>
                                                <div class="modal-footer">
                                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                                                    <button type="submit" class="btn btn-danger">Simpan & Reset</button>
                                                </div>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center py-4 text-muted">Belum ada pengguna.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    @if($users->hasPages())
        <div class="card-footer bg-white">
            {{ $users->links() }}
        </div>
    @endif
</div>

<!-- Modal Tambah Admin Baru -->
<div class="modal fade" id="addAdminModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="post" action="{{ route('admin.users.store') }}">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title fw-bold">Tambah Akun Administrator Baru</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p class="small text-secondary mb-3">
                        Akun administrator baru akan otomatis memiliki hak akses penuh ke panel admin dan diwajibkan mengganti kata sandi saat pertama kali login.
                    </p>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">NIK / ID Admin <span class="text-danger">*</span></label>
                        <input type="text" name="id" class="form-control" placeholder="Contoh: ADMIN2" required maxlength="10">
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Nama Lengkap <span class="text-danger">*</span></label>
                        <input type="text" name="name" class="form-control" placeholder="Nama administrator" required maxlength="100">
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Alamat Email <span class="text-danger">*</span></label>
                        <input type="email" name="email" class="form-control" placeholder="admin@maranatha.edu" required maxlength="100">
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">No. Telepon</label>
                        <input type="text" name="phone" class="form-control" placeholder="08xxxxxxxxxx" maxlength="20">
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Kata Sandi Awal <span class="text-danger">*</span></label>
                        <input type="password" name="password" class="form-control" required minlength="8" autocomplete="new-password">
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Konfirmasi Kata Sandi Awal <span class="text-danger">*</span></label>
                        <input type="password" name="password_confirmation" class="form-control" required minlength="8" autocomplete="new-password">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary">Buat Akun Admin</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
