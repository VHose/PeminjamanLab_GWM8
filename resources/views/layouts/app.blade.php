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
        .dataTables_wrapper .dataTables_filter {
            text-align: right;
        }
        .dataTables_wrapper .dataTables_filter label {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            margin-bottom: 0;
        }
        .dataTables_wrapper .dataTables_length label {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            margin-bottom: 0;
        }
        .dataTables_wrapper .dataTables_paginate ul.pagination {
            justify-content: flex-end;
            margin-bottom: 0;
        }
        /* ── Simple Bottom-Right Stacked Toast & Confirm UI ── */
        .app-toast-stack {
            position: fixed;
            right: 20px;
            bottom: 20px;
            z-index: 1095;
            display: flex;
            flex-direction: column-reverse;
            align-items: flex-end;
            gap: 10px;
            max-width: calc(100vw - 32px);
            max-height: calc(100vh - 40px);
            overflow-y: auto;
            pointer-events: none;
            padding: 4px;
        }
        .app-toast-card {
            pointer-events: auto;
            width: 340px;
            max-width: calc(100vw - 40px);
            background: #ffffff;
            color: #1f2937;
            border: 1px solid #e5e7eb;
            border-left: 4px solid #2563eb;
            border-radius: 0.6rem;
            box-shadow: 0 8px 24px rgba(15, 23, 42, 0.12), 0 2px 6px rgba(15, 23, 42, 0.06);
            padding: 0.75rem 0.9rem;
            font-size: 0.85rem;
            line-height: 1.45;
            opacity: 1;
            transform: translateY(0);
            transition: opacity 0.2s ease, transform 0.2s ease;
        }
        .app-toast-card.hiding {
            opacity: 0;
            transform: translateY(8px);
        }
        .app-toast-card.toast-success { border-left-color: #16a34a; }
        .app-toast-card.toast-warning { border-left-color: #d97706; }
        .app-toast-card.toast-danger  { border-left-color: #dc2626; }
        .app-toast-card.toast-info    { border-left-color: #2563eb; }
        .app-toast-close {
            background: transparent;
            border: 0;
            color: #9ca3af;
            font-size: 1.1rem;
            line-height: 1;
            padding: 0 0 0 0.5rem;
            cursor: pointer;
        }
        .app-toast-close:hover {
            color: #374151;
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
                @yield('content')
            </main>
        </div>
    </div>

    {{-- Stack Pop-up Alert & Confirm Pojok Kanan Bawah --}}
    <div id="appToastStack" class="app-toast-stack" aria-live="polite" aria-atomic="false">
        @if(session('success'))
            <div class="app-toast-card toast-success" data-auto-dismiss="4500" role="alert">
                <div class="d-flex justify-content-between align-items-start">
                    <div>{{ session('success') }}</div>
                    <button type="button" class="app-toast-close" aria-label="Tutup">&times;</button>
                </div>
            </div>
        @endif
        @if(session('status'))
            <div class="app-toast-card toast-info" data-auto-dismiss="4500" role="alert">
                <div class="d-flex justify-content-between align-items-start">
                    <div>{{ session('status') }}</div>
                    <button type="button" class="app-toast-close" aria-label="Tutup">&times;</button>
                </div>
            </div>
        @endif
        @if(session('warning'))
            <div class="app-toast-card toast-warning" data-auto-dismiss="5500" role="alert">
                <div class="d-flex justify-content-between align-items-start">
                    <div>{{ session('warning') }}</div>
                    <button type="button" class="app-toast-close" aria-label="Tutup">&times;</button>
                </div>
            </div>
        @endif
        @if(session('error'))
            <div class="app-toast-card toast-danger" data-auto-dismiss="6000" role="alert">
                <div class="d-flex justify-content-between align-items-start">
                    <div>{{ session('error') }}</div>
                    <button type="button" class="app-toast-close" aria-label="Tutup">&times;</button>
                </div>
            </div>
        @endif
        @if($errors->any())
            @foreach($errors->all() as $error)
                <div class="app-toast-card toast-danger" data-auto-dismiss="6500" role="alert">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>{{ $error }}</div>
                        <button type="button" class="app-toast-close" aria-label="Tutup">&times;</button>
                    </div>
                </div>
            @endforeach
        @endif
    </div>

    <!-- jQuery & Bootstrap -->
    <script src="{{ asset('assets/js/plugin/datatables/jquery.min.js') }}"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
    
    <!-- Datatables -->
    <script src="{{ asset('assets/js/plugin/datatables/datatables.min.js') }}"></script>
    <script src="{{ asset('assets/js/plugin/datatables/dataTables.bootstrap5.min.js') }}"></script>
    <script>
        if (typeof $.fn.dataTable !== 'undefined') {
            $.fn.dataTable.ext.errMode = 'none';
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
                dom: "<'row align-items-center mb-2'<'col-sm-12 col-md-6'l><'col-sm-12 col-md-6'f>><'table-responsive'tr><'row align-items-center mt-3'<'col-sm-12 col-md-5'i><'col-sm-12 col-md-7 d-flex justify-content-md-end'p>>",
                responsive: false,
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

            // ── Global Bottom-Right Stacked Alert & Confirm System ──
            const toastStack = document.getElementById('appToastStack');

            function dismissToastCard(card) {
                if (!card || card.dataset.dismissed === '1') return;
                card.dataset.dismissed = '1';
                card.classList.add('hiding');
                setTimeout(() => {
                    if (card.parentNode) card.parentNode.removeChild(card);
                }, 200);
            }

            function attachAutoDismiss(card, delayMs) {
                let timer = null;
                const closeBtn = card.querySelector('.app-toast-close');
                if (closeBtn) {
                    closeBtn.addEventListener('click', () => dismissToastCard(card));
                }
                if (delayMs > 0) {
                    const start = () => {
                        timer = setTimeout(() => dismissToastCard(card), delayMs);
                    };
                    const stop = () => {
                        if (timer) clearTimeout(timer);
                    };
                    card.addEventListener('mouseenter', stop);
                    card.addEventListener('mouseleave', start);
                    start();
                }
            }

            if (toastStack) {
                toastStack.querySelectorAll('.app-toast-card[data-auto-dismiss]').forEach(card => {
                    const ms = parseInt(card.getAttribute('data-auto-dismiss') || '4500', 10);
                    attachAutoDismiss(card, ms);
                });
            }

            window.showAppToast = function (message, type = 'info', duration = 4500) {
                if (!toastStack) return;
                const variant = ['success', 'warning', 'danger', 'info'].includes(type) ? type : (type === 'error' ? 'danger' : 'info');
                const card = document.createElement('div');
                card.className = `app-toast-card toast-${variant}`;
                card.setAttribute('role', 'alert');

                const row = document.createElement('div');
                row.className = 'd-flex justify-content-between align-items-start';

                const textDiv = document.createElement('div');
                textDiv.textContent = message;

                const closeBtn = document.createElement('button');
                closeBtn.type = 'button';
                closeBtn.className = 'app-toast-close';
                closeBtn.setAttribute('aria-label', 'Tutup');
                closeBtn.innerHTML = '&times;';

                row.appendChild(textDiv);
                row.appendChild(closeBtn);
                card.appendChild(row);

                toastStack.appendChild(card);
                attachAutoDismiss(card, duration);
            };

            window.alert = function (msg) {
                window.showAppToast(String(msg ?? ''), 'warning', 5000);
            };

            window.showAppConfirm = function (message, onConfirm, options = {}) {
                if (!toastStack) {
                    if (typeof onConfirm === 'function') onConfirm();
                    return;
                }

                const isDanger = Boolean(options.danger);
                const card = document.createElement('div');
                card.className = `app-toast-card ${isDanger ? 'toast-danger' : 'toast-info'}`;
                card.setAttribute('role', 'alertdialog');

                const titleEl = document.createElement('div');
                titleEl.className = 'fw-semibold text-secondary mb-1';
                titleEl.style.fontSize = '0.72rem';
                titleEl.style.textTransform = 'uppercase';
                titleEl.style.letterSpacing = '0.04em';
                titleEl.textContent = 'Konfirmasi';

                const msgEl = document.createElement('div');
                msgEl.className = 'mb-2 text-dark';
                msgEl.textContent = message;

                const actionsEl = document.createElement('div');
                actionsEl.className = 'd-flex justify-content-end gap-2 mt-2';

                const btnCancel = document.createElement('button');
                btnCancel.type = 'button';
                btnCancel.className = 'btn btn-sm btn-light border px-3 py-1';
                btnCancel.style.fontSize = '0.78rem';
                btnCancel.textContent = options.cancelText || 'Batal';

                const btnOk = document.createElement('button');
                btnOk.type = 'button';
                btnOk.className = `btn btn-sm ${isDanger ? 'btn-danger' : 'btn-primary'} px-3 py-1 fw-semibold`;
                btnOk.style.fontSize = '0.78rem';
                btnOk.textContent = options.confirmText || 'Ya, Lanjutkan';

                btnCancel.addEventListener('click', () => {
                    dismissToastCard(card);
                });

                btnOk.addEventListener('click', () => {
                    dismissToastCard(card);
                    if (typeof onConfirm === 'function') {
                        onConfirm();
                    }
                });

                actionsEl.appendChild(btnCancel);
                actionsEl.appendChild(btnOk);

                card.appendChild(titleEl);
                card.appendChild(msgEl);
                card.appendChild(actionsEl);

                toastStack.appendChild(card);
                btnOk.focus();
            };

            // Delegated listener for buttons with [data-confirm]
            document.addEventListener('click', function (e) {
                const btn = e.target.closest('button[data-confirm], a[data-confirm]');
                if (!btn) return;
                if (btn.dataset.confirmedOnce === '1') {
                    delete btn.dataset.confirmedOnce;
                    return;
                }
                e.preventDefault();
                e.stopPropagation();

                const msg = btn.getAttribute('data-confirm') || 'Apakah Anda yakin ingin melanjutkan?';
                const isDanger = btn.classList.contains('btn-danger') || btn.classList.contains('btn-outline-danger');

                window.showAppConfirm(msg, function () {
                    btn.dataset.confirmedOnce = '1';
                    btn.click();
                }, { danger: isDanger });
            }, true);

            // Delegated listener for forms with [data-confirm]
            document.addEventListener('submit', function (e) {
                const form = e.target.closest('form[data-confirm]');
                if (!form) return;
                if (form.dataset.confirmedOnce === '1') {
                    delete form.dataset.confirmedOnce;
                    return;
                }
                e.preventDefault();

                const submitter = e.submitter || form.querySelector('button[type="submit"]');
                const msg = form.getAttribute('data-confirm') || 'Apakah Anda yakin ingin melanjutkan?';
                const isDanger = submitter && (submitter.classList.contains('btn-danger') || submitter.classList.contains('btn-outline-danger'));

                window.showAppConfirm(msg, function () {
                    form.dataset.confirmedOnce = '1';
                    if (typeof form.requestSubmit === 'function') {
                        form.requestSubmit(submitter || undefined);
                    } else {
                        form.submit();
                    }
                }, { danger: Boolean(isDanger) });
            }, true);
        });
    </script>
    @stack('scripts')
</body>
</html>
