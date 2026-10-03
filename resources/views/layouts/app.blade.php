<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ config('app.name', 'Peminjaman Ruangan Lab GWM') }}</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
</head>
<body class="bg-body-tertiary d-flex flex-column min-vh-100">
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary shadow-sm">
        <div class="container">
            <a class="navbar-brand fw-bold" href="{{ route('home') }}">Peminjaman Ruangan Lab GWM</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navMenu">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navMenu">
                <ul class="navbar-nav me-auto mb-2 mb-lg-0">
                    <li class="nav-item">
                        <a class="nav-link active" href="{{ route('home') }}">Jadwal & Denah</a>
                    </li>
                    @auth
                        <li class="nav-item">
                            <a class="nav-link text-white" href="{{ route('bookings.index') }}">Peminjaman</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link text-white" href="{{ route('bookings.create') }}">Ajukan Peminjaman</a>
                        </li>
                        @if(auth()->user()->isInternal())
                            <li class="nav-item">
                                <a class="nav-link text-white" href="{{ route('staff-bookings.create') }}">Input Dosen</a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link text-white" href="{{ route('sections.index') }}">Jadwal Kuliah</a>
                            </li>
                            <li class="nav-item dropdown">
                                <a class="nav-link dropdown-toggle text-white" href="#" role="button" data-bs-toggle="dropdown">
                                    Master Data
                                </a>
                                <ul class="dropdown-menu">
                                    <li><a class="dropdown-item" href="{{ route('master.index', 'rooms') }}">Ruangan</a></li>
                                    <li><a class="dropdown-item" href="{{ route('master.index', 'lecturers') }}">Dosen</a></li>
                                    <li><a class="dropdown-item" href="{{ route('master.index', 'courses') }}">Mata Kuliah</a></li>
                                    <li><a class="dropdown-item" href="{{ route('master.index', 'study-programs') }}">Program Studi</a></li>
                                    <li><a class="dropdown-item" href="{{ route('master.index', 'periods') }}">Periode Semester</a></li>
                                </ul>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link text-white" href="{{ route('logs.index') }}">Log Audit</a>
                            </li>
                        @endif
                        @if(auth()->user()->hasRole('Kepala_Prodi', 'Kepala_Lab'))
                            <li class="nav-item">
                                <a class="nav-link text-white" href="{{ route('approvals.index') }}">Persetujuan</a>
                            </li>
                        @endif
                    @endauth
                </ul>
                <div class="d-flex align-items-center gap-2">
                    @auth
                        <span class="badge text-bg-light">{{ auth()->user()->name }} ({{ str_replace('_', ' ', auth()->user()->role?->name ?? 'User') }})</span>
                        <form method="post" action="{{ route('logout') }}" class="d-inline">
                            @csrf
                            <button type="submit" class="btn btn-sm btn-outline-light">Keluar</button>
                        </form>
                    @else
                        <a class="btn btn-sm btn-outline-light" href="{{ route('login') }}">Masuk</a>
                        <a class="btn btn-sm btn-light" href="{{ route('register') }}">Daftar Visitor</a>
                    @endauth
                </div>
            </div>
        </div>
    </nav>

    <main class="container py-4 flex-grow-1">
        @if(session('success'))
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                {{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif
        @if(session('error'))
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                {{ session('error') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif
        @if($errors->any())
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <div class="fw-bold mb-1">Periksa kembali data Anda:</div>
                <ul class="mb-0">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif

        @yield('content')
    </main>

    <footer class="border-top bg-white py-3 mt-auto">
        <div class="container text-center text-secondary small">
            Sistem Informasi Monitoring & Peminjaman Ruangan Lab Komputer — Gedung Kuliah Bersama (GWM) Lantai 8
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
    @stack('scripts')
</body>
</html>
