@extends('layouts.app')

@section('content')
<div class="row justify-content-center">
    <div class="col-md-6 col-lg-5">
        <div class="card shadow-sm border-0 mt-4 mb-5">
            <div class="card-body p-4">
                <div class="text-center mb-4">
                    <h1 class="h4 fw-bold">Pendaftaran Akun Visitor</h1>
                    <p class="text-secondary small mb-0">Daftarkan akun eksternal untuk mengajukan peminjaman laboratorium</p>
                </div>

                <div class="p-3 bg-light border rounded small mb-4 text-secondary">
                    <div class="fw-semibold text-dark mb-1">Alur Pendaftaran (2 Tahap):</div>
                    <ol class="mb-0 ps-3">
                        <li><strong class="text-dark">Verifikasi Email:</strong> Tautan konfirmasi akan dikirimkan ke email Anda.</li>
                        <li><strong class="text-dark">Persetujuan Pengelola:</strong> Akun Anda akan ditinjau oleh Kepala Lab / Staf Lab sebelum dapat membuat pengajuan peminjaman.</li>
                    </ol>
                </div>

                <form method="post" action="{{ route('register') }}">
                    @csrf

                    <div class="mb-3">
                        <label class="form-label fw-semibold">Nama Lengkap / PIC <span class="text-danger">*</span></label>
                        <input class="form-control @error('name') is-invalid @enderror" name="name" type="text" value="{{ old('name') }}" placeholder="Masukkan nama lengkap Anda..." required autofocus maxlength="80">
                        @error('name')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold">Instansi / Lembaga / Organisasi <span class="text-danger">*</span></label>
                        <input class="form-control @error('pic_or_instansi') is-invalid @enderror" name="pic_or_instansi" type="text" value="{{ old('pic_or_instansi') }}" placeholder="Contoh: BEM Fakultas / PT Mitra / Komunitas..." required maxlength="100">
                        @error('pic_or_instansi')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold">Nomor Telepon / WhatsApp <span class="text-danger">*</span></label>
                        <input class="form-control @error('phone') is-invalid @enderror" name="phone" type="text" value="{{ old('phone') }}" placeholder="Contoh: 081234567890" required maxlength="15">
                        @error('phone')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold">Alamat Email <span class="text-danger">*</span></label>
                        <input class="form-control @error('email') is-invalid @enderror" name="email" type="email" value="{{ old('email') }}" placeholder="nama@example.com" required maxlength="70">
                        <div class="form-text small">Pastikan email aktif untuk menerima tautan verifikasi.</div>
                        @error('email')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold">Kata Sandi <span class="text-danger">*</span></label>
                        <input class="form-control @error('password') is-invalid @enderror" name="password" type="password" placeholder="Minimal 8 karakter..." required>
                        @error('password')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mb-4">
                        <label class="form-label fw-semibold">Konfirmasi Kata Sandi <span class="text-danger">*</span></label>
                        <input class="form-control" name="password_confirmation" type="password" placeholder="Ketik ulang kata sandi..." required>
                    </div>

                    <button type="submit" class="btn btn-primary w-100 py-2 fw-semibold">Daftar Akun Sekarang</button>
                </form>

                <hr class="my-4">

                <div class="text-center">
                    <p class="small text-secondary mb-0">Sudah memiliki akun? <a href="{{ route('login') }}" class="fw-semibold">Masuk di sini</a></p>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
