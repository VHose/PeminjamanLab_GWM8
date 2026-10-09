@extends('layouts.app')

@section('content')
<div class="row justify-content-center">
    <div class="col-md-5">
        <div class="card shadow-sm border-0 mt-4">
            <div class="card-body p-4">
                <div class="text-center mb-4">
                    <h1 class="h4 fw-bold">Reset Kata Sandi</h1>
                    <p class="text-secondary small mb-0">Silakan masukkan kata sandi baru Anda</p>
                </div>

                <form method="post" action="{{ route('password.update') }}">
                    @csrf
                    <input type="hidden" name="token" value="{{ $token }}">

                    <div class="mb-3">
                        <label class="form-label fw-semibold">Alamat Email</label>
                        <input class="form-control @error('email') is-invalid @enderror" name="email" type="email" value="{{ old('email', $email) }}" required autofocus>
                        @error('email')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold">Kata Sandi Baru</label>
                        <input class="form-control @error('password') is-invalid @enderror" name="password" type="password" placeholder="Minimal 8 karakter" required>
                        @error('password')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold">Konfirmasi Kata Sandi Baru</label>
                        <input class="form-control" name="password_confirmation" type="password" placeholder="Ulangi kata sandi baru" required>
                    </div>

                    <button type="submit" class="btn btn-primary w-100 py-2">Simpan Kata Sandi Baru</button>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
