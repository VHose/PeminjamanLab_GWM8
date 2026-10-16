<?php /** @var \App\Models\User $user */ ?>
@extends('layouts.app')

@section('content')
<div class="row justify-content-center">
    <div class="col-md-6">
        <div class="card shadow-sm border-0">
            <div class="card-header bg-white py-3">
                <h1 class="h5 fw-bold mb-0">
                    {{ $isForced ? 'Wajib Ganti Kata Sandi Awal' : 'Ganti Kata Sandi' }}
                </h1>
            </div>
            <div class="card-body p-4">
                @if($isForced)
                    <div class="alert alert-warning mb-4">
                        <div class="fw-bold mb-1"><i class="bi bi-shield-exclamation"></i> Penggantian Kata Sandi Diperlukan</div>
                        Akun Anda baru dibuat atau menggunakan kata sandi awal sistem. Untuk alasan keamanan, silakan tentukan kata sandi baru Anda sebelum menggunakan fitur aplikasi.
                    </div>
                @endif

                <form method="post" action="{{ route('password.change.update') }}">
                    @csrf
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Kata Sandi Saat Ini</label>
                        <input type="password" name="current_password" class="form-control" required autocomplete="current-password">
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold">Kata Sandi Baru</label>
                        <input type="password" name="password" class="form-control" required minlength="8" autocomplete="new-password">
                        <div class="form-text">Minimal 8 karakter dan berbeda dari kata sandi lama.</div>
                    </div>

                    <div class="mb-4">
                        <label class="form-label fw-semibold">Konfirmasi Kata Sandi Baru</label>
                        <input type="password" name="password_confirmation" class="form-control" required minlength="8" autocomplete="new-password">
                    </div>

                    <div class="d-flex justify-content-between align-items-center">
                        @if(!$isForced)
                            <a href="{{ route('home') }}" class="btn btn-outline-secondary">Batal</a>
                        @else
                            <span></span>
                        @endif
                        <button type="submit" class="btn btn-primary px-4">Simpan Kata Sandi</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
