@extends('layouts.app')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h1 class="h3 fw-bold mb-1">Pengaturan Sistem & Laboratorium</h1>
        <p class="text-secondary mb-0">Kelola aturan peminjaman, jam operasional, slot jam perkuliahan, dan periode semester.</p>
    </div>
</div>

<div class="card shadow-sm border-0 mb-4">
    <div class="card-header bg-white border-bottom-0 pt-3">
        <ul class="nav nav-tabs card-header-tabs" role="tablist">
            <li class="nav-item">
                <a class="nav-link {{ $activeTab === 'general' ? 'active fw-bold' : 'text-secondary' }}" data-bs-toggle="tab" href="#tab-general" role="tab">
                    <span class="d-inline-flex align-items-center gap-1">
                        Aturan Booking & Jam Operasional
                    </span>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link {{ $activeTab === 'slots' ? 'active fw-bold' : 'text-secondary' }}" data-bs-toggle="tab" href="#tab-slots" role="tab">
                    <span class="d-inline-flex align-items-center gap-1">
                        Slot Jam Kelas (Dropdown Jadwal)
                        <span class="badge text-bg-secondary">{{ $classSlots->count() }}</span>
                    </span>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link {{ $activeTab === 'periods' ? 'active fw-bold' : 'text-secondary' }}" data-bs-toggle="tab" href="#tab-periods" role="tab">
                    <span class="d-inline-flex align-items-center gap-1">
                        Periode Semester & Kalender
                        <span class="badge text-bg-secondary">{{ $periods->count() }}</span>
                    </span>
                </a>
            </li>
        </ul>
    </div>
    <div class="card-body p-4">
        <div class="tab-content">
            <!-- TAB 1: PENGATURAN UMUM / ATURAN BOOKING -->
            <div class="tab-pane fade {{ $activeTab === 'general' ? 'show active' : '' }}" id="tab-general" role="tabpanel">
                <form method="post" action="{{ route('admin.settings.update') }}">
                    @csrf
                    <div class="row g-4">
                        <div class="col-lg-6">
                            <div class="p-3 bg-light rounded border h-100">
                                <h2 class="h6 fw-bold text-primary mb-3">1. Jam Operasional Laboratorium</h2>
                                <p class="text-secondary small mb-3">Tentukan batas jam buka dan tutup lab. Peminjaman di luar jam ini tidak dapat diajukan.</p>

                                <div class="mb-3">
                                    <label class="form-label fw-semibold">Senin s/d Jumat: Jam Buka & Tutup <span class="text-danger">*</span></label>
                                    <div class="input-group">
                                        <span class="input-group-text">Buka</span>
                                        <input type="time" name="lab_open_time" class="form-control" value="{{ old('lab_open_time', $settings['lab_open_time'] ?? '') }}" required>
                                        <span class="input-group-text">Tutup</span>
                                        <input type="time" name="lab_close_time" class="form-control" value="{{ old('lab_close_time', $settings['lab_close_time'] ?? '') }}" required>
                                    </div>
                                    <div class="form-text">Contoh: 07:00 sampai 21:00.</div>
                                </div>

                                <div class="mb-3">
                                    <label class="form-label fw-semibold">Hari Sabtu: Jam Operasional Khusus (Opsional)</label>
                                    <div class="input-group">
                                        <span class="input-group-text">Buka</span>
                                        <input type="time" name="lab_open_time_saturday" class="form-control" value="{{ old('lab_open_time_saturday', $settings['lab_open_time_saturday'] ?? '') }}">
                                        <span class="input-group-text">Tutup</span>
                                        <input type="time" name="lab_close_time_saturday" class="form-control" value="{{ old('lab_close_time_saturday', $settings['lab_close_time_saturday'] ?? '') }}">
                                    </div>
                                    <div class="form-text">Kosongkan jika operasional hari Sabtu mengikuti jam Senin - Jumat.</div>
                                </div>
                            </div>
                        </div>

                        <div class="col-lg-6">
                            <div class="p-3 bg-light rounded border h-100">
                                <h2 class="h6 fw-bold text-primary mb-3">2. Aturan Blok Waktu & Batas Booking</h2>
                                <p class="text-secondary small mb-3">Parameter ini menentukan pembulatan pemblokiran waktu kelas dan batasan bagi pemohon.</p>

                                <div class="mb-3">
                                    <label class="form-label fw-semibold">Blok Menit Peminjaman (booking_block_minutes) <span class="text-danger">*</span></label>
                                    <select name="booking_block_minutes" class="form-select" required>
                                        <option value="30" @selected(old('booking_block_minutes', $settings['booking_block_minutes'] ?? '30') == '30')>30 Menit (:00 atau :30 - Default)</option>
                                        <option value="15" @selected(old('booking_block_minutes', $settings['booking_block_minutes'] ?? '') == '15')>15 Menit</option>
                                        <option value="60" @selected(old('booking_block_minutes', $settings['booking_block_minutes'] ?? '') == '60')>60 Menit</option>
                                    </select>
                                    <div class="form-text">Kelas yang selesai pada menit ganjil (misal 08:40) akan memblokir ruangan ke atas hingga kelipatan blok berikutnya (09:00).</div>
                                </div>

                                <div class="mb-3">
                                    <label class="form-label fw-semibold">Maksimal Pemesanan ke Depan (booking_max_days_ahead) <span class="text-danger">*</span></label>
                                    <div class="input-group">
                                        <input type="number" name="booking_max_days_ahead" class="form-control" min="1" max="180" value="{{ old('booking_max_days_ahead', $settings['booking_max_days_ahead'] ?? '') }}" required placeholder="Contoh: 30">
                                        <span class="input-group-text">Hari ke depan</span>
                                    </div>
                                    <div class="form-text">Pemohon tidak dapat mengajukan peminjaman melebihi rentang hari ini.</div>
                                </div>

                                <div class="mb-3">
                                    <label class="form-label fw-semibold">Maksimal Durasi per Peminjaman (booking_max_duration_hours) <span class="text-danger">*</span></label>
                                    <div class="input-group">
                                        <input type="number" name="booking_max_duration_hours" class="form-control" min="1" max="12" value="{{ old('booking_max_duration_hours', $settings['booking_max_duration_hours'] ?? '') }}" required placeholder="Contoh: 4">
                                        <span class="input-group-text">Jam per sesi</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="mt-4 pt-3 border-top text-end">
                        <button type="submit" class="btn btn-primary px-4">
                            Simpan Pengaturan Sistem
                        </button>
                    </div>
                </form>
            </div>

            <!-- TAB 2: SLOT JAM KELAS -->
            <div class="tab-pane fade {{ $activeTab === 'slots' ? 'show active' : '' }}" id="tab-slots" role="tabpanel">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <div>
                        <h2 class="h6 fw-bold mb-1">Daftar Slot Jam Perkuliahan (Referensi Dropdown Input Jadwal)</h2>
                        <p class="text-secondary small mb-0">Disiapkan dari pola jadwal akademik (Teori 2 SKS 100 menit, Teori 3 SKS 150 menit, Praktikum 120 menit). Jadwal tetap menyimpan jam aslinya.</p>
                    </div>
                    <button type="button" class="btn btn-sm btn-primary" data-bs-toggle="collapse" data-bs-target="#addClassSlotForm">
                        + Tambah Slot Jam
                    </button>
                </div>

                <div class="collapse mb-4" id="addClassSlotForm">
                    <div class="card card-body bg-light border">
                        <h3 class="h6 fw-bold mb-3">Tambah Slot Jam Baru</h3>
                        <form method="post" action="{{ route('admin.class-slots.store') }}">
                            @csrf
                            <div class="row g-3">
                                <div class="col-md-3">
                                    <label class="form-label fw-semibold">Kode Slot <span class="text-danger">*</span></label>
                                    <input type="text" name="code" class="form-control" placeholder="Contoh: T2-1, P2-1" required maxlength="10">
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label fw-semibold">Jam Mulai <span class="text-danger">*</span></label>
                                    <input type="time" name="start_time" class="form-control" required>
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label fw-semibold">Jam Selesai <span class="text-danger">*</span></label>
                                    <input type="time" name="end_time" class="form-control" required>
                                </div>
                                <div class="col-md-3 d-flex align-items-end">
                                    <button type="submit" class="btn btn-success w-100">Simpan Slot</button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>

                <div class="table-responsive">
                    <table class="table table-hover align-middle border">
                        <thead class="table-light">
                            <tr>
                                <th>Kode Slot</th>
                                <th>Jam Mulai</th>
                                <th>Jam Selesai</th>
                                <th>Durasi</th>
                                <th>Status</th>
                                <th class="text-end">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($classSlots as $slot)
                                @php
                                    $s = \Carbon\Carbon::parse($slot->start_time);
                                    $e = \Carbon\Carbon::parse($slot->end_time);
                                    $diffMin = $e->diffInMinutes($s);
                                @endphp
                                <tr>
                                    <td class="fw-bold text-primary">{{ $slot->code }}</td>
                                    <td>{{ substr($slot->start_time, 0, 5) }}</td>
                                    <td>{{ substr($slot->end_time, 0, 5) }}</td>
                                    <td><span class="badge text-bg-light border">{{ $diffMin }} menit</span></td>
                                    <td>
                                        @if($slot->is_active)
                                            <span class="badge bg-success">Aktif</span>
                                        @else
                                            <span class="badge bg-secondary">Nonaktif</span>
                                        @endif
                                    </td>
                                    <td class="text-end">
                                        <form method="post" action="{{ route('admin.class-slots.toggle', $slot) }}" class="d-inline">
                                            @csrf
                                            <button type="submit" class="btn btn-sm {{ $slot->is_active ? 'btn-outline-warning' : 'btn-outline-success' }}">
                                                {{ $slot->is_active ? 'Nonaktifkan' : 'Aktifkan' }}
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="text-center text-muted py-4">Belum ada slot jam kelas yang didaftarkan.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- TAB 3: PERIODE SEMESTER & KALENDER -->
            <div class="tab-pane fade {{ $activeTab === 'periods' ? 'show active' : '' }}" id="tab-periods" role="tabpanel">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <div>
                        <h2 class="h6 fw-bold mb-1">Daftar Periode Semester Akademik</h2>
                        <p class="text-secondary small mb-0">Hanya satu periode yang boleh aktif dalam satu waktu. Tanggal mulai periode wajib hari Senin.</p>
                    </div>
                    <a href="{{ route('admin.periods.create') }}" class="btn btn-sm btn-primary">
                        + Tambah Periode
                    </a>
                </div>

                <div class="table-responsive">
                    <table class="table table-hover align-middle border">
                        <thead class="table-light">
                            <tr>
                                <th>Nama Periode</th>
                                <th>Rentang Kuliah (start - end)</th>
                                <th>Rentang UTS</th>
                                <th>Rentang UAS</th>
                                <th>Hari Libur</th>
                                <th>Jadwal Kuliah</th>
                                <th>Status</th>
                                <th class="text-end">Aksi & Regenerate</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($periods as $p)
                                <tr class="{{ $p->is_active ? 'table-success bg-opacity-10' : '' }}">
                                    <td>
                                        <span class="fw-bold">{{ $p->name }}</span>
                                        @if($p->is_active)
                                            <span class="badge bg-success ms-1">Aktif</span>
                                        @endif
                                    </td>
                                    <td>
                                        {{ $p->start_date->format('d/m/Y') }} - {{ $p->end_date->format('d/m/Y') }}
                                        <div class="small text-secondary">{{ $p->start_date->translatedFormat('l') }}</div>
                                    </td>
                                    <td>
                                        @if($p->uts_start && $p->uts_end)
                                            {{ $p->uts_start->format('d/m/Y') }} - {{ $p->uts_end->format('d/m/Y') }}
                                        @else
                                            <span class="text-muted">-</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if($p->uas_start && $p->uas_end)
                                            {{ $p->uas_start->format('d/m/Y') }} - {{ $p->uas_end->format('d/m/Y') }}
                                        @else
                                            <span class="text-muted">-</span>
                                        @endif
                                    </td>
                                    <td>
                                        <a href="{{ route('admin.holidays.index', $p) }}" class="btn btn-sm btn-outline-info">
                                            Libur ({{ $p->holidays_count }})
                                        </a>
                                    </td>
                                    <td>
                                        <span class="badge text-bg-light border">{{ $p->schedules_count }} jadwal</span>
                                    </td>
                                    <td>
                                        <form method="post" action="{{ route('admin.periods.toggle', $p) }}" class="d-inline">
                                            @csrf
                                            <button type="submit" class="btn btn-sm {{ $p->is_active ? 'btn-success' : 'btn-outline-secondary' }}" title="Klik untuk beralih status">
                                                {{ $p->is_active ? 'Aktif' : 'Nonaktif' }}
                                            </button>
                                        </form>
                                    </td>
                                    <td class="text-end">
                                        <div class="btn-group">
                                            <form method="post" action="{{ route('admin.periods.regenerate', $p) }}" class="d-inline" onsubmit="return confirm('Regenerate sesi pertemuan untuk periode ini? Sesi manual yang sudah ada tidak akan ditimpa.')">
                                                @csrf
                                                <button type="submit" class="btn btn-sm btn-outline-primary" title="Generate atau regenerate schedule_session">
                                                    Regenerate Sesi
                                                </button>
                                            </form>
                                            <a href="{{ route('admin.periods.edit', $p) }}" class="btn btn-sm btn-outline-secondary">
                                                Ubah
                                            </a>
                                            @if($p->is_active)
                                                <form method="post" action="{{ route('admin.periods.destroy', $p) }}" class="d-inline" onsubmit="return confirm('Nonaktifkan periode semester ini?')">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="btn btn-sm btn-outline-danger">
                                                        Nonaktifkan
                                                    </button>
                                                </form>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="8" class="text-center text-muted py-4">Belum ada periode semester yang terdaftar.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
