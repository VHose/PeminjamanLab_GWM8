@extends('layouts.app')

@section('content')
<div class="row justify-content-center">
    <div class="col-md-5">
        <div class="card shadow-sm border-0 mt-4">
            <div class="card-body p-4">
                <div class="text-center mb-4">
                    <h1 class="h4 fw-bold">Pendaftaran Akun Visitor</h1>
                    <p class="text-secondary small mb-0">Daftarkan akun untuk mengajukan peminjaman laboratorium</p>
                </div>

                <form method="post" action="{{ route('register') }}">
                    @csrf

                    <div class="mb-3">
                        <label class="form-label fw-semibold">Nama Lengkap</label>
                        <input class="form-control" name="name" type="text" value="{{ old('name') }}" placeholder="Masukkan nama lengkap Anda..." required autofocus>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold">Alamat Email</label>
                        <input class="form-control" name="email" type="email" value="{{ old('email') }}" placeholder="nama@example.com" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold">Kata Sandi</label>
                        <input class="form-control" name="password" type="password" placeholder="Minimal 8 karakter..." required>
                    </div>

                    <div class="mb-4">
                        <label class="form-label fw-semibold">Konfirmasi Kata Sandi</label>
                        <input class="form-control" name="password_confirmation" type="password" placeholder="Ketik ulang kata sandi..." required>
                    </div>

                    <button type="submit" class="btn btn-primary w-100 py-2">Daftar Akun</button>
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
