<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ config('app.name', 'Peminjaman Ruangan Lab GWM') }}</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <!-- Datatables CSS -->
    <link rel="stylesheet" href="{{ asset('assets/css/plugin/datatables/dataTables.bootstrap5.min.css') }}">
    <style>
        .sidebar {
            width: 260px;
            transition: all 0.3s ease;
            position: relative;
        }
        .sidebar .nav-link {
            color: rgba(255, 255, 255, 0.85);
            padding: 0.6rem 1rem;
            border-radius: 0.375rem;
            margin-bottom: 0.2rem;
            display: flex;
            align-items: center;
            font-size: 0.925rem;
            text-decoration: none;
        }
        .sidebar .nav-link:hover {
            color: #fff;
            background-color: rgba(255, 255, 255, 0.15);
        }
        .sidebar .nav-link.active {
            color: #fff;
            background-color: rgba(255, 255, 255, 0.25);
            font-weight: 600;
        }
        .sidebar .sidebar-heading {
            font-size: 0.72rem;
            text-transform: uppercase;
            letter-spacing: 0.08rem;
            color: rgba(255, 255, 255, 0.55);
            margin-top: 1rem;
            margin-bottom: 0.35rem;
            padding-left: 0.75rem;
            font-weight: 700;
        }
        .main-content {
            min-width: 0;
            transition: all 0.3s ease;
        }
        .sidebar-toggle-btn {
            font-weight: bold;
            font-family: monospace;
            padding: 0.15rem 0.5rem;
            line-height: 1;
            font-size: 1rem;
            border-radius: 0.375rem;
        }
        @media (min-width: 992px) {
            .sidebar {
                position: sticky;
                top: 0;
                height: 100vh;
                overflow-y: auto;
            }
            body.sidebar-collapsed .sidebar {
                width: 68px;
                padding-left: 0.5rem !important;
                padding-right: 0.5rem !important;
            }
            body.sidebar-collapsed .sidebar-title-text,
            body.sidebar-collapsed .sidebar-content {
                display: none !important;
            }
            body.sidebar-collapsed .sidebar-header {
                justify-content: center !important;
                border-bottom: 0 !important;
                margin-bottom: 0 !important;
                padding-bottom: 0 !important;
            }
        }
        @media (max-width: 991.98px) {
            .sidebar {
                min-height: auto;
                width: 100%;
            }
        }
    </style>
    @stack('styles')
