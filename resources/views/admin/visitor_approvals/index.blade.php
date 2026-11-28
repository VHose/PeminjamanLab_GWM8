@extends('layouts.app')

@section('content')
<div class="d-flex flex-wrap justify-content-between align-items-center mb-4">
    <div>
        <h1 class="h3 fw-bold mb-1">Persetujuan Akun Visitor</h1>
    </div>
</div>

<div class="card shadow-sm border-0">
    <div class="card-header bg-white py-3">
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-2">
            <h2 class="h5 fw-bold mb-0">Daftar Pendaftar Akun Visitor</h2>
            @if(request('status') || request('search'))
                <div>
                    <a href="{{ route('admin.visitor-approvals.index') }}" class="btn btn-sm btn-outline-secondary">Tampilkan Semua</a>
                </div>
            @endif
        </div>
    </div>
    <div class="card-body p-3">
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
                    <th class="text-end" style="width: 120px;">Aksi</th>
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
                            {{ $user->pic_or_instansi ?: '-' }}
                        </td>
                        <td>
                            <div>{{ $user->phone ?: '-' }}</div>
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
                            <div class="d-inline-flex justify-content-end gap-1">
                                @if($user->status !== 'active')
                                    <form method="post" action="{{ route('admin.visitor-approvals.approve', $user) }}" class="d-inline m-0" data-confirm="Setujui akun visitor {{ $user->name }}? User akan dapat mengajukan peminjaman lab.">
                                        @csrf
                                        <button type="submit" class="btn btn-sm btn-outline-success" title="Setujui Akun" aria-label="Setujui Akun">
                                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 16 16" aria-hidden="true">
                                                <path d="M12.736 3.97a.733.733 0 0 1 1.047 0c.286.289.29.756.01 1.05L7.88 12.01a.733.733 0 0 1-1.065.02L3.217 8.384a.757.757 0 0 1 0-1.06.733.733 0 0 1 1.047 0l3.052 3.093 5.4-6.425z"/>
                                            </svg>
                                        </button>
                                    </form>
                                @endif

                                @if($user->status !== 'rejected')
                                    <button type="button" class="btn btn-sm btn-outline-danger" data-bs-toggle="modal" data-bs-target="#rejectModal{{ $user->id }}" title="Tolak Akun" aria-label="Tolak Akun">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 16 16" aria-hidden="true">
                                            <path d="M2.146 2.854a.5.5 0 1 1 .708-.708L8 7.293l5.146-5.147a.5.5 0 0 1 .708.708L8.707 8l5.147 5.146a.5.5 0 0 1-.708.708L8 8.707l-5.146 5.147a.5.5 0 0 1-.708-.708L7.293 8z"/>
                                        </svg>
                                    </button>
                                @endif
                            </div>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>

@foreach($users as $user)
    @if($user->status !== 'rejected')
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
    @endif
@endforeach

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function () {
        if (typeof jQuery !== 'undefined' && typeof jQuery.fn.DataTable !== 'undefined') {
            if ($.fn.DataTable.isDataTable('#visitorApprovalsTable')) {
                $('#visitorApprovalsTable').DataTable().destroy();
            }
            $('#visitorApprovalsTable').DataTable({
                order: [],
                columnDefs: [
                    { orderable: false, targets: [2, 3, 7] },
                    { searchable: false, targets: [2, 3, 4, 5, 6, 7] }
                ],
                language: {
                    search: "Cari Pendaftar:",
                    lengthMenu: "Tampilkan _MENU_ data per halaman",
                    zeroRecords: "Tidak ada data pendaftar yang cocok.",
                    info: "Menampilkan halaman _PAGE_ dari _PAGES_ (Total _TOTAL_ pendaftar)",
                    infoEmpty: "Tidak ada data tersedia",
                    infoFiltered: "",
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
