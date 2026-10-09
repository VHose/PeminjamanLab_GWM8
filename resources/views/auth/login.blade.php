@extends('layouts.app')

@section('content')
<div class="row justify-content-center">
    <div class="col-md-5">
        <div class="card shadow-sm border-0 mt-4">
            <div class="card-body p-4">
                <div class="text-center mb-4">
                    <h1 class="h4 fw-bold">Masuk ke Sistem</h1>
                    <p class="text-secondary small mb-0">Silakan masukkan email dan kata sandi Anda</p>
                </div>

                <form method="post" action="{{ route('login') }}">
                    @csrf

                    <div class="mb-3">
                        <label class="form-label fw-semibold">Alamat Email</label>
                        <input class="form-control" name="email" type="email" value="{{ old('email') }}" placeholder="nama@example.com" required autofocus>
                    </div>

                    <div class="mb-3">
                        <div class="d-flex justify-content-between align-items-center">
                            <label class="form-label fw-semibold mb-0">Kata Sandi</label>
                            <a href="{{ route('password.request') }}" class="small text-decoration-none">Lupa kata sandi?</a>
                        </div>
                        <input class="form-control mt-1" name="password" type="password" placeholder="Masukkan kata sandi..." required>
                    </div>

                    <div class="form-check mb-3">
                        <input class="form-check-input" type="checkbox" name="remember" id="rememberMe">
                        <label class="form-check-label small" for="rememberMe">Ingat saya di perangkat ini</label>
                    </div>

                    <button type="submit" class="btn btn-primary w-100 py-2">Masuk</button>
                </form>

                <hr class="my-4">

                <div class="text-center">
                    <p class="small text-secondary mb-0">Belum memiliki akun Visitor? <a href="{{ route('register') }}" class="fw-semibold">Daftar Akun Visitor</a></p>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection