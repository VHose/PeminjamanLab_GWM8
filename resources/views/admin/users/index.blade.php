@extends('layouts.app')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h1 class="h3 fw-bold mb-1">Kelola Pengguna & Peran (User & User Role)</h1>
        <p class="text-secondary mb-0">Kelola akun, NIK/ID Pengguna, dan penetapan masa berlaku peran (User Role).</p>
    </div>
    <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addUserModal">
        + Tambah Pengguna Baru
    </button>
</div>

@if(session('success'))
    <div class="alert alert-success alert-dismissible fade show mb-4" role="alert">
        {{ session('success') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
@endif

<div class="card shadow-sm border-0 mb-4">
    <div class="card-header bg-white py-3">
        <h2 class="h5 fw-bold mb-0">Daftar Pengguna (Total: {{ $users->total() }})</h2>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>NIK / ID Visitor</th>
                        <th>Nama</th>
                        <th>Email</th>
                        <th>Program Studi</th>
                        <th>Peran (User Role) Aktif & Riwayat</th>
                        <th class="text-end" style="width: 220px;">Aksi Peran</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($users as $user)
                        <tr>
                            <td class="fw-bold text-primary font-monospace">{{ $user->id }}</td>
                            <td class="fw-semibold">{{ $user->name }}</td>
                            <td>{{ $user->email }}</td>
                            <td>{{ $user->studyProgram->name ?? '-' }}</td>
                            <td>
                                @forelse($user->userRoles as $ur)
                                    @php
                                        $isActive = $ur->isActive();
                                    @endphp
                                    <div class="d-flex align-items-center justify-content-between border rounded p-1 mb-1 small {{ $isActive ? 'bg-light' : 'bg-body-secondary text-muted' }}">
                                        <div>
                                            <span class="badge {{ $isActive ? 'bg-success' : 'bg-secondary' }}">
                                                {{ $ur->role->name }}
                                            </span>
                                            <span style="font-size: 0.75rem;">
                                                ({{ $ur->start_date->format('d/m/Y') }} s/d {{ $ur->end_date ? $ur->end_date->format('d/m/Y') : 'Sekarang' }})
                                            </span>
                                        </div>
                                        @if($isActive)
                                            <form method="post" action="{{ route('admin.users.deactivateRole', $ur) }}" class="ms-2">
                                                @csrf
                                                <button type="submit" class="btn btn-outline-danger btn-sm py-0 px-1" style="font-size: 0.70rem;" onclick="return confirm('Nonaktifkan role ini sekarang?')">
                                                    Nonaktifkan
                                                </button>
                                            </form>
                                        @endif
                                    </div>
                                @empty
                                    <span class="badge bg-secondary">Visitor (Default)</span>
                                @endforelse
                            </td>
                            <td class="text-end">
                                <button type="button" class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#assignRoleModal-{{ Str::slug($user->id) }}">
                                    + Beri Role
                                </button>

                                <!-- Modal Assign Role -->
                                <div class="modal fade text-start" id="assignRoleModal-{{ Str::slug($user->id) }}" tabindex="-1" aria-hidden="true">
                                    <div class="modal-dialog">
                                        <div class="modal-content">
                                            <form method="post" action="{{ route('admin.users.assignRole', $user) }}">
                                                @csrf
                                                <div class="modal-header">
                                                    <h5 class="modal-title fw-bold">Tambah Role: {{ $user->name }}</h5>
                                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                </div>
                                                <div class="modal-body">
                                                    <div class="mb-3">
                                                        <label class="form-label fw-semibold">Pilih Role</label>
                                                        <select name="role_id" class="form-select" required>
                                                            @foreach($allRoles as $r)
                                                                <option value="{{ $r->id }}">{{ $r->name }}</option>
                                                            @endforeach
                                                        </select>
                                                    </div>
                                                    <div class="mb-3">
                                                        <label class="form-label fw-semibold">Tanggal Mulai Berlaku</label>
                                                        <input type="date" name="start_date" class="form-control" value="{{ now()->toDateString() }}" required>
                                                    </div>
                                                    <div class="mb-3">
                                                        <label class="form-label fw-semibold">Tanggal Berakhir (Opsional / Kosongkan jika Aktif Terus)</label>
                                                        <input type="date" name="end_date" class="form-control">
                                                    </div>
                                                </div>
                                                <div class="modal-footer">
                                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                                                    <button type="submit" class="btn btn-primary">Simpan Role</button>
                                                </div>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center py-4 text-muted">Belum ada pengguna.</td>
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

<!-- Modal Add User -->
<div class="modal fade" id="addUserModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="post" action="{{ route('admin.users.store') }}">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title fw-bold">Tambah Pengguna Baru (Dosen / Staf)</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label fw-semibold">NIK (Sebagai ID Akun) <span class="text-danger">*</span></label>
                        <input type="text" name="id" class="form-control" placeholder="Contoh: 198501012010121001" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Nama Lengkap <span class="text-danger">*</span></label>
                        <input type="text" name="name" class="form-control" placeholder="Nama lengkap beserta gelar..." required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Email <span class="text-danger">*</span></label>
                        <input type="email" name="email" class="form-control" placeholder="email@kampus.ac.id" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">No. Telepon</label>
                        <input type="text" name="phone" class="form-control" placeholder="08xxxxxxxxxx">
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Program Studi (Khusus Dosen/Kaprodi)</label>
                        <select name="study_program_id" class="form-select">
                            <option value="">-- Tanpa Program Studi --</option>
                            @foreach($studyPrograms as $sp)
                                <option value="{{ $sp->id }}">{{ $sp->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Peran Awal (Role) <span class="text-danger">*</span></label>
                        <select name="role_id" class="form-select" required>
                            @foreach($allRoles as $r)
                                <option value="{{ $r->id }}" @selected($r->name === 'Dosen')>{{ $r->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="row g-2 mb-3">
                        <div class="col-6">
                            <label class="form-label fw-semibold">Tanggal Mulai Role</label>
                            <input type="date" name="start_date" class="form-control" value="{{ now()->toDateString() }}" required>
                        </div>
                        <div class="col-6">
                            <label class="form-label fw-semibold">Tanggal Berakhir</label>
                            <input type="date" name="end_date" class="form-control">
                        </div>
                    </div>
                    <div class="alert alert-info small mb-0">
                        Password akun akan dibuat secara acak. Pengguna dapat menggunakan fitur <strong>Lupa Password</strong> untuk membuat kata sandi pertama kali.
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary">Buat Akun</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
