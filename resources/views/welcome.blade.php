@extends('layouts.app')

@section('content')
<div class="row align-items-center mb-4">
    <div class="col-md-8">
        <h1 class="h3 fw-bold mb-1">Jadwal Penggunaan Ruangan Lab GWM Lantai 8</h1>
        <p class="text-secondary mb-0">
            Tanggal: <span class="fw-semibold text-dark" id="headerScheduleDate">{{ $date->translatedFormat('l, d F Y') }}</span>
            <span class="badge bg-danger ms-1 {{ ($selectedDayInfo && $selectedDayInfo['is_holiday']) ? '' : 'd-none' }}" id="headerHolidayBadge">
                Libur: {{ $selectedDayInfo['holiday_note'] ?? 'Hari Libur' }}
            </span>
            <span id="headerPeriodWrapper" class="{{ $period ? '' : 'd-none' }}">
                | Periode: <span id="headerPeriodName">{{ $period?->name }}</span>
            </span>
        </p>
    </div>
    <div class="col-md-4 text-md-end mt-3 mt-md-0">
        @auth
            @if(!auth()->user()->isInternal() && !auth()->user()->hasVerifiedEmail())
                <a class="btn btn-warning" href="{{ route('verification.notice') }}">Verifikasi Email</a>
            @elseif(!auth()->user()->isInternal() && (auth()->user()->status !== 'active' || !auth()->user()->is_active))
                <button class="btn btn-secondary" disabled title="Menunggu persetujuan akun dari Kepala Lab / Staf Lab">Menunggu Persetujuan</button>
            @else
                <a class="btn btn-primary" href="{{ route('bookings.create') }}">Ajukan Peminjaman Ruangan</a>
            @endif
        @else
            <a class="btn btn-primary" href="{{ route('login') }}">Masuk untuk Meminjam</a>
        @endauth
    </div>
</div>

@auth
    @if(!auth()->user()->isInternal())
        @if(!auth()->user()->hasVerifiedEmail())
            <div class="alert alert-warning d-flex align-items-center justify-content-between mb-4 shadow-sm" role="alert">
                <div><strong>Email Belum Terverifikasi:</strong> Silakan verifikasi alamat email Anda agar dapat mengajukan peminjaman laboratorium.</div>
                <a href="{{ route('verification.notice') }}" class="btn btn-sm btn-warning text-dark fw-semibold text-nowrap ms-3">Verifikasi Sekarang</a>
            </div>
        @elseif(auth()->user()->status === 'pending' || !auth()->user()->is_active)
            <div class="alert alert-secondary d-flex align-items-center mb-4 shadow-sm" role="alert">
                <div><strong>Pendaftaran Menunggu Persetujuan:</strong> Akun Anda telah terverifikasi email dan sedang menunggu persetujuan Kepala Lab / Staf Lab.</div>
            </div>
        @elseif(auth()->user()->status === 'rejected')
            <div class="alert alert-danger d-flex align-items-center mb-4 shadow-sm" role="alert">
                <div><strong>Akun Tidak Disetujui:</strong> Pendaftaran akun visitor Anda tidak disetujui oleh pengelola laboratorium.</div>
            </div>
        @endif
    @endif

    @if($pendingVisitorCount > 0)
        <div class="alert alert-warning d-flex justify-content-between align-items-center mb-4 shadow-sm">
            <div><strong>Pemberitahuan Pendaftar Baru:</strong> Terdapat <strong>{{ $pendingVisitorCount }} akun visitor baru</strong> yang menunggu persetujuan Anda.</div>
            <a href="{{ route('admin.visitor-approvals.index', ['status' => 'pending']) }}" class="btn btn-sm btn-warning text-dark fw-bold text-nowrap ms-3">Tinjau & Setujui</a>
        </div>
    @endif
@endauth

<div class="card mb-4 shadow-sm border-0">
    <div class="card-header bg-white py-3">
        <h2 class="h5 mb-0 fw-bold">Denah Laboratorium Komputer GWM Lantai 8</h2>
    </div>
    <div class="card-body p-3 text-center bg-light">
        <div class="shadow-sm rounded border bg-white p-2 d-inline-block" style="max-width: 960px; width: 100%;">
            <img src="{{ asset('images/denah-lantai-8.png') }}" alt="Denah Laboratorium Komputer GWM Lantai 8" class="img-fluid rounded" style="width: 100%; height: auto;">
        </div>
    </div>
</div>