</head>
<body class="bg-body-tertiary">
    <div class="d-flex flex-column flex-lg-row min-vh-100">
        <aside class="sidebar bg-primary text-white d-flex flex-column flex-shrink-0 p-3 shadow-sm">
            <div class="d-flex align-items-center justify-content-between mb-3 pb-2 border-bottom border-white-50 sidebar-header">
                <a href="{{ route('home') }}" class="d-flex align-items-center text-white text-decoration-none sidebar-title-text">
                    <span class="fs-5 fw-bold">Lab GWM Lt. 8</span>
                </a>
                <div class="d-flex align-items-center gap-1">
                    <button class="btn btn-sm btn-outline-light d-none d-lg-inline-flex align-items-center justify-content-center sidebar-toggle-btn" id="btnToggleSidebar" type="button" title="Buka/Tutup Sidebar">
                        &lt;
                    </button>
                    <button class="navbar-toggler d-lg-none btn btn-sm btn-outline-light" type="button" data-bs-toggle="collapse" data-bs-target="#sidebarCollapse">
                        Menu
                    </button>
                </div>
            </div>

            <div class="collapse d-lg-block flex-grow-1 sidebar-content" id="sidebarCollapse">
                <ul class="nav nav-pills flex-column mb-auto">
                    <li class="nav-item">
                        <a class="nav-link {{ request()->routeIs('home*') ? 'active' : '' }}" href="{{ route('home') }}">
                            Jadwal & Denah
                        </a>
                    </li>

                    @auth
                        <div class="sidebar-heading">Layanan Peminjaman</div>
                        <li class="nav-item">
                            <a class="nav-link {{ request()->routeIs('bookings.index') ? 'active' : '' }}" href="{{ route('bookings.index') }}">
                                Riwayat Peminjaman
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link {{ request()->routeIs('bookings.create') ? 'active' : '' }}" href="{{ route('bookings.create') }}">
                                Ajukan Peminjaman
                            </a>
                        </li>

                        @if(auth()->user()->hasRole('Staf_Lab'))
                            <li class="nav-item">
                                <a class="nav-link {{ request()->routeIs('staff-bookings.*') ? 'active' : '' }}" href="{{ route('staff-bookings.create') }}">
                                    Input Dosen
                                </a>
                            </li>
                        @endif

                        @if(auth()->user()->hasRole('Kepala_Prodi', 'Kepala_Lab', 'Staf_Lab', 'Admin'))
                            <div class="sidebar-heading">Validasi & Approval</div>
                            @if(auth()->user()->hasRole('Kepala_Prodi', 'Kepala_Lab'))
                                <li class="nav-item">
                                    <a class="nav-link {{ request()->routeIs('approvals.*') ? 'active' : '' }}" href="{{ route('approvals.index') }}">
                                        Persetujuan Peminjaman
                                    </a>
                                </li>
                            @endif
                            @if(auth()->user()->hasRole('Kepala_Lab', 'Staf_Lab', 'Admin'))
                                <li class="nav-item">
                                    <a class="nav-link d-flex justify-content-between align-items-center {{ request()->routeIs('admin.visitor-approvals.*') ? 'active' : '' }}" href="{{ route('admin.visitor-approvals.index') }}">
                                        <span>Persetujuan Visitor</span>
                                        @php
                                            $pendingVisitors = \App\Models\User::where('status', 'pending')->count();
                                        @endphp
                                        @if($pendingVisitors > 0)
                                            <span class="badge rounded-pill bg-warning text-dark">{{ $pendingVisitors }}</span>
                                        @endif
                                    </a>
                                </li>
                            @endif
                        @endif

                        @if(auth()->user()->hasRole('Kepala_Lab', 'Admin'))
                            <div class="sidebar-heading">Manajemen Lab & Pengguna</div>
                            <li class="nav-item">
                                <a class="nav-link {{ request()->routeIs('admin.users.*') || request()->routeIs('admin.period-roles.*') ? 'active' : '' }}" href="{{ route('admin.users.index') }}">
                                    Kelola Pengguna
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link {{ request()->routeIs('admin.schedules.import*') ? 'active' : '' }}" href="{{ route('admin.schedules.import') }}">
                                    Import Jadwal Kuliah
                                </a>
                            </li>
                        @endif

                        @if(auth()->user()->hasRole('Admin'))
                            <div class="sidebar-heading">Administrasi Sistem</div>
                            <li class="nav-item">
                                <a class="nav-link {{ request()->routeIs('admin.periods.*') || request()->routeIs('admin.holidays.*') ? 'active' : '' }}" href="{{ route('admin.periods.index') }}">
                                    Periode Semester
                                </a>
                            </li>

                            <li class="nav-item">
                                <a class="nav-link text-white dropdown-toggle {{ request()->routeIs('master.*') ? 'active' : '' }}" href="#masterSubmenu" data-bs-toggle="collapse" role="button" aria-expanded="{{ request()->routeIs('master.*') ? 'true' : 'false' }}">
                                    Master Data
                                </a>
                                <div class="collapse {{ request()->routeIs('master.*') ? 'show' : '' }} ps-3" id="masterSubmenu">
                                    <ul class="nav flex-column">
                                        <li>
                                            <a class="nav-link {{ request()->is('master/rooms*') ? 'active' : '' }}" href="{{ route('master.index', 'rooms') }}">
                                                Ruangan Lab
                                            </a>
                                        </li>
                                        <li>
                                            <a class="nav-link {{ request()->is('master/courses*') ? 'active' : '' }}" href="{{ route('master.index', 'courses') }}">
                                                Mata Kuliah
                                            </a>
                                        </li>
                                        <li>
                                            <a class="nav-link {{ request()->is('master/study-programs*') ? 'active' : '' }}" href="{{ route('master.index', 'study-programs') }}">
                                                Program Studi
                                            </a>
                                        </li>
                                    </ul>
                                </div>
                            </li>
                        @endif

                        @if(auth()->user()->hasRole('Staf_Lab', 'Kepala_Prodi', 'Kepala_Lab', 'Admin'))
                            <div class="sidebar-heading">Audit & Laporan</div>
                            <li class="nav-item">
                                <a class="nav-link {{ request()->routeIs('logs.*') ? 'active' : '' }}" href="{{ route('logs.index') }}">
                                    Log Audit
                                </a>
                            </li>
                        @endif
                    @endauth
                </ul>

                <hr class="border-white-50 my-3">

                <div>
                    @auth
                        @php
                            $activeRoleNames = auth()->user()->activeRoles()->pluck('name')->map(fn($n) => str_replace('_', ' ', $n))->join(', ') ?: 'Visitor';
                        @endphp
                        <div class="dropdown">
                            <a href="#" class="d-flex align-items-center text-white text-decoration-none dropdown-toggle p-2 rounded hover-bg" data-bs-toggle="dropdown" aria-expanded="false">
                                <div class="bg-white text-primary rounded-circle d-flex align-items-center justify-content-center me-2 fw-bold" style="width: 32px; height: 32px; font-size: 14px;">
                                    {{ strtoupper(substr(auth()->user()->name, 0, 1)) }}
                                </div>
                                <div class="text-truncate" style="max-width: 170px;">
                                    <div class="fw-semibold small text-truncate">{{ auth()->user()->name }}</div>
                                    <div class="text-white-50" style="font-size: 0.75rem;">{{ $activeRoleNames }}</div>
                                </div>
                            </a>
                            <ul class="dropdown-menu dropdown-menu-dark shadow w-100">
                                <li><a class="dropdown-item" href="{{ route('password.change') }}">Ganti Kata Sandi</a></li>
                                <li><hr class="dropdown-divider"></li>
                                <li>
                                    <form method="post" action="{{ route('logout') }}">
                                        @csrf
                                        <button type="submit" class="dropdown-item text-danger">Keluar</button>
                                    </form>
                                </li>
                            </ul>
                        </div>
                    @else
                        <div class="d-grid gap-2">
                            <a class="btn btn-outline-light btn-sm text-start" href="{{ route('login') }}">
                                Masuk
                            </a>
                            <a class="btn btn-light btn-sm text-primary text-start fw-semibold" href="{{ route('register') }}">
                                Daftar Visitor
                            </a>
                        </div>
                    @endauth
                </div>
            </div>
        </aside>

        <div class="d-flex flex-column flex-grow-1 main-content min-vh-100">
            <main class="container-fluid px-3 px-md-4 py-4 flex-grow-1">
                @if(session('success'))
                    <div class="alert alert-success alert-dismissible fade show shadow-sm" role="alert">
                        {{ session('success') }}
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                @endif
                @if(session('warning'))
                    <div class="alert alert-warning alert-dismissible fade show shadow-sm" role="alert">
                        {{ session('warning') }}
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                @endif
                @if(session('error'))
                    <div class="alert alert-danger alert-dismissible fade show shadow-sm" role="alert">
                        {{ session('error') }}
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                @endif
                @if($errors->any())
                    <div class="alert alert-danger alert-dismissible fade show shadow-sm" role="alert">
                        <div class="fw-bold mb-1">Terdapat kesalahan pada isian Anda:</div>
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
                <div class="container-fluid px-4 text-center text-secondary small">
                    Sistem Informasi Monitoring & Peminjaman Ruangan Lab Komputer, Gedung Widya Maranatha (GWM) Lantai 8
                </div>
            </footer>
        </div>
    </div>

    <!-- jQuery & Bootstrap -->
    <script src="{{ asset('assets/js/plugin/datatables/jquery.min.js') }}"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
    
    <!-- Datatables -->
    <script src="{{ asset('assets/js/plugin/datatables/datatables.min.js') }}"></script>
    <script src="{{ asset('assets/js/plugin/datatables/dataTables.bootstrap5.min.js') }}"></script>
    <script>
        if (typeof $.fn.dataTable !== 'undefined') {
            $.extend(true, $.fn.dataTable.defaults, {
                language: {
                    search: "Cari Cepat:",
                    lengthMenu: "Tampilkan _MENU_ data",
                    info: "Menampilkan _START_ sampai _END_ dari _TOTAL_ data",
                    infoEmpty: "Menampilkan 0 data",
                    infoFiltered: "(disaring dari _MAX_ total data)",
                    zeroRecords: "Tidak ada data yang cocok",
                    emptyTable: "Tidak ada data di tabel",
                    paginate: {
                        first: "Awal",
                        last: "Akhir",
                        next: "Berikutnya",
                        previous: "Sebelumnya"
                    }
                },
                responsive: true,
                autoWidth: false,
                pageLength: 10,
                lengthMenu: [[10, 25, 50, 100, -1], [10, 25, 50, 100, "Semua"]]
            });
        }
    </script>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const btnToggle = document.getElementById('btnToggleSidebar');
            
            function updateToggleIcon() {
                if (btnToggle) {
                    const isCollapsed = document.body.classList.contains('sidebar-collapsed');
                    btnToggle.innerHTML = isCollapsed ? '&gt;' : '&lt;';
                    btnToggle.setAttribute('title', isCollapsed ? 'Buka Sidebar' : 'Tutup Sidebar');
                }
            }

            if (localStorage.getItem('sidebar_collapsed') === '1') {
                document.body.classList.add('sidebar-collapsed');
            }
            updateToggleIcon();

            if (btnToggle) {
                btnToggle.addEventListener('click', function () {
                    document.body.classList.toggle('sidebar-collapsed');
                    const isCollapsed = document.body.classList.contains('sidebar-collapsed');
                    localStorage.setItem('sidebar_collapsed', isCollapsed ? '1' : '0');
                    updateToggleIcon();
                });
            }
        });
    </script>
    @stack('scripts')
</body>
</html>
