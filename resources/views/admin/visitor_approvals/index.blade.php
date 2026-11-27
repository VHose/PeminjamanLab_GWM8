@extends('layouts.app')

@section('content')
<div class="d-flex flex-wrap justify-content-between align-items-center mb-4">
    <div>
        <h1 class="h3 fw-bold mb-1">Persetujuan Akun Visitor</h1>
    </div>
</div>

<div class="row g-3 mb-4">
    <div class="col-6 col-md-3">
        <a href="{{ route('admin.visitor-approvals.index') }}" class="card text-decoration-none shadow-sm border-0 {{ !request('status') ? 'border-primary border-2 bg-light' : '' }}">
            <div class="card-body p-3">
                <div class="text-secondary small fw-semibold">Semua Visitor</div>
                <div class="fs-4 fw-bold text-dark">{{ $counts['all'] }}</div>
            </div>
        </a>
    </div>
    <div class="col-6 col-md-3">
        <a href="{{ route('admin.visitor-approvals.index', ['status' => 'pending']) }}" class="card text-decoration-none shadow-sm border-0 {{ request('status') === 'pending' ? 'border-warning border-2 bg-warning-subtle' : '' }}">
            <div class="card-body p-3">
                <div class="text-warning-emphasis small fw-semibold">Menunggu Persetujuan</div>
                <div class="fs-4 fw-bold text-warning-emphasis">{{ $counts['pending'] }}</div>
            </div>
        </a>
    </div>
    <div class="col-6 col-md-3">
        <a href="{{ route('admin.visitor-approvals.index', ['status' => 'active']) }}" class="card text-decoration-none shadow-sm border-0 {{ request('status') === 'active' ? 'border-success border-2 bg-success-subtle' : '' }}">
            <div class="card-body p-3">
                <div class="text-success small fw-semibold">Aktif / Disetujui</div>
                <div class="fs-4 fw-bold text-success">{{ $counts['active'] }}</div>
            </div>
        </a>
    </div>
    <div class="col-6 col-md-3">
        <a href="{{ route('admin.visitor-approvals.index', ['status' => 'rejected']) }}" class="card text-decoration-none shadow-sm border-0 {{ request('status') === 'rejected' ? 'border-danger border-2 bg-danger-subtle' : '' }}">
            <div class="card-body p-3">
                <div class="text-danger small fw-semibold">Ditolak</div>
                <div class="fs-4 fw-bold text-danger">{{ $counts['rejected'] }}</div>
            </div>
        </a>
    </div>
</div>

