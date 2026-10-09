@extends('layouts.app')

@section('content')
<div class="row justify-content-center">
    <div class="col-md-5">
        <div class="card shadow-sm border-0 mt-4">
            <div class="card-body p-4">
                <div class="text-center mb-4">
                    <h1 class="h4 fw-bold">Lupa Kata Sandi</h1>
                    <p class="text-secondary small mb-0">Masukkan alamat email Anda untuk menerima tautan reset kata sandi</p>
                </div>

                @if(session('status'))
                    <div class="alert alert-success small mb-3">
                        {{ session('status') }}
                    </div>
                @endif

                <form method="post" action="{{ route('password.email') }}">
                    @csrf

                    <div class="mb-3">
                        <label class="form-label fw-semibold">Alamat Email</label>
                        <input class="form-control @error('email') is-invalid @enderror" name="email" type="email" value="{{ old('email') }}" placeholder="nama@example.com" required autofocus>
                        @error('email')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <button type="submit" class="btn btn-primary w-100 py-2">Kirim Tautan Reset Kata Sandi</button>
                </form>

                <hr class="my-4">

                <div class="text-center">
                    <a href="{{ route('login') }}" class="small text-decoration-none">Kembali ke Halaman Masuk</a>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