<div class="card mb-4 shadow-sm border-0">
    <div class="card-body p-3">
        <div class="row g-3 align-items-end">
            <div class="col-md-3">
                <label class="form-label fw-semibold" for="schedulePeriodFilter">1. Periode</label>
                <select name="period_id" id="schedulePeriodFilter" class="form-select">
                    @foreach($allPeriods as $p)
                        <option value="{{ $p->id }}" @selected($period && $period->id == $p->id)>
                            {{ $p->name }} {{ $p->is_active ? '(Aktif)' : '' }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-4">
                <label class="form-label fw-semibold" for="scheduleWeekFilter">2. Minggu Pertemuan / Kalender</label>
                <select name="week" id="scheduleWeekFilter" class="form-select">
                    @foreach($calendar as $w)
                        <option value="{{ $w['week_index'] }}" @selected($weekIndex == $w['week_index'])>{{ $w['label'] }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label fw-semibold" for="scheduleDayFilter">3. Hari</label>
                <select name="day" id="scheduleDayFilter" class="form-select">
                    @foreach(['Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'] as $index => $dayName)
                        <option value="{{ $index + 1 }}" @selected($day == $index + 1)>{{ $dayName }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label fw-semibold" for="scheduleRoomFilter">4. Laboratorium</label>
                <select name="room_id" id="scheduleRoomFilter" class="form-select">
                    <option value="">Semua Lab ({{ $allRooms->count() }} Ruangan)</option>
                    @foreach($allRooms as $r)
                        <option value="{{ $r->id }}" @selected($selectedRoomId == $r->id)>{{ $r->labelWithCapacity() }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-1 d-flex align-items-end">
                <button type="button" id="btnResetScheduleFilter" class="btn btn-outline-secondary w-100 d-none" title="Reset ke hari/minggu aktif">Reset</button>
            </div>
        </div>
    </div>
</div>

<div class="card mb-4 shadow-sm border-0">
    <div class="card-body py-2">
        <div class="d-flex flex-wrap align-items-center gap-3 small">
            <span class="fw-bold">Keterangan:</span>
            @foreach($studyPrograms as $sp)
                <span class="d-inline-flex align-items-center gap-1">
                    <span class="d-inline-block border" style="width: 18px; height: 18px; background-color: {{ $sp->color_hex }};"></span>
                    <span>{{ $sp->name }}</span>
                </span>
            @endforeach
            <span class="d-inline-flex align-items-center gap-1">
                <span class="d-inline-block border" style="width: 18px; height: 18px; background-color: #BFE3F7;"></span>
                <span>Peminjaman / Booking</span>
            </span>
            @if($canSeeInactiveSessions)
                <span class="d-inline-flex align-items-center gap-1">
                    <span class="d-inline-block border" style="width: 18px; height: 18px; background-color: #f8f9fa; border: 1px dashed #adb5bd !important;"></span>
                    <span>Kuliah Libur / Nonaktif</span>
                </span>
            @endif
            <span class="d-inline-flex align-items-center gap-1">
                <span class="d-inline-block border bg-white" style="width: 18px; height: 18px;"></span>
                <span>Kosong (Dapat Dipinjam)</span>
            </span>
        </div>
    </div>
</div>

<svg xmlns="http://www.w3.org/2000/svg" class="d-none" aria-hidden="true">
    <symbol id="icon-pencil-square" viewBox="0 0 16 16">
        <path d="M15.502 1.94a.5.5 0 0 1 0 .706L14.459 3.69l-2-2L13.502.646a.5.5 0 0 1 .707 0l1.293 1.293zm-1.75 2.456-2-2L4.939 9.21a.5.5 0 0 0-.121.196l-.805 2.414a.25.25 0 0 0 .316.316l2.414-.805a.5.5 0 0 0 .196-.12l6.813-6.814z"/>
        <path fill-rule="evenodd" d="M1 13.5A1.5 1.5 0 0 0 2.5 15h11a1.5 1.5 0 0 0 1.5-1.5v-6a.5.5 0 0 0-1 0v6a.5.5 0 0 1-.5.5h-11a.5.5 0 0 1-.5-.5v-11a.5.5 0 0 1 .5-.5H9a.5.5 0 0 0 0-1H2.5A1.5 1.5 0 0 0 1 2.5v11z"/>
    </symbol>
</svg>

<div class="card shadow-sm border mb-5 schedule-table-card">
    <div class="card-header bg-white py-3 px-4 border-bottom">
        <h2 class="h5 mb-0 fw-bold">Jadwal Penggunaan Laboratorium</h2>
    </div>
    <div class="card-body p-3 p-md-4 bg-light bg-opacity-25" id="scheduleTableWrapper">
        <table id="scheduleGridTable" class="table table-bordered align-middle text-center schedule-grid-table">
            <thead class="table-light" id="scheduleGridThead">
                <tr>
                    <th class="col-jam fw-bold text-secondary text-center" style="font-size: 0.78rem;">Jam</th>
                    @foreach($rooms as $room)
                        <th class="col-room fw-bold text-dark text-center" style="font-size: 0.78rem;">{{ $room->labelWithCapacity() }}</th>
                    @endforeach
                </tr>
            </thead>
            <tbody id="scheduleGridTbody">
                @php
                    $csrfField = csrf_field();
                    $renderSessionControls = function (array $sess, string $wrapperCls) use ($csrfField): string {
                        if (empty($sess['can_manage'])) return '';
                        $checked = !empty($sess['is_active']) ? 'checked' : '';
                        $tip = !empty($sess['is_active']) ? 'Klik untuk meliburkan' : 'Klik untuk mengaktifkan kembali';
                        $sId = (int) $sess['schedule_id'];
                        $wNo = (int) $sess['week_no'];
                        $url = e($sess['toggle_url']);
                        return '<div class="' . $wrapperCls . ' d-flex justify-content-center align-items-center gap-2">'
                            . '<form method="POST" action="' . $url . '" class="d-inline-block m-0 session-toggle-form" data-schedule-id="' . $sId . '" data-week-no="' . $wNo . '">'
                            . $csrfField
                            . '<div class="form-check form-switch m-0 p-0" title="' . $tip . '">'
                            . '<input class="form-check-input m-0 session-toggle-input" type="checkbox" role="switch" id="toggle-' . $sId . '-' . $wNo . '" ' . $checked . '>'
                            . '</div></form>'
                            . '<button type="button" class="btn btn-sm btn-light border py-0 px-1 text-secondary" style="font-size: 0.68rem; line-height: 1.2;" title="Pindah Jadwal / Ruangan / Waktu" data-bs-toggle="modal" data-bs-target="#overrideSessionModal" data-schedule-id="' . $sId . '" data-week-no="' . $wNo . '">'
                            . '<svg width="11" height="11" fill="currentColor"><use href="#icon-pencil-square"/></svg></button></div>';
                    };
                @endphp
                @foreach($initialGridRows as $row)
                    <tr>
                        <td class="col-jam table-light fw-bold text-secondary py-2" style="font-size: 0.78rem; white-space: nowrap;">{{ $row['label'] }}</td>
                        @foreach($row['cells'] as $cell)
                            @if(!$cell['render'])
                                <td class="d-none spanned-cell"></td>
                            @elseif($cell['type'] === 'active_session')
                                @php $s = $cell['item']; @endphp
                                <td rowspan="{{ $cell['rowspan'] }}" class="col-room p-2 text-center" style="background-color: {{ $s['color_hex'] }}; vertical-align: middle;">
                                    <div class="fw-bold" style="font-size: 0.82rem; line-height: 1.25;">{{ $s['course_name'] }}</div>
                                    <div class="small text-secondary mt-1" style="font-size: 0.70rem;">
                                        {{ $s['start'] }} - {{ $s['end'] }}
                                        @if($s['class_code']) <span class="badge text-bg-light border">{{ $s['class_code'] }}</span> @endif
                                    </div>
                                    @if($s['lecturer_name']) <div class="small text-dark mt-1" style="font-size: 0.70rem;">{{ $s['lecturer_name'] }}</div> @endif
                                    @if($s['note']) <div class="small text-secondary fw-semibold mt-1" style="font-size: 0.70rem;">{{ $s['note'] }}</div> @endif
                                    {!! $renderSessionControls($s, 'mt-2 pt-1 border-top border-dark border-opacity-10') !!}
                                </td>
                            @elseif($cell['type'] === 'booking')
                                @php $bk = $cell['item']; $inact = $cell['inactive']; @endphp
                                <td rowspan="{{ $cell['rowspan'] }}" class="col-room p-2 text-center" style="background-color: #BFE3F7; vertical-align: middle;">
                                    @if($bk['is_pending'])
                                        <div class="fw-bold text-primary" style="font-size: 0.82rem;">Pending</div>
                                        <div class="small text-secondary" style="font-size: 0.70rem;">{{ $bk['start'] }} - {{ $bk['end'] }}</div>
                                        <div class="small text-dark fw-semibold mt-1" style="font-size: 0.72rem;">{{ $bk['display_requester'] }}</div>
                                    @else
                                        <div class="fw-bold text-dark" style="font-size: 0.82rem;">{{ $bk['display_requester'] }}</div>
                                        <div class="small text-secondary" style="font-size: 0.70rem;">{{ $bk['start'] }} - {{ $bk['end'] }}</div>
                                        @if($bk['purpose_short']) <div class="small text-muted mt-1" style="font-size: 0.70rem;">{{ $bk['purpose_short'] }}</div> @endif
                                    @endif
                                    @if($inact)
                                        <div class="mt-1 pt-1 border-top border-primary border-opacity-25">
                                            <div class="small text-muted" style="font-size: 0.65rem;">{{ $inact['course_name'] }}</div>
                                            <div class="small text-danger fw-semibold" style="font-size: 0.65rem;">Diliburkan</div>
                                            {!! $renderSessionControls($inact, 'mt-1') !!}
                                        </div>
                                    @endif
                                </td>
                            @elseif($cell['type'] === 'inactive_session')
                                @php $s = $cell['item']; @endphp
                                <td rowspan="{{ $cell['rowspan'] }}" class="col-room p-2 text-center" style="background-color: #f8f9fa; border: 1px dashed #adb5bd; vertical-align: middle;">
                                    <div class="fw-semibold text-secondary" style="font-size: 0.82rem; line-height: 1.25;">{{ $s['course_name'] }}</div>
                                    <div class="small text-muted mt-1" style="font-size: 0.70rem;">
                                        {{ $s['start'] }} - {{ $s['end'] }}
                                        @if($s['class_code']) <span class="badge text-bg-light border text-muted">{{ $s['class_code'] }}</span> @endif
                                    </div>
                                    @if($s['lecturer_name']) <div class="small text-muted mt-1" style="font-size: 0.70rem;">{{ $s['lecturer_name'] }}</div> @endif
                                    <div class="small text-danger fw-semibold mt-1" style="font-size: 0.70rem;">{{ $s['note'] ?: 'Kuliah diliburkan.' }}</div>
                                    {!! $renderSessionControls($s, 'mt-2 pt-1 border-top border-secondary border-opacity-25') !!}
                                </td>
                            @elseif($cell['bookable'])
                                <td class="col-room empty-slot bg-white p-0"
                                    data-bookable="1"
                                    data-room-id="{{ $cell['room_id'] }}"
                                    data-date="{{ $cell['date'] }}"
                                    data-slot-idx="{{ $row['slot_idx'] }}"
                                    title="Klik atau geser untuk memblok waktu peminjaman di {{ $cell['room_label'] }}"></td>
                            @else
                                <td class="col-room unbookable-slot p-0" data-bookable="0" title="Tidak dapat dipinjam (minimal pengajuan H+2)"></td>
                            @endif
                        @endforeach
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>

{{-- Floating Pop-up Konfirmasi Blok Peminjaman --}}
<div id="bookingDragPopup" class="booking-selection-popup shadow-lg border">
    <div class="d-flex justify-content-between align-items-center px-3 py-2 bg-primary text-white rounded-top">
        <div class="d-flex align-items-center gap-2">
            <span class="fw-bold small">Peminjaman Ruangan Terpilih</span>
            <span class="badge bg-white text-primary fw-bold d-none" id="popupTotalBadge" style="font-size: 0.68rem;">1</span>
        </div>
        <button type="button" class="btn-close btn-close-white btn-sm" id="btnCancelDragSelection" aria-label="Tutup"></button>
    </div>
    <div class="p-3">
        <div id="popupFrozenContainer" class="mb-2 d-none">
            <div class="text-secondary fw-semibold mb-1" style="font-size: 0.68rem; letter-spacing: 0.03em;">RUANGAN TERKUNCI</div>
            <div id="popupFrozenList" class="d-flex flex-column gap-1" style="max-height: 120px; overflow-y: auto;"></div>
            <hr class="my-2">
        </div>
        <div id="popupActiveSelection">
            <div class="mb-2">
                <div class="text-secondary small" style="font-size: 0.70rem;">HARI & TANGGAL</div>
                <div class="fw-bold text-dark" id="popupDragDate">-</div>
            </div>
            <div class="mb-2">
                <div class="text-secondary small" style="font-size: 0.70rem;">LABORATORIUM</div>
                <div class="fw-bold text-dark" id="popupDragRoom">-</div>
            </div>
            <div class="p-2 rounded bg-light border d-flex align-items-center justify-content-between mb-3">
                <div>
                    <div class="text-secondary small" style="font-size: 0.68rem;">JAM PEMINJAMAN</div>
                    <div class="fw-bold text-primary fs-6" id="popupDragTime">-</div>
                </div>
                <span class="badge text-bg-primary fw-semibold" id="popupDragDuration">-</span>
            </div>
        </div>
        <div id="popupWaitingNextDrag" class="p-2 rounded bg-light border text-secondary small mb-3 d-none" style="font-size: 0.76rem;">
            Jadwal sebelumnya telah dikunci. Silakan <strong>klik & geser (drag)</strong> pada kolom ruangan lain untuk menambah peminjaman berikutnya.
        </div>
        <div class="row g-2">
            <div class="col-6">
                <button type="button" id="btnFreezeAddRoom" class="btn btn-outline-primary w-100 fw-semibold py-2 shadow-sm d-flex align-items-center justify-content-center gap-1" style="font-size: 0.80rem;">
                    <span>+ Tambah Ruangan</span>
                </button>
            </div>
            <div class="col-6">
                <a href="#" id="btnConfirmDragBooking" class="btn btn-primary w-100 fw-bold py-2 shadow-sm d-flex align-items-center justify-content-center gap-1" style="font-size: 0.80rem;">
                    <span>Konfirmasi & Ajukan</span>
                </a>
            </div>
        </div>
    </div>
</div>

@if(auth()->check() && auth()->user()->hasRole('Admin', 'Kepala_Lab', 'Staf_Lab', 'Dosen', 'Kepala_Prodi'))
<div class="modal fade" id="overrideSessionModal" tabindex="-1" aria-labelledby="overrideSessionModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-light">
                <div>
                    <h5 class="modal-title fw-bold fs-6" id="overrideSessionModalLabel">Override Pertemuan Kuliah</h5>
                    <div class="text-muted small" id="overrideSessionSubtitle">-</div>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
            </div>
            <form id="overrideSessionForm" method="POST" action="">
                @csrf
                @method('PATCH')
                <div class="modal-body p-4">
                    <div class="alert alert-secondary py-2 px-3 mb-3 small d-flex flex-column gap-1">
                        <div class="fw-semibold text-dark">Jadwal Induk:</div>
                        <div>Hari & Tanggal Default: <span class="fw-bold" id="overrideParentDateText">-</span></div>
                        <div>Ruangan Default: <span class="fw-bold" id="overrideParentRoomText">-</span></div>
                        <div>Waktu Default: <span class="fw-bold" id="overrideParentTimeText">-</span></div>
                    </div>
                    <div class="mb-3">
                        <label for="overrideSessionDate" class="form-label small fw-bold">Pindah ke Hari / Tanggal (Opsional)</label>
                        <input type="date" name="session_date" id="overrideSessionDate" class="form-control"
                            @if($period) min="{{ $period->start_date->toDateString() }}" max="{{ $period->end_date->toDateString() }}" @endif>
                        <div class="form-text small" style="font-size: 0.72rem;">Pilih tanggal baru jika pertemuan ini dipindahkan ke hari lain (Senin - Sabtu).</div>
                        <div class="small fw-semibold text-primary mt-1" id="overrideSessionDatePreview" style="font-size: 0.72rem;"></div>
                    </div>
                    <div class="mb-3">
                        <label for="overrideRoomId" class="form-label small fw-bold">Pindah ke Ruangan (Opsional)</label>
                        <select name="room_id" id="overrideRoomId" class="form-select">
                            <option value="">— Ikut jadwal induk —</option>
                            @foreach($allRooms as $r)
                                <option value="{{ $r->id }}">{{ $r->labelWithCapacity() }} — {{ $r->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="row g-2 mb-3">
                        <div class="col-6">
                            <label for="overrideStartTime" class="form-label small fw-bold">Jam Mulai (Opsional)</label>
                            <input type="time" name="start_time" id="overrideStartTime" class="form-control">
                        </div>
                        <div class="col-6">
                            <label for="overrideEndTime" class="form-label small fw-bold">Jam Selesai (Opsional)</label>
                            <input type="time" name="end_time" id="overrideEndTime" class="form-control">
                        </div>
                        <div class="col-12">
                            <div class="form-text small" style="font-size: 0.72rem;">Kosongkan jika jam kuliah tidak berubah. Jika diisi, jam mulai dan jam selesai wajib diisi keduanya.</div>
                        </div>
                    </div>
                    <div class="mb-1">
                        <label for="overrideNote" class="form-label small fw-bold">Catatan Override</label>
                        <input type="text" name="note" id="overrideNote" class="form-control" maxlength="100" placeholder="Contoh: Dipindah ke Lab ADV2">
                    </div>
                </div>
                <div class="modal-footer bg-light px-4 py-2 d-flex justify-content-between align-items-center">
                    <button type="submit" name="clear_override" value="1" class="btn btn-outline-danger btn-sm" id="btnResetOverride" data-confirm="Reset semua override pertemuan ini ke jadwal induk?">Reset Override</button>
                    <div class="d-flex gap-2 ms-auto">
                        <button type="button" class="btn btn-light btn-sm border" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-primary btn-sm px-3 fw-semibold">Simpan</button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>
@endif
@endsection

@push('styles')
<style>
.schedule-table-card { border-radius: 0.75rem; overflow: hidden; }
.schedule-scroll-container { overflow-x: auto; border-radius: 0.5rem; border: 1px solid #dee2e6; background: #fff; }
.schedule-grid-table { table-layout: fixed !important; width: max-content !important; min-width: 100% !important; margin-bottom: 0 !important; border-collapse: separate !important; border-spacing: 0 !important; }
.schedule-grid-table tr { height: 38px !important; }
.schedule-grid-table th, .schedule-grid-table td { border-right: 1px solid #dee2e6 !important; border-bottom: 1px solid #dee2e6 !important; }
.schedule-grid-table thead th { text-align: center !important; font-size: 0.78rem !important; vertical-align: middle !important; }
.schedule-grid-table th.col-jam, .schedule-grid-table td.col-jam { width: 125px !important; min-width: 125px !important; max-width: 125px !important; height: 38px !important; position: sticky !important; left: 0 !important; z-index: 10 !important; background-color: #f8f9fa !important; border-right: 2px solid #ced4da !important; padding: 4px 8px !important; vertical-align: middle !important; }
.schedule-grid-table thead th.col-jam { z-index: 12 !important; }
.schedule-grid-table th.col-room, .schedule-grid-table td.col-room { width: 145px !important; min-width: 145px !important; max-width: 145px !important; vertical-align: middle !important; word-break: break-word !important; }
.schedule-grid-table td.empty-slot { cursor: pointer; background-color: #fff; user-select: none; transition: background-color 0.1s ease-in-out; }
.schedule-grid-table td.empty-slot:hover { background-color: #e9f2ff !important; }
.schedule-grid-table td.empty-slot.selected-drag-slot { background-color: #cfe2ff !important; outline: 2px dashed #0d6efd !important; outline-offset: -2px; }
.schedule-grid-table td.empty-slot.locked-drag-slot { background-color: #dbeafe !important; outline: 2px solid #2563eb !important; outline-offset: -2px; cursor: not-allowed !important; }
.schedule-grid-table td.unbookable-slot { background-color: #f8f9fa !important; cursor: not-allowed !important; user-select: none; }
.schedule-grid-table .form-switch { min-height: auto; padding-left: 0 !important; display: flex !important; justify-content: center !important; align-items: center !important; }
.schedule-grid-table .form-switch .form-check-input { float: none !important; margin: 0 !important; cursor: pointer !important; width: 2.3em !important; height: 1.15em !important; }
.booking-selection-popup { position: fixed; top: 75px; left: 20px; z-index: 1060; width: 356px; max-width: calc(100vw - 32px); border-radius: 0.75rem; background: #fff; border: 2px solid #0d6efd !important; transform: translateY(-15px); opacity: 0; pointer-events: none; transition: all 0.22s cubic-bezier(0.16, 1, 0.3, 1); box-shadow: 0 12px 36px rgba(13, 110, 253, 0.2), 0 4px 12px rgba(0, 0, 0, 0.08); }
.booking-selection-popup.show { transform: translateY(0); opacity: 1; pointer-events: auto; }
.schedule-scroll-container::-webkit-scrollbar { height: 8px; }
.schedule-scroll-container::-webkit-scrollbar-track { background: #f1f3f5; border-radius: 4px; }
.schedule-scroll-container::-webkit-scrollbar-thumb { background: #ced4da; border-radius: 4px; }
</style>
@endpush

@push('scripts')
<script>
$(document).ready(function () {
    const csrfToken = $('meta[name="csrf-token"]').attr('content') || '';
    const bookingCreateBaseUrl = @json(route('bookings.create'));
    const scheduleData = JSON.parse(atob(@json($scheduleFrontendPayload)));
    const roomMap = Object.fromEntries((scheduleData.rooms || []).map(r => [r.id, r]));
    const STORAGE_KEY = 'lab_frozen_drag_slots_v1';

    let currentSearchQuery = '';
    let isDragging = false;
    let dragRoomId = null;
    let dragDateVal = null;
    let dragStartIdx = null;
    let dragCurrentIdx = null;
    let currentSelection = null;
    let lockedSelections = [];
    let lastUnbookableNoticeAt = 0;

    try {
        const parsed = JSON.parse(sessionStorage.getItem(STORAGE_KEY) || '[]');
        if (Array.isArray(parsed)) lockedSelections = parsed;
    } catch (e) {}

    function escapeHtml(str) {
        return String(str ?? '').replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;').replace(/'/g, '&#39;');
    }

    function formatIndonesianDate(dateStr, padDay = false) {
        if (!dateStr) return '';
        const days = ['Minggu', 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'];
        const months = ['Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];
        const p = dateStr.split('-');
        if (p.length !== 3) return '';
        const d = new Date(Number(p[0]), Number(p[1]) - 1, Number(p[2]));
        if (isNaN(d.getTime())) return '';
        return `${days[d.getDay()]}, ${padDay ? String(d.getDate()).padStart(2, '0') : d.getDate()} ${months[d.getMonth()]} ${d.getFullYear()}`;
    }

    function initScheduleDataTable() {
        if (typeof $.fn.DataTable === 'undefined') return;
        if ($.fn.DataTable.isDataTable('#scheduleGridTable')) {
            $('#scheduleGridTable').DataTable().destroy();
        }
        $('#scheduleGridTable').DataTable({
            paging: false,
            ordering: false,
            info: false,
            searching: false,
            autoWidth: false,
            dom: "<'schedule-scroll-container shadow-sm'tr>"
        });
    }

    function renderManageControls(sess, wrapperClass) {
        if (!sess.can_manage) return '';
        const checkedAttr = sess.is_active ? 'checked' : '';
        const tip = sess.is_active ? 'Klik untuk meliburkan' : 'Klik untuk mengaktifkan kembali';
        return `<div class="${wrapperClass} d-flex justify-content-center align-items-center gap-2">
            <form method="POST" action="${escapeHtml(sess.toggle_url)}" class="d-inline-block m-0 session-toggle-form" data-schedule-id="${sess.schedule_id}" data-week-no="${sess.week_no}">
                <input type="hidden" name="_token" value="${escapeHtml(csrfToken)}">
                <div class="form-check form-switch m-0 p-0" title="${tip}">
                    <input class="form-check-input m-0 session-toggle-input" type="checkbox" role="switch" id="toggle-${sess.schedule_id}-${sess.week_no}" ${checkedAttr}>
                </div>
            </form>
            <button type="button" class="btn btn-sm btn-light border py-0 px-1 text-secondary" style="font-size: 0.68rem; line-height: 1.2;" title="Pindah Jadwal / Ruangan / Waktu" data-bs-toggle="modal" data-bs-target="#overrideSessionModal" data-schedule-id="${sess.schedule_id}" data-week-no="${sess.week_no}">
                <svg width="11" height="11" fill="currentColor"><use href="#icon-pencil-square"/></svg>
            </button>
        </div>`;
    }

    function saveLockedSelections() {
        try {
            if (lockedSelections.length > 0) sessionStorage.setItem(STORAGE_KEY, JSON.stringify(lockedSelections));
            else sessionStorage.removeItem(STORAGE_KEY);
        } catch (e) {}
    }

    function syncLockedGridVisuals() {
        $('.empty-slot.locked-drag-slot').removeClass('locked-drag-slot');
        lockedSelections.forEach(sel => {
            for (let i = sel.minIdx; i <= sel.maxIdx; i++) {
                $(`.empty-slot[data-room-id="${sel.roomId}"][data-date="${sel.date}"][data-slot-idx="${i}"]`).addClass('locked-drag-slot');
            }
        });
    }

    function buildConfirmUrl() {
        const all = [...lockedSelections, ...(currentSelection ? [currentSelection] : [])];
        if (!all.length) return '#';
        if (all.length === 1) {
            const s = all[0];
            return `${bookingCreateBaseUrl}?room_id=${encodeURIComponent(s.roomId)}&date=${encodeURIComponent(s.date)}&start=${encodeURIComponent(s.start)}&end=${encodeURIComponent(s.end)}`;
        }
        const params = new URLSearchParams();
        all.forEach((s, idx) => {
            params.append(`slots[${idx}][room_id]`, s.roomId);
            params.append(`slots[${idx}][date]`, s.date);
            params.append(`slots[${idx}][start]`, s.start);
            params.append(`slots[${idx}][end]`, s.end);
        });
        return `${bookingCreateBaseUrl}?${params.toString()}`;
    }

    function renderPopupState() {
        syncLockedGridVisuals();
        const totalCount = lockedSelections.length + (currentSelection ? 1 : 0);
        if (!totalCount) {
            $('#bookingDragPopup').removeClass('show');
            return;
        }
        $('#popupTotalBadge').text(`${totalCount} Ruangan`).toggleClass('d-none', totalCount <= 1);
        if (lockedSelections.length > 0) {
            $('#popupFrozenContainer').removeClass('d-none');
            $('#popupFrozenList').html(lockedSelections.map((item, idx) => `
                <div class="d-flex justify-content-between align-items-center px-2 py-1 rounded border bg-primary bg-opacity-10" style="font-size: 0.74rem;">
                    <div class="text-truncate me-2">
                        <span class="fw-bold text-primary">${escapeHtml(item.roomLabel)}</span>
                        <span class="text-dark">• ${escapeHtml(item.start)} - ${escapeHtml(item.end)}</span>
                        <div class="text-secondary" style="font-size: 0.66rem;">${escapeHtml(item.dateFormatted)}</div>
                    </div>
                    <button type="button" class="btn btn-sm p-0 px-1 text-danger border-0 fw-bold btn-remove-frozen" data-idx="${idx}" title="Hapus ruangan ini">&times;</button>
                </div>`).join(''));
        } else {
            $('#popupFrozenContainer').addClass('d-none');
            $('#popupFrozenList').empty();
        }

        if (currentSelection) {
            $('#popupActiveSelection').removeClass('d-none');
            $('#popupWaitingNextDrag').addClass('d-none');
            $('#popupDragDate').text(currentSelection.dateFormatted);
            $('#popupDragRoom').text(currentSelection.roomLabel);
            $('#popupDragTime').text(`${currentSelection.start} - ${currentSelection.end}`);
            $('#popupDragDuration').text(currentSelection.durationText);
            $('#btnFreezeAddRoom').prop('disabled', false);
        } else {
            $('#popupActiveSelection').addClass('d-none');
            $('#popupWaitingNextDrag').removeClass('d-none');
            $('#btnFreezeAddRoom').prop('disabled', true);
        }
        $('#btnConfirmDragBooking').attr('href', buildConfirmUrl());
        $('#bookingDragPopup').addClass('show');
    }

    function clearActiveDragOnly() {
        $('.empty-slot.selected-drag-slot').removeClass('selected-drag-slot');
        isDragging = false;
        dragRoomId = dragDateVal = dragStartIdx = dragCurrentIdx = currentSelection = null;
    }

    function updateDragSelection() {
        if (!dragRoomId || dragStartIdx === null || dragCurrentIdx === null) return;
        const min = Math.min(dragStartIdx, dragCurrentIdx);
        const max = Math.max(dragStartIdx, dragCurrentIdx);
        const targetCells = [];
        for (let i = min; i <= max; i++) {
            const cell = document.querySelector(`.empty-slot[data-room-id="${dragRoomId}"][data-slot-idx="${i}"]`);
            if (!cell || cell.classList.contains('locked-drag-slot') || cell.dataset.bookable === '0') return;
            targetCells.push(cell);
        }
        $('.empty-slot.selected-drag-slot').removeClass('selected-drag-slot');
        targetCells.forEach(c => c.classList.add('selected-drag-slot'));

        const slots = scheduleData.time_slots || [];
        const startTime = slots[min]?.start || '';
        const endTime = slots[max]?.end || '';
        const totalMinutes = (max - min + 1) * 30;
        const h = Math.floor(totalMinutes / 60);
        const m = totalMinutes % 60;
        const rm = roomMap[dragRoomId] || { id: dragRoomId, name: dragRoomId, label: dragRoomId };

        currentSelection = {
            roomId: dragRoomId,
            roomLabel: rm.label || dragRoomId,
            date: dragDateVal,
            dateFormatted: formatIndonesianDate(dragDateVal, true),
            start: startTime,
            end: endTime,
            minIdx: min,
            maxIdx: max,
            durationText: m === 0 ? `${h} Jam` : (h > 0 ? `${h} Jam ${m} Mnt` : `${m} Menit`),
        };
        renderPopupState();
    }

    function syncResetFilterButton() {
        const def = scheduleData.default_state;
        const isDiff = String($('#schedulePeriodFilter').val() || '') !== String(def.period_id || '')
            || Number($('#scheduleWeekFilter').val() || 1) !== Number(def.week || 1)
            || Number($('#scheduleDayFilter').val() || 1) !== Number(def.day || 1)
            || String($('#scheduleRoomFilter').val() || '') !== ''
            || currentSearchQuery.trim() !== '';
        $('#btnResetScheduleFilter').toggleClass('d-none', !isDiff);
    }

    function renderScheduleFrontend(updateHistory = true, isSearchOnly = false) {
        const selPeriodId = String($('#schedulePeriodFilter').val() || '');
        const periodObj = scheduleData.periods.find(p => String(p.id) === selPeriodId) || scheduleData.periods[0] || null;
        const calendar = periodObj ? (periodObj.calendar || []) : [];

        let selWeekIdx = Number($('#scheduleWeekFilter').val() || 1);
        const selectedWeek = calendar.find(w => Number(w.week_index) === selWeekIdx) || calendar[0] || null;
        if (selectedWeek) selWeekIdx = Number(selectedWeek.week_index);

        const selDay = Math.max(1, Math.min(6, Number($('#scheduleDayFilter').val() || 1)));
        const selRoomId = String($('#scheduleRoomFilter').val() || '');
        const searchQuery = currentSearchQuery.trim().toLowerCase();

        const dayInfo = selectedWeek ? (selDay === 6 ? selectedWeek.saturday : (selectedWeek.days[selDay - 1] || null)) : null;
        const targetDateStr = dayInfo ? dayInfo.date : '';

        if (periodObj) {
            $('#overrideSessionDate').attr({ min: periodObj.start_date, max: periodObj.end_date });
            $('#headerPeriodName').text(periodObj.name);
            $('#headerPeriodWrapper').removeClass('d-none');
        } else {
            $('#headerPeriodWrapper').addClass('d-none');
        }

        const formattedHeaderDate = formatIndonesianDate(targetDateStr, true);
        if (formattedHeaderDate) $('#headerScheduleDate').text(formattedHeaderDate);

        if (dayInfo && dayInfo.is_holiday) {
            $('#headerHolidayBadge').text(`Libur: ${dayInfo.holiday_note || 'Hari Libur'}`).removeClass('d-none');
        } else {
            $('#headerHolidayBadge').addClass('d-none');
        }

        if (currentSelection && (currentSelection.date !== targetDateStr || (selRoomId && currentSelection.roomId !== selRoomId))) {
            clearActiveDragOnly();
        }

        const visibleRooms = selRoomId ? scheduleData.rooms.filter(r => r.id === selRoomId) : scheduleData.rooms;
        const daySessions = (periodObj && targetDateStr)
            ? scheduleData.sessions.filter(s => Number(s.period_id) === Number(periodObj.id) && s.date === targetDateStr && (scheduleData.can_see_inactive || s.is_active) && (!searchQuery || s.search_text.includes(searchQuery)))
            : [];
        const dayBookings = targetDateStr
            ? scheduleData.bookings.filter(b => b.date === targetDateStr && (!searchQuery || b.search_text.includes(searchQuery)))
            : [];

        const timeSlots = scheduleData.time_slots || [];
        const totalSlots = timeSlots.length;
        const gridOccupants = {};
        const cellMeta = {};

        visibleRooms.forEach(room => {
            gridOccupants[room.id] = timeSlots.map(slot => {
                const st = slot.start;
                const activeSess = daySessions.find(s => s.is_active && s.room_id === room.id && s.start <= st && s.end > st);
                const inactiveSess = daySessions.find(s => !s.is_active && s.room_id === room.id && s.start <= st && s.end > st);
                const bk = dayBookings.find(b => b.room_id === room.id && b.start <= st && b.end > st);
                if (activeSess) return { type: 'active_session', key: `sess_${activeSess.schedule_id}_${activeSess.week_no}`, item: activeSess, inactive: null };
                if (bk) return { type: 'booking', key: bk.key, item: bk, inactive: inactiveSess || null };
                if (inactiveSess) return { type: 'inactive_session', key: `inactive_${inactiveSess.schedule_id}_${inactiveSess.week_no}`, item: inactiveSess, inactive: null };
                return { type: 'empty', key: null, item: null, inactive: null };
            });

            cellMeta[room.id] = [];
            let idx = 0;
            while (idx < totalSlots) {
                const occ = gridOccupants[room.id][idx];
                if (occ.type === 'empty') {
                    cellMeta[room.id][idx++] = { render: true, rowspan: 1, data: occ };
                } else {
                    let span = 1;
                    while ((idx + span) < totalSlots && gridOccupants[room.id][idx + span].key === occ.key) span++;
                    cellMeta[room.id][idx] = { render: true, rowspan: span, data: occ };
                    for (let s = 1; s < span; s++) cellMeta[room.id][idx + s] = { render: false };
                    idx += span;
                }
            }
        });

        if (!isSearchOnly) {
            if ($.fn.DataTable.isDataTable('#scheduleGridTable')) {
                $('#scheduleGridTable').DataTable().destroy();
            }
            $('#scheduleGridThead').html(`<tr><th class="col-jam fw-bold text-secondary text-center" style="font-size: 0.78rem;">Jam</th>${visibleRooms.map(r => `<th class="col-room fw-bold text-dark text-center" style="font-size: 0.78rem;">${escapeHtml(r.label)}</th>`).join('')}</tr>`);
        }

        const isSunday = targetDateStr ? new Date(targetDateStr + 'T00:00:00').getDay() === 0 : false;
        const minBookingDt = scheduleData.min_booking_datetime || '';

        const rowsHtml = timeSlots.map((slot, slotIdx) => {
            const isBookable = Boolean(targetDateStr) && !isSunday && (`${targetDateStr} ${slot.start}` >= minBookingDt) && !searchQuery;
            const bookableFlag = isBookable ? 1 : 0;

            const cellsHtml = visibleRooms.map(room => {
                const meta = cellMeta[room.id][slotIdx];
                if (!meta || !meta.render) return `<td class="d-none spanned-cell"></td>`;
                const occ = meta.data;

                if (occ.type === 'active_session') {
                    const s = occ.item;
                    return `<td rowspan="${meta.rowspan}" class="col-room p-2 text-center" style="background-color: ${escapeHtml(s.color_hex)}; vertical-align: middle;">
                        <div class="fw-bold" style="font-size: 0.82rem; line-height: 1.25;">${escapeHtml(s.course_name)}</div>
                        <div class="small text-secondary mt-1" style="font-size: 0.70rem;">${escapeHtml(s.start)} - ${escapeHtml(s.end)}${s.class_code ? ` <span class="badge text-bg-light border">${escapeHtml(s.class_code)}</span>` : ''}</div>
                        ${s.lecturer_name ? `<div class="small text-dark mt-1" style="font-size: 0.70rem;">${escapeHtml(s.lecturer_name)}</div>` : ''}
                        ${s.note ? `<div class="small text-secondary fw-semibold mt-1" style="font-size: 0.70rem;">${escapeHtml(s.note)}</div>` : ''}
                        ${renderManageControls(s, 'mt-2 pt-1 border-top border-dark border-opacity-10')}
                    </td>`;
                }

                if (occ.type === 'booking') {
                    const bk = occ.item;
                    const inact = occ.inactive;
                    const mainHtml = bk.is_pending
                        ? `<div class="fw-bold text-primary" style="font-size: 0.82rem;">Pending</div><div class="small text-secondary" style="font-size: 0.70rem;">${escapeHtml(bk.start)} - ${escapeHtml(bk.end)}</div><div class="small text-dark fw-semibold mt-1" style="font-size: 0.72rem;">${escapeHtml(bk.display_requester)}</div>`
                        : `<div class="fw-bold text-dark" style="font-size: 0.82rem;">${escapeHtml(bk.display_requester)}</div><div class="small text-secondary" style="font-size: 0.70rem;">${escapeHtml(bk.start)} - ${escapeHtml(bk.end)}</div>${bk.purpose_short ? `<div class="small text-muted mt-1" style="font-size: 0.70rem;">${escapeHtml(bk.purpose_short)}</div>` : ''}`;
                    const inactHtml = inact ? `<div class="mt-1 pt-1 border-top border-primary border-opacity-25">
                        <div class="small text-muted" style="font-size: 0.65rem;">${escapeHtml(inact.course_name)}</div>
                        <div class="small text-danger fw-semibold" style="font-size: 0.65rem;">Diliburkan</div>
                        ${renderManageControls(inact, 'mt-1')}
                    </div>` : '';
                    return `<td rowspan="${meta.rowspan}" class="col-room p-2 text-center" style="background-color: #BFE3F7; vertical-align: middle;">${mainHtml}${inactHtml}</td>`;
                }

                if (occ.type === 'inactive_session') {
                    const s = occ.item;
                    return `<td rowspan="${meta.rowspan}" class="col-room p-2 text-center" style="background-color: #f8f9fa; border: 1px dashed #adb5bd; vertical-align: middle;">
                        <div class="fw-semibold text-secondary" style="font-size: 0.82rem; line-height: 1.25;">${escapeHtml(s.course_name)}</div>
                        <div class="small text-muted mt-1" style="font-size: 0.70rem;">${escapeHtml(s.start)} - ${escapeHtml(s.end)}${s.class_code ? ` <span class="badge text-bg-light border text-muted">${escapeHtml(s.class_code)}</span>` : ''}</div>
                        ${s.lecturer_name ? `<div class="small text-muted mt-1" style="font-size: 0.70rem;">${escapeHtml(s.lecturer_name)}</div>` : ''}
                        <div class="small text-danger fw-semibold mt-1" style="font-size: 0.70rem;">${escapeHtml(s.note || 'Diliburkan.')}</div>
                        ${renderManageControls(s, 'mt-2 pt-1 border-top border-secondary border-opacity-25')}
                    </td>`;
                }

                return isBookable
                    ? `<td class="col-room empty-slot bg-white p-0" data-bookable="${bookableFlag}" data-room-id="${escapeHtml(room.id)}" data-date="${escapeHtml(targetDateStr)}" data-slot-idx="${slotIdx}" title="Klik atau geser untuk memblok waktu peminjaman di ${escapeHtml(room.label)}"></td>`
                    : `<td class="col-room unbookable-slot p-0" data-bookable="0" title="Tidak dapat dipinjam (minimal pengajuan H+2)"></td>`;
            }).join('');

            return `<tr><td class="col-jam table-light fw-bold text-secondary py-2" style="font-size: 0.78rem; white-space: nowrap;">${escapeHtml(slot.label)}</td>${cellsHtml}</tr>`;
        }).join('');

        $('#scheduleGridTbody').html(rowsHtml);
        if (!isSearchOnly) initScheduleDataTable();

        syncResetFilterButton();
        renderPopupState();

        if (updateHistory && window.history?.replaceState) {
            const url = new URL(window.location.href);
            if (periodObj) url.searchParams.set('period_id', periodObj.id);
            url.searchParams.set('week', String(selWeekIdx));
            url.searchParams.set('day', String(selDay));
            if (selRoomId) url.searchParams.set('room_id', selRoomId);
            else url.searchParams.delete('room_id');
            window.history.replaceState(null, '', url.toString());
        }
    }

    initScheduleDataTable();
    syncResetFilterButton();
    if (lockedSelections.length > 0) renderPopupState();

    $('#schedulePeriodFilter').on('change', function () {
        const periodObj = scheduleData.periods.find(p => String(p.id) === String($(this).val() || ''));
        if (periodObj) {
            $('#scheduleWeekFilter').html((periodObj.calendar || []).map(w =>
                `<option value="${w.week_index}" ${Number(w.week_index) === Number(periodObj.default_week_index) ? 'selected' : ''}>${escapeHtml(w.label)}</option>`
            ).join(''));
        }
        renderScheduleFrontend(true);
    });

    $('#scheduleWeekFilter, #scheduleDayFilter, #scheduleRoomFilter').on('change', () => renderScheduleFrontend(true));

    $('#btnResetScheduleFilter').on('click', function () {
        const def = scheduleData.default_state;
        $('#schedulePeriodFilter').val(String(def.period_id || ''));
        const periodObj = scheduleData.periods.find(p => String(p.id) === String(def.period_id));
        if (periodObj) {
            $('#scheduleWeekFilter').html((periodObj.calendar || []).map(w => `<option value="${w.week_index}">${escapeHtml(w.label)}</option>`).join(''));
        }
        $('#scheduleWeekFilter').val(String(def.week || 1));
        $('#scheduleDayFilter').val(String(def.day || 1));
        $('#scheduleRoomFilter').val('');
        currentSearchQuery = '';
        renderScheduleFrontend(true);
    });

    const $wrapper = $('#scheduleTableWrapper');

    $wrapper.on('change', '.session-toggle-input', async function () {
        const toggleInput = this;
        const form = toggleInput.closest('.session-toggle-form');
        if (!form) return;

        const targetActive = toggleInput.checked;
        toggleInput.disabled = true;

        try {
            const resp = await fetch(form.action, {
                method: 'POST',
                headers: { 'Accept': 'application/json', 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken, 'X-Requested-With': 'XMLHttpRequest' },
                body: JSON.stringify({ is_active: targetActive }),
            });
            const json = await resp.json().catch(() => null);
            if (resp.ok && json?.success) {
                const updatedIds = Array.isArray(json.schedule_ids) ? json.schedule_ids.map(Number) : [Number(form.dataset.scheduleId)];
                const targetWeek = Number(json.week_no || form.dataset.weekNo);
                scheduleData.sessions.forEach(s => {
                    if (Number(s.week_no) === targetWeek && (updatedIds.includes(Number(s.schedule_id)) || (Array.isArray(s.combined_ids) && s.combined_ids.some(id => updatedIds.includes(Number(id)))))) {
                        s.is_active = Boolean(json.is_active);
                        s.note = json.note || '';
                    }
                });
                renderScheduleFrontend(false);
                if (window.showAppToast && json.message) window.showAppToast(json.message, 'success');
            } else {
                toggleInput.checked = !targetActive;
                toggleInput.disabled = false;
                if (window.showAppToast) window.showAppToast(json?.message || 'Tidak dapat mengubah status sesi pertemuan.', 'danger', 6000);
            }
        } catch (err) {
            toggleInput.checked = !targetActive;
            toggleInput.disabled = false;
            if (window.showAppToast) window.showAppToast('Gagal menghubungi peladen. Silakan coba kembali.', 'danger');
        }
    });

    $wrapper.on('mousedown', function (e) {
        if (e.button !== 0) return;
        if (e.target.closest('.unbookable-slot')) {
            const nowMs = Date.now();
            if (nowMs - lastUnbookableNoticeAt > 1500 && window.showAppToast) {
                lastUnbookableNoticeAt = nowMs;
                window.showAppToast('Jadwal ini tidak dapat dipinjam. Pengajuan peminjaman wajib minimal H+2 (2 hari sebelum waktu kegiatan).', 'warning');
            }
            return;
        }
        const slot = e.target.closest('.empty-slot');
        if (!slot || slot.classList.contains('locked-drag-slot') || slot.dataset.bookable === '0') return;
        e.preventDefault();
        isDragging = true;
        dragRoomId = slot.dataset.roomId;
        dragDateVal = slot.dataset.date;
        dragStartIdx = dragCurrentIdx = Number(slot.dataset.slotIdx);
        updateDragSelection();
    });

    $wrapper.on('mouseover', function (e) {
        if (!isDragging) return;
        const slot = e.target.closest('.empty-slot');
        if (slot && !slot.classList.contains('locked-drag-slot') && slot.dataset.bookable !== '0' && slot.dataset.roomId === dragRoomId) {
            dragCurrentIdx = Number(slot.dataset.slotIdx);
            updateDragSelection();
        }
    });

    $(document).on('mouseup', () => { isDragging = false; });

    $('#btnFreezeAddRoom').on('click', function (e) {
        e.preventDefault();
        if (!currentSelection) return;
        lockedSelections.push({ ...currentSelection });
        saveLockedSelections();
        clearActiveDragOnly();
        renderPopupState();
    });

    $('#popupFrozenList').on('click', '.btn-remove-frozen', function (e) {
        e.preventDefault();
        const idx = Number(this.dataset.idx);
        if (!isNaN(idx) && idx >= 0 && idx < lockedSelections.length) {
            lockedSelections.splice(idx, 1);
            saveLockedSelections();
            renderPopupState();
        }
    });

    $('#btnConfirmDragBooking').on('click', function (e) {
        const url = buildConfirmUrl();
        if (!url || url === '#') { e.preventDefault(); return; }
        try { sessionStorage.removeItem(STORAGE_KEY); } catch (err) {}
        this.href = url;
    });

    $('#btnCancelDragSelection').on('click', function (e) {
        e.preventDefault();
        clearActiveDragOnly();
        lockedSelections = [];
        saveLockedSelections();
        syncLockedGridVisuals();
        $('#bookingDragPopup').removeClass('show');
    });

    const overrideModalEl = document.getElementById('overrideSessionModal');
    if (overrideModalEl) {
        $('#overrideSessionDate').on('input', function () {
            $('#overrideSessionDatePreview').text(this.value ? `Hari terpilih: ${formatIndonesianDate(this.value)}` : '');
        });

        overrideModalEl.addEventListener('show.bs.modal', function (event) {
            const btn = event.relatedTarget;
            if (!btn) return;
            const schedId = Number(btn.getAttribute('data-schedule-id'));
            const weekNo = Number(btn.getAttribute('data-week-no'));
            const sess = scheduleData.sessions.find(s => Number(s.schedule_id) === schedId && Number(s.week_no) === weekNo);
            if (!sess) return;

            $('#overrideSessionForm').attr('action', sess.update_url);
            $('#overrideSessionSubtitle').text(`${sess.course_name}${sess.class_code ? ' (' + sess.class_code + ')' : ''} • Pertemuan ke-${sess.week_no} (${formatIndonesianDate(sess.date)})`);
            $('#overrideParentDateText').text(formatIndonesianDate(sess.default_date) || '-');
            $('#overrideParentRoomText').text(sess.parent_room || '-');
            $('#overrideParentTimeText').text(`${sess.parent_start || ''} - ${sess.parent_end || ''}`);
            $('#overrideSessionDate').val(sess.date);
            $('#overrideSessionDatePreview').text(sess.date ? `Hari terpilih: ${formatIndonesianDate(sess.date)}` : '');
            $('#overrideRoomId').val(sess.override_room || '');
            $('#overrideStartTime').val(sess.override_start || '');
            $('#overrideEndTime').val(sess.override_end || '');
            $('#overrideNote').val(sess.note || '');
            const isOverridden = Boolean(sess.override_room || sess.override_start || (sess.date !== sess.default_date) || sess.note);
            $('#btnResetOverride').toggle(isOverridden);
        });
    }
});
</script>
@endpush
