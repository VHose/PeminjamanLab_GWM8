<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ config('app.name', 'Peminjaman Ruangan Lab') }}</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
</head>
<body class="bg-body-tertiary d-flex flex-column min-vh-100">
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary shadow-sm">
        <div class="container">
            <a class="navbar-brand fw-semibold" href="{{ route('home') }}">Peminjaman Ruangan Lab</a>
            <div class="ms-auto d-flex gap-2 align-items-center text-white small">@auth <a class="text-white" href="{{ route('bookings.index') }}">Peminjaman</a>@if(auth()->user()->isInternal())<a class="text-white" href="{{ route('sections.index') }}">Jadwal</a><a class="text-white" href="{{ route('master.index','rooms') }}">Master</a>@endif @if(auth()->user()->hasRole('Kepala_Prodi','Kepala_Lab'))<a class="text-white" href="{{ route('approvals.index') }}">Approval</a>@endif <form method="post" action="{{ route('logout') }}">@csrf<button class="btn btn-sm btn-light">Keluar</button></form>@else <a class="btn btn-sm btn-light" href="{{ route('login') }}">Masuk</a>@endauth</div>
        </div>
    </nav>

    <main class="container py-5 flex-grow-1">
        @if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
        @if($errors->any())<div class="alert alert-danger"><ul class="mb-0">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
        @yield('content')
    </main>

    <footer class="border-top bg-white py-3">
        <div class="container text-secondary small">Sistem Peminjaman Ruangan / Lab</div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
</body>
</html>
