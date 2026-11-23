@extends('layouts.app')

@section('content')
<div class="row justify-content-center">
    <div class="col-md-6 col-lg-5">
        <div class="card shadow-sm border-0 mt-5">
            <div class="card-body p-4 text-center">
                <div class="mb-3 text-warning">
                    <svg xmlns="http://www.w3.org/2000/svg" width="56" height="56" fill="currentColor" class="bi bi-envelope-exclamation" viewBox="0 0 16 16">
                        <path d="M2 2a2 2 0 0 0-2 2v8.01A2 2 0 0 0 2 14h5.5a.5.5 0 0 0 0-1H2a1 1 0 0 1-.966-.741l5.64-3.471L8 9.583l7-4.2V8.5a.5.5 0 0 0 1 0V4a2 2 0 0 0-2-2zm3.708 6.208L1 11.105V5.383zM1 4.217V4a1 1 0 0 1 1-1h12a1 1 0 0 1 1 1v.217l-7 4.2z"/>
                        <path d="M12.5 16a3.5 3.5 0 1 0 0-7 3.5 3.5 0 0 0 0 7m.5-5v1.5a.5.5 0 0 1-1 0V11a.5.5 0 0 1 1 0m0 3a.5.5 0 1 1-1 0 .5.5 0 0 1 1 0"/>
                    </svg>
                </div>

                <h1 class="h4 fw-bold mb-2">Verifikasi Alamat Email Anda</h1>
                <p class="text-secondary small mb-4">
                    Tautan verifikasi telah dikirimkan ke alamat email <strong>{{ auth()->user()->email ?? '' }}</strong>.
                </p>

                @if (session('status') == 'verification-link-sent')
                    <div class="alert alert-success py-2 px-3 small text-start mb-4" role="alert">
                        <i class="bi bi-check-circle me-1"></i> Tautan verifikasi baru telah berhasil dikirim ke alamat email Anda. Silakan periksa kotak masuk atau folder spam.
                    </div>
                @endif

                <div class="card bg-light border-0 p-3 text-start small mb-4">
                    <div class="fw-semibold mb-1">Tahap 1 dari 2: Verifikasi Email</div>
                    <p class="text-secondary mb-0">
                        Sistem memerlukan konfirmasi email untuk memastikan akun Anda aktif dan dapat menerima notifikasi status peminjaman. Setelah email terverifikasi, akun Anda akan diverifikasi oleh Kepala Lab / Staf Lab pada Tahap 2.
                    </p>
                </div>

                <div class="d-flex flex-column gap-2">
                    <form method="POST" action="{{ route('verification.send') }}">
                        @csrf
                        <button type="submit" class="btn btn-primary w-100 py-2 fw-semibold">
                            Kirim Ulang Email Verifikasi
                        </button>
                    </form>

                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" class="btn btn-outline-secondary w-100 py-2">
                            Keluar / Ganti Akun
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