<div class="card shadow-sm border-0">
    <div class="card-header bg-white py-3">
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-2">
            <h2 class="h5 fw-bold mb-0">Daftar Pendaftar Akun Visitor</h2>
            <div class="d-flex gap-2">
                @if(request('status') || request('search'))
                    <a href="{{ route('admin.visitor-approvals.index') }}" class="btn btn-sm btn-outline-secondary">Reset Filter</a>
                @endif
            </div>
        </div>
    </div>
    <div class="card-body p-3">
        <div class="table-responsive">
            <table class="table table-hover align-middle text-nowrap mb-0" id="visitorApprovalsTable">
                <thead class="table-light">
                    <tr>
                        <th class="text-start" style="width: 80px;">ID</th>
                        <th class="text-start">Nama Pendaftar</th>
                        <th class="text-start">Instansi / Organisasi</th>
                        <th class="text-start">Kontak</th>
                        <th class="text-center">Status Email</th>
                        <th class="text-center">Status Akun</th>
                        <th class="text-start">Waktu Daftar</th>
                        <th class="text-end" style="width: 170px;">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($users as $user)
                        <tr>
                            <td class="fw-bold">{{ $user->id }}</td>
                            <td>
                                <div class="fw-semibold text-dark">{{ $user->name }}</div>
                                <div class="small text-secondary">{{ $user->email }}</div>
                            </td>
                            <td>
                                <span class="badge text-bg-light border text-dark">{{ $user->pic_or_instansi ?? '-' }}</span>
                            </td>
                            <td>
                                <div><i class="bi bi-telephone text-secondary me-1"></i>{{ $user->phone ?? '-' }}</div>
                            </td>
                            <td class="text-center">
                                @if($user->email_verified_at)
                                    <span class="badge text-bg-success" title="Diverifikasi pada {{ $user->email_verified_at->format('d/m/Y H:i') }}">
                                        Verified
                                    </span>
                                @else
                                    <span class="badge text-bg-secondary">
                                        Unverified
                                    </span>
                                @endif
                            </td>
                            <td class="text-center">
                                @if($user->status === 'pending')
                                    <span class="badge text-bg-warning">Menunggu Persetujuan</span>
                                @elseif($user->status === 'active')
                                    <span class="badge text-bg-success">Aktif</span>
                                @elseif($user->status === 'rejected')
                                    <span class="badge text-bg-danger">Ditolak</span>
                                @else
                                    <span class="badge text-bg-secondary">{{ ucfirst($user->status) }}</span>
                                @endif
                            </td>
                            <td class="small text-secondary">
                                {{ $user->created_at ? $user->created_at->format('d/m/Y H:i') : '-' }}
                            </td>
                            <td class="text-end">
                                <div class="d-flex justify-content-end gap-1">
                                    @if($user->status !== 'active')
                                        <form method="post" action="{{ route('admin.visitor-approvals.approve', $user) }}" onsubmit="return confirm('Setujui akun visitor {{ $user->name }}? User akan dapat mengajukan peminjaman lab.')">
                                            @csrf
                                            <button type="submit" class="btn btn-sm btn-success" title="Setujui Akun">
                                                Setujui
                                            </button>
                                        </form>
                                    @endif

                                    @if($user->status !== 'rejected')
                                        <button type="button" class="btn btn-sm btn-outline-danger" data-bs-toggle="modal" data-bs-target="#rejectModal{{ $user->id }}" title="Tolak Akun">
                                            Tolak
                                        </button>
                                    @endif
                                </div>

                                <!-- Modal Penolakan -->
                                <div class="modal fade" id="rejectModal{{ $user->id }}" tabindex="-1" aria-hidden="true">
                                    <div class="modal-dialog modal-dialog-centered text-start">
                                        <div class="modal-content">
                                            <form method="post" action="{{ route('admin.visitor-approvals.reject', $user) }}">
                                                @csrf
                                                <div class="modal-header">
                                                    <h5 class="modal-title fw-bold">Tolak Akun Visitor</h5>
                                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                </div>
                                                <div class="modal-body">
                                                    <p class="mb-2">Anda akan menolak pendaftaran akun berikut:</p>
                                                    <div class="p-2 rounded bg-light mb-3">
                                                        <div class="fw-bold">{{ $user->name }} ({{ $user->id }})</div>
                                                        <div class="small text-secondary">{{ $user->email }} | {{ $user->pic_or_instansi ?? 'Tidak ada instansi' }}</div>
                                                    </div>
                                                    <div class="mb-3">
                                                        <label class="form-label fw-semibold">Alasan Penolakan (Opsional)</label>
                                                        <textarea class="form-control" name="reason" rows="2" placeholder="Contoh: Instansi tidak valid atau kontak tidak dapat dihubungi..."></textarea>
                                                    </div>
                                                </div>
                                                <div class="modal-footer">
                                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                                                    <button type="submit" class="btn btn-danger">Tolak Akun</button>
                                                </div>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function () {
        if (typeof jQuery !== 'undefined' && typeof jQuery.fn.DataTable !== 'undefined') {
            $('#visitorApprovalsTable').DataTable({
                responsive: true,
                order: [],
                language: {
                    search: "Cari Pendaftar:",
                    lengthMenu: "Tampilkan _MENU_ data per halaman",
                    zeroRecords: "Tidak ada data pendaftar yang cocok.",
                    info: "Menampilkan halaman _PAGE_ dari _PAGES_ (Total _TOTAL_ pendaftar)",
                    infoEmpty: "Tidak ada data tersedia",
                    infoFiltered: "(disaring dari _MAX_ total pendaftar)",
                    paginate: {
                        first: "Awal",
                        last: "Akhir",
                        next: "Berikutnya",
                        previous: "Sebelumnya"
                    }
                }
            });
        }
    });
</script>
@endpush
@endsection
