@extends('layouts.app')

@section('content')
<div class="row align-items-center mb-4">
    <div class="col-md-8">
        <h1 class="h3 fw-bold mb-1">Jadwal Penggunaan Ruangan Lab GWM Lantai 8</h1>
        <p class="text-secondary mb-0">
            Tanggal: <span class="fw-semibold text-dark">{{ $date->translatedFormat('l, d F Y') }}</span>
            @if($selectedDayInfo && $selectedDayInfo['is_holiday'])
                <span class="badge bg-danger ms-1">Libur: {{ $selectedDayInfo['holiday_note'] ?? 'Hari Libur' }}</span>
            @endif
            @if($period)
                | Periode: <span class="badge bg-secondary">{{ $period->name }}</span>
            @endif
        </p>
    </div>
    <div class="col-md-4 text-md-end mt-3 mt-md-0">
        @auth
            <a class="btn btn-primary" href="{{ route('bookings.create') }}">Ajukan Peminjaman Ruangan</a>
        @else
            <a class="btn btn-primary" href="{{ route('login') }}">Masuk untuk Meminjam</a>
        @endauth
    </div>
</div>

@php
    $bookingDate = ($date->isPast() && !$date->isToday()) ? now()->toDateString() : $date->toDateString();

    $labRooms = [
        [
            'id' => 'PROG2',
            'name' => 'Programming Lab 2',
            'x' => 24, 'y' => 95, 'w' => 98, 'h' => 166,
            'color' => '#0d6efd',
            'bg' => 'rgba(13, 110, 253, 0.38)',
        ],
        [
            'id' => 'PROG1',
            'name' => 'Programming Lab 1',
            'x' => 122, 'y' => 75, 'w' => 94, 'h' => 169,
            'color' => '#0d6efd',
            'bg' => 'rgba(13, 110, 253, 0.38)',
        ],
        [
            'id' => 'Network',
            'name' => 'Computer Network Lab',
            'x' => 216, 'y' => 88, 'w' => 92, 'h' => 156,
            'color' => '#0dcaf0',
            'bg' => 'rgba(13, 202, 240, 0.40)',
        ],
        [
            'id' => 'INT2',
            'name' => 'Internet Lab 2',
            'x' => 173, 'y' => 296, 'w' => 136, 'h' => 143,
            'color' => '#20c997',
            'bg' => 'rgba(32, 201, 151, 0.38)',
        ],
        [
            'id' => 'INT1',
            'name' => 'Internet Lab 1',
            'x' => 309, 'y' => 297, 'w' => 94, 'h' => 142,
            'color' => '#20c997',
            'bg' => 'rgba(32, 201, 151, 0.38)',
        ],
        [
            'id' => 'ADV4',
            'name' => 'Advance Programming Lab 4',
            'x' => 403, 'y' => 297, 'w' => 95, 'h' => 143,
            'color' => '#6610f2',
            'bg' => 'rgba(102, 16, 242, 0.38)',
        ],
        [
            'id' => 'ADV3',
            'name' => 'Advance Programming Lab 3',
            'x' => 498, 'y' => 297, 'w' => 88, 'h' => 146,
            'color' => '#6610f2',
            'bg' => 'rgba(102, 16, 242, 0.38)',
        ],
        [
            'id' => 'DB',
            'name' => 'Database Lab',
            'x' => 778, 'y' => 299, 'w' => 92, 'h' => 145,
            'color' => '#fd7e14',
            'bg' => 'rgba(253, 126, 20, 0.40)',
        ],
        [
            'id' => 'MMD',
            'name' => 'Multimedia Lab',
            'x' => 870, 'y' => 279, 'w' => 107, 'h' => 219,
            'color' => '#d63384',
            'bg' => 'rgba(214, 51, 132, 0.38)',
        ],
        [
            'id' => 'ENT1',
            'name' => 'Enterprise Lab 1',
            'x' => 28, 'y' => 487, 'w' => 99, 'h' => 90,
            'color' => '#198754',
            'bg' => 'rgba(25, 135, 84, 0.38)',
        ],
        [
            'id' => 'ENT2',
            'name' => 'Enterprise Lab 2',
            'x' => 127, 'y' => 488, 'w' => 88, 'h' => 122,
            'color' => '#198754',
            'bg' => 'rgba(25, 135, 84, 0.38)',
        ],
        [
            'id' => 'ADV1',
            'name' => 'Advance Programming Lab 1',
            'x' => 313, 'y' => 490, 'w' => 89, 'h' => 139,
            'color' => '#6610f2',
            'bg' => 'rgba(102, 16, 242, 0.38)',
        ],
        [
            'id' => 'ADV2',
            'name' => 'Advance Programming Lab 2',
            'x' => 402, 'y' => 490, 'w' => 92, 'h' => 140,
            'color' => '#6610f2',
            'bg' => 'rgba(102, 16, 242, 0.38)',
        ],
    ];
@endphp

<div class="card mb-4 shadow-sm border-0">
    <div class="card-header bg-white py-3">
        <h2 class="h5 mb-0 fw-bold">Denah Laboratorium Komputer GWM Lantai 8</h2>
    </div>
    <div class="card-body p-3 text-center bg-light">
        <div class="denah-interactive-wrapper shadow-sm rounded border bg-white">
            <svg viewBox="0 0 1024 765" class="denah-svg" preserveAspectRatio="xMidYMid meet">
                <!-- Gambar Denah Asli -->
                <image href="{{ asset('images/denah-lantai-8.png') }}" width="1024" height="765" />

                <!-- Area Interaktif Setiap Ruangan Lab -->
                @foreach($labRooms as $lab)
                    <a href="{{ route('bookings.create', ['room_id' => $lab['id'], 'date' => $bookingDate]) }}"
                       class="denah-lab-link"
                       style="--lab-hover-bg: {{ $lab['bg'] }}; --lab-hover-stroke: {{ $lab['color'] }};"
                       data-room-id="{{ $lab['id'] }}"
                       data-room-name="{{ $lab['name'] }}"
                       aria-label="Ajukan Peminjaman {{ $lab['name'] }} ({{ $lab['id'] }})">
                        <rect x="{{ $lab['x'] }}" y="{{ $lab['y'] }}" width="{{ $lab['w'] }}" height="{{ $lab['h'] }}" rx="4" class="denah-room-overlay" />
                    </a>
                @endforeach
            </svg>

            <!-- Floating Tooltip -->
            <div id="denahTooltip" class="denah-tooltip" role="tooltip"></div>
        </div>
    </div>
</div>

<!-- FORM PEMILIH PERIODE, MINGGU, HARI, DAN LAB -->
<div class="card mb-4 shadow-sm border-0">
    <div class="card-body p-3">
        <form method="get" action="{{ route('home') }}">
            <div class="row g-3 align-items-end">
                <div class="col-md-3">
                    <label class="form-label fw-semibold">1. Periode</label>
                    <select name="period_id" class="form-select" onchange="this.form.submit()">
                        @foreach($allPeriods as $p)
                            <option value="{{ $p->id }}" @selected($period && $period->id == $p->id)>
                                {{ $p->name }} {{ $p->is_active ? '(Aktif)' : '' }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label fw-semibold">2. Minggu Pertemuan / Kalender</label>
                    <select name="week" class="form-select" onchange="this.form.submit()">
                        @foreach($calendar as $w)
                            <option value="{{ $w['week_index'] }}" @selected($weekIndex == $w['week_index'])>
                                {{ $w['label'] }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label fw-semibold">3. Hari</label>
                    <select name="day" class="form-select" onchange="this.form.submit()">
                        @foreach(['Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'] as $index => $dayName)
                            <option value="{{ $index + 1 }}" @selected($day == $index + 1)>
                                {{ $dayName }} {{ $index + 1 === 6 ? '(Tanpa Perkuliahan Reguler)' : '' }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label fw-semibold">4. Laboratorium</label>
                    <select name="room_id" class="form-select">
                        <option value="">Semua Lab ({{ $allRooms->count() }} Ruangan)</option>
                        @foreach($allRooms as $r)
                            <option value="{{ $r->id }}" @selected($selectedRoomId == $r->id)>{{ $r->id }} ({{ $r->name }})</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <button type="submit" class="btn btn-outline-primary w-100">Tampilkan</button>
                </div>
            </div>
        </form>
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

@if($isNonLectureWeek)
    <div class="alert alert-info border-info mb-4 shadow-sm">
        <strong>Pemberitahuan Kalender Akademik:</strong>
        Minggu terpilih adalah <strong>{{ $selectedWeek['label'] }}</strong>.
        Jadwal perkuliahan reguler pada minggu ini <strong>kosong</strong>, namun ruangan laboratorium tetap dapat dipinjam untuk kegiatan akademik maupun himpunan mahasiswa.
    </div>
@elseif($day === 6)
    <div class="alert alert-secondary mb-4 shadow-sm">
        <strong>Hari Sabtu:</strong> Tidak ada jadwal perkuliahan reguler (jadwal kuliah berlangsung Senin sampai Jumat). Seluruh slot ruangan laboratorium di hari Sabtu dapat dipinjam.
    </div>
@elseif($selectedDayInfo && $selectedDayInfo['is_holiday'])
    <div class="alert alert-warning mb-4 shadow-sm">
        <strong>Hari Libur ({{ $selectedDayInfo['holiday_note'] ?? 'Libur' }}):</strong> Sesi perkuliahan pada tanggal ini dinonaktifkan.
    </div>
@endif

<div class="card shadow-sm border mb-5 schedule-table-card">
    <div class="card-header bg-white py-3 px-4 border-bottom">
        <h2 class="h5 mb-0 fw-bold">Jadwal Penggunaan Laboratorium</h2>
    </div>
    <div class="card-body p-3 p-md-4 bg-light bg-opacity-25">
        <div class="schedule-scroll-container shadow-sm">
            @php
                $gridOccupants = [];
                $totalSlots = count($timeSlots);

                foreach ($rooms as $room) {
                    $gridOccupants[$room->id] = [];
                    foreach ($timeSlots as $slotIdx => $slot) {
                        $slotStart = $slot['start'];

                        $slotSession = $sessions->first(function($s) use ($room, $slotStart) {
                            $effRoom = $s->effectiveRoomId();
                            $effStart = substr($s->effectiveStartTime(), 0, 5);
                            $effEnd = substr($s->effectiveEndTime(), 0, 5);
                            return $effRoom === $room->id && $effStart <= $slotStart && $effEnd > $slotStart;
                        });

                        $slotBooking = $bookings->first(function($b) use ($room, $slotStart) {
                            $bStart = substr($b->start_time, 0, 5);
                            $bEnd = substr($b->end_time, 0, 5);
                            return $b->room_id === $room->id && $bStart <= $slotStart && $bEnd > $slotStart;
                        });

                        if ($slotSession && $slotSession->is_active) {
                            $gridOccupants[$room->id][$slotIdx] = [
                                'type'     => 'active_session',
                                'key'      => 'sess_' . $slotSession->schedule_id . '_' . $slotSession->week_no,
                                'item'     => $slotSession,
                                'inactive' => null,
                            ];
                        } elseif ($slotBooking) {
                            $gridOccupants[$room->id][$slotIdx] = [
                                'type'     => 'booking',
                                'key'      => 'bk_' . $slotBooking->id,
                                'item'     => $slotBooking,
                                'inactive' => ($slotSession && ! $slotSession->is_active) ? $slotSession : null,
                            ];
                        } elseif ($slotSession && ! $slotSession->is_active) {
                            $gridOccupants[$room->id][$slotIdx] = [
                                'type'     => 'inactive_session',
                                'key'      => 'inactive_' . $slotSession->schedule_id . '_' . $slotSession->week_no,
                                'item'     => $slotSession,
                                'inactive' => null,
                            ];
                        } else {
                            $gridOccupants[$room->id][$slotIdx] = [
                                'type'     => 'empty',
                                'key'      => null,
                                'item'     => null,
                                'inactive' => null,
                            ];
                        }
                    }
                }

                $cellMeta = [];
                foreach ($rooms as $room) {
                    $cellMeta[$room->id] = [];
                    $slotIdx = 0;
                    while ($slotIdx < $totalSlots) {
                        $occ = $gridOccupants[$room->id][$slotIdx];
                        if ($occ['type'] === 'empty') {
                            $cellMeta[$room->id][$slotIdx] = [
                                'render'  => true,
                                'rowspan' => 1,
                                'data'    => $occ,
                            ];
                            $slotIdx++;
                        } else {
                            $span = 1;
                            while (($slotIdx + $span) < $totalSlots && $gridOccupants[$room->id][$slotIdx + $span]['key'] === $occ['key']) {
                                $span++;
                            }
                            $cellMeta[$room->id][$slotIdx] = [
                                'render'  => true,
                                'rowspan' => $span,
                                'data'    => $occ,
                            ];
                            for ($s = 1; $s < $span; $s++) {
                                $cellMeta[$room->id][$slotIdx + $s] = [
                                    'render'  => false,
                                    'rowspan' => 0,
                                    'data'    => null,
                                ];
                            }
                            $slotIdx += $span;
                        }
                    }
                }
            @endphp

            <table class="table table-bordered align-middle text-center schedule-grid-table">
                <thead class="table-light">
                    <tr>
                        <th class="col-jam fw-bold text-secondary">Jam</th>
                        @foreach($rooms as $room)
                            <th class="col-room fw-bold text-dark">{{ $room->id }}</th>
                        @endforeach
                    </tr>
                </thead>
                <tbody>
                    @foreach($timeSlots as $slotIdx => $slot)
                        <tr>
                            <td class="col-jam table-light fw-bold text-secondary py-2" style="font-size: 0.78rem; white-space: nowrap;">
                                {{ $slot['label'] }}
                            </td>
                            @foreach($rooms as $room)
                                @php
                                    $meta = $cellMeta[$room->id][$slotIdx] ?? null;
                                @endphp

                                @if($meta && $meta['render'])
                                    @php
                                        $rowspan = $meta['rowspan'];
                                        $occ = $meta['data'];
                                        $type = $occ['type'];
                                    @endphp

                                    @if($type === 'active_session')
                                        @php
                                            $sess = $occ['item'];
                                            $effStart = substr($sess->effectiveStartTime(), 0, 5);
                                            $effEnd = substr($sess->effectiveEndTime(), 0, 5);
                                            $color = $sess->schedule->course->studyProgram->color_hex ?? '#E2E3E5';
                                        @endphp
                                        <td rowspan="{{ $rowspan }}" class="col-room p-2 text-center" style="background-color: {{ $color }}; vertical-align: middle;">
                                            <div class="fw-bold" style="font-size: 0.82rem; line-height: 1.25;">
                                                {{ $sess->schedule->course->name }}
                                            </div>
                                            <div class="small text-secondary mt-1" style="font-size: 0.70rem;">
                                                {{ $effStart }} - {{ $effEnd }}
                                                @if($sess->schedule->class_code)
                                                    <span class="badge text-bg-light border">{{ $sess->schedule->class_code }}</span>
                                                @endif
                                            </div>
                                            @if($isInternal && $sess->schedule->lecturer)
                                                <div class="small text-dark mt-1" style="font-size: 0.70rem;">{{ $sess->schedule->lecturer->name }}</div>
                                            @endif
                                            @if($sess->note)
                                                <div class="small text-danger fw-semibold mt-1" style="font-size: 0.70rem;">{{ $sess->note }}</div>
                                            @endif

                                            @if($canToggleSession)
                                                <div class="mt-2 pt-1 border-top border-dark border-opacity-10 d-flex justify-content-center">
                                                    <form method="POST" action="{{ route('admin.schedule-sessions.toggle', [$sess->schedule_id, $sess->week_no]) }}" class="d-inline-block m-0">
                                                        @csrf
                                                        <div class="form-check form-switch m-0 p-0" title="Kuliah Aktif - Klik untuk meliburkan">
                                                            <input class="form-check-input m-0" type="checkbox" role="switch" id="toggle-{{ $sess->schedule_id }}-{{ $sess->week_no }}" onchange="this.form.submit()" checked>
                                                        </div>
                                                    </form>
                                                </div>
                                            @endif
                                        </td>
                                    @elseif($type === 'booking')
                                        @php
                                            $bk = $occ['item'];
                                            $inactiveSess = $occ['inactive'];
                                            $bStart = substr($bk->start_time, 0, 5);
                                            $bEnd = substr($bk->end_time, 0, 5);
                                        @endphp
                                        <td rowspan="{{ $rowspan }}" class="col-room p-2 text-center" style="background-color: #BFE3F7; vertical-align: middle;">
                                            @if($bk->status === \App\Enums\BookingDetailStatus::PENDING)
                                                <div class="fw-bold text-primary" style="font-size: 0.82rem;">Pending</div>
                                                <div class="small text-secondary" style="font-size: 0.70rem;">{{ $bStart }} - {{ $bEnd }}</div>
                                                <div class="small text-dark fw-semibold mt-1" style="font-size: 0.72rem;">{{ $bk->display_requester }}</div>
                                            @else
                                                <div class="fw-bold text-dark" style="font-size: 0.82rem;">{{ $bk->display_requester }}</div>
                                                <div class="small text-secondary" style="font-size: 0.70rem;">{{ $bStart }} - {{ $bEnd }}</div>
                                                @if($isInternal)
                                                    <div class="small text-muted mt-1" style="font-size: 0.70rem;">{{ Str::limit($bk->booking->purpose, 25) }}</div>
                                                @endif
                                            @endif

                                            @if($inactiveSess)
                                                <div class="mt-1 pt-1 border-top border-primary border-opacity-25">
                                                    <span class="badge text-bg-danger" style="font-size: 0.62rem;">Kuliah Libur</span>
                                                    <div class="small text-muted" style="font-size: 0.65rem;">{{ $inactiveSess->schedule->course->name }}</div>
                                                    @if($canToggleSession)
                                                        <div class="d-flex justify-content-center mt-1">
                                                            <form method="POST" action="{{ route('admin.schedule-sessions.toggle', [$inactiveSess->schedule_id, $inactiveSess->week_no]) }}" class="d-inline-block m-0">
                                                                @csrf
                                                                <div class="form-check form-switch m-0 p-0" title="Kuliah Libur - Klik untuk mengaktifkan kembali">
                                                                    <input class="form-check-input m-0" type="checkbox" role="switch" id="toggle-{{ $inactiveSess->schedule_id }}-{{ $inactiveSess->week_no }}" onchange="this.form.submit()">
                                                                </div>
                                                            </form>
                                                        </div>
                                                    @endif
                                                </div>
                                            @endif
                                        </td>
                                    @elseif($type === 'inactive_session')
                                        @php
                                            $sess = $occ['item'];
                                            $effStart = substr($sess->effectiveStartTime(), 0, 5);
                                            $effEnd = substr($sess->effectiveEndTime(), 0, 5);
                                        @endphp
                                        <td rowspan="{{ $rowspan }}" class="col-room p-2 text-center" style="background-color: #f8f9fa; border: 1px dashed #adb5bd; vertical-align: middle;">
                                            <div class="d-flex justify-content-center mb-1">
                                                <span class="badge text-bg-danger" style="font-size: 0.65rem;">Kuliah Libur</span>
                                            </div>
                                            <div class="fw-semibold text-secondary text-decoration-line-through" style="font-size: 0.82rem; line-height: 1.25;">
                                                {{ $sess->schedule->course->name }}
                                            </div>
                                            <div class="small text-muted mt-1" style="font-size: 0.70rem;">
                                                {{ $effStart }} - {{ $effEnd }}
                                                @if($sess->schedule->class_code)
                                                    <span class="badge text-bg-light border text-muted">{{ $sess->schedule->class_code }}</span>
                                                @endif
                                            </div>
                                            @if($isInternal && $sess->schedule->lecturer)
                                                <div class="small text-muted mt-1" style="font-size: 0.70rem;">{{ $sess->schedule->lecturer->name }}</div>
                                            @endif
                                            @if($sess->note)
                                                <div class="small text-danger fw-semibold mt-1" style="font-size: 0.70rem;">{{ $sess->note }}</div>
                                            @endif

                                            @if($canToggleSession)
                                                <div class="mt-2 pt-1 border-top border-secondary border-opacity-25 d-flex justify-content-center">
                                                    <form method="POST" action="{{ route('admin.schedule-sessions.toggle', [$sess->schedule_id, $sess->week_no]) }}" class="d-inline-block m-0">
                                                        @csrf
                                                        <div class="form-check form-switch m-0 p-0" title="Kuliah Libur - Klik untuk mengaktifkan kembali">
                                                            <input class="form-check-input m-0" type="checkbox" role="switch" id="toggle-{{ $sess->schedule_id }}-{{ $sess->week_no }}" onchange="this.form.submit()">
                                                        </div>
                                                    </form>
                                                </div>
                                            @endif
                                        </td>
                                    @else
                                        {{-- Empty slot (span 1) - Can be clicked and dragged to block booking --}}
                                        <td class="col-room empty-slot bg-white p-0"
                                            data-room-id="{{ $room->id }}"
                                            data-room-name="{{ $room->name }}"
                                            data-date="{{ $date->toDateString() }}"
                                            data-date-formatted="{{ $date->locale('id')->translatedFormat('l, d F Y') }}"
                                            data-start="{{ $slot['start'] }}"
                                            data-end="{{ $slot['end'] }}"
                                            data-slot-idx="{{ $slotIdx }}"
                                            title="Klik atau geser untuk memblok waktu peminjaman di {{ $room->id }}">
                                        </td>
                                    @endif
                                @endif
                            @endforeach
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>

{{-- Floating Pop-up Konfirmasi Blok Peminjaman (Kanan Atas) --}}
<div id="bookingDragPopup" class="booking-selection-popup shadow-lg border">
    <div class="d-flex justify-content-between align-items-center px-3 py-2 bg-primary text-white rounded-top">
        <div class="d-flex align-items-center gap-2">
            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-calendar2-check-fill" viewBox="0 0 16 16">
              <path d="M3.5 0a.5.5 0 0 1 .5.5V1h8V.5a.5.5 0 0 1 1 0V1h1a2 2 0 0 1 2 2v11a2 2 0 0 1-2 2H2a2 2 0 0 1-2-2V3a2 2 0 0 1 2-2h1V.5a.5.5 0 0 1 .5-.5zM2 3.5v1a.5.5 0 0 0 .5.5h11a.5.5 0 0 0 .5-.5v-1a.5.5 0 0 0-.5-.5H2a.5.5 0 0 0-.5.5zm10.854 4.646a.5.5 0 0 0-.708 0L7.5 12.793l-1.646-1.647a.5.5 0 0 0-.708.708l2 2a.5.5 0 0 0 .708 0l5-5a.5.5 0 0 0 0-.708z"/>
            </svg>
            <span class="fw-bold small">Peminjaman Ruangan Terpilih</span>
        </div>
        <button type="button" class="btn-close btn-close-white btn-sm" id="btnCancelDragSelection" aria-label="Tutup"></button>
    </div>
    <div class="p-3">
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
        <a href="#" id="btnConfirmDragBooking" class="btn btn-primary w-100 fw-bold py-2 shadow-sm d-flex align-items-center justify-content-center gap-2">
            <span>Konfirmasi & Ajukan</span>
            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-arrow-right" viewBox="0 0 16 16">
              <path fill-rule="evenodd" d="M1 8a.5.5 0 0 1 .5-.5h11.793l-3.147-3.146a.5.5 0 0 1 .708-.708l4 4a.5.5 0 0 1 0 .708l-4 4a.5.5 0 0 1-.708-.708L13.293 8.5H1.5A.5.5 0 0 1 1 8z"/>
            </svg>
        </a>
    </div>
</div>
@endsection

@push('styles')
<style>
/* ── Schedule Grid Table & Scroll Container Styling ── */
.schedule-table-card {
    border-radius: 0.75rem;
    overflow: hidden;
}

.schedule-scroll-container {
    overflow-x: auto;
    overflow-y: visible;
    -webkit-overflow-scrolling: touch;
    border-radius: 0.5rem;
    border: 1px solid #dee2e6;
    background: #ffffff;
}

.schedule-grid-table {
    table-layout: fixed !important;
    width: max-content !important;
    min-width: 100% !important;
    margin-bottom: 0 !important;
    border-collapse: separate !important;
    border-spacing: 0 !important;
}

.schedule-grid-table tr {
    height: 38px !important;
}

.schedule-grid-table th,
.schedule-grid-table td {
    border-right: 1px solid #dee2e6 !important;
    border-bottom: 1px solid #dee2e6 !important;
}

.schedule-grid-table th.col-jam,
.schedule-grid-table td.col-jam {
    width: 125px !important;
    min-width: 125px !important;
    max-width: 125px !important;
    height: 38px !important;
    position: sticky !important;
    left: 0 !important;
    z-index: 10 !important;
    background-color: #f8f9fa !important;
    border-right: 2px solid #ced4da !important;
    padding: 4px 8px !important;
    vertical-align: middle !important;
}

.schedule-grid-table thead th.col-jam {
    z-index: 12 !important;
}

.schedule-grid-table th.col-room,
.schedule-grid-table td.col-room {
    width: 145px !important;
    min-width: 145px !important;
    max-width: 145px !important;
    vertical-align: middle !important;
    word-break: break-word !important;
}

.schedule-grid-table td.empty-slot {
    cursor: pointer;
    background-color: #ffffff;
    user-select: none;
    -webkit-user-select: none;
    transition: background-color 0.1s ease-in-out;
}

.schedule-grid-table td.empty-slot:hover {
    background-color: #e9f2ff !important;
}

.schedule-grid-table td.empty-slot.selected-drag-slot {
    background-color: #cfe2ff !important;
    outline: 2px dashed #0d6efd !important;
    outline-offset: -2px;
}

.schedule-grid-table .form-switch {
    min-height: auto;
    padding-left: 0 !important;
    display: flex !important;
    justify-content: center !important;
    align-items: center !important;
}

.schedule-grid-table .form-switch .form-check-input {
    float: none !important;
    margin: 0 !important;
    cursor: pointer !important;
    width: 2.3em !important;
    height: 1.15em !important;
}

.booking-selection-popup {
    position: fixed;
    top: 85px;
    right: 24px;
    z-index: 1060;
    width: 320px;
    max-width: calc(100vw - 32px);
    border-radius: 0.75rem;
    background: #ffffff;
    border: 2px solid #0d6efd !important;
    transform: translateY(-15px);
    opacity: 0;
    pointer-events: none;
    transition: all 0.22s cubic-bezier(0.16, 1, 0.3, 1);
    box-shadow: 0 12px 36px rgba(13, 110, 253, 0.2), 0 4px 12px rgba(0, 0, 0, 0.08);
}

.booking-selection-popup.show {
    transform: translateY(0);
    opacity: 1;
    pointer-events: auto;
}

.schedule-scroll-container::-webkit-scrollbar {
    height: 8px;
}
.schedule-scroll-container::-webkit-scrollbar-track {
    background: #f1f3f5;
    border-radius: 4px;
}
.schedule-scroll-container::-webkit-scrollbar-thumb {
    background: #ced4da;
    border-radius: 4px;
}
.schedule-scroll-container::-webkit-scrollbar-thumb:hover {
    background: #adb5bd;
}

.denah-interactive-wrapper {
    position: relative;
    display: inline-block;
    width: 100%;
    max-width: 960px;
    margin: 0 auto;
    overflow: visible;
}
.denah-svg {
    width: 100%;
    height: auto;
    display: block;
    background: #ffffff;
    border-radius: 0.375rem;
}
.denah-lab-link {
    cursor: pointer;
    outline: none;
}
.denah-room-overlay {
    fill: transparent;
    stroke: transparent;
    stroke-width: 3.5;
    transition: fill 0.18s ease-in-out, stroke 0.18s ease-in-out, filter 0.18s ease-in-out;
}
.denah-lab-link:hover .denah-room-overlay,
.denah-lab-link:focus .denah-room-overlay {
    fill: var(--lab-hover-bg, rgba(13, 110, 253, 0.38));
    stroke: var(--lab-hover-stroke, #0d6efd);
    filter: drop-shadow(0 0 8px var(--lab-hover-stroke, #0d6efd));
}
.denah-tooltip {
    position: absolute;
    pointer-events: none;
    background: rgba(18, 22, 31, 0.94);
    color: #ffffff;
    padding: 7px 14px;
    border-radius: 6px;
    font-size: 0.82rem;
    line-height: 1.35;
    z-index: 1050;
    transform: translate(-50%, -125%);
    white-space: nowrap;
    opacity: 0;
    transition: opacity 0.15s ease-in-out;
    box-shadow: 0 4px 16px rgba(0, 0, 0, 0.3);
    border: 1px solid rgba(255, 255, 255, 0.2);
}
.denah-tooltip::after {
    content: '';
    position: absolute;
    top: 100%;
    left: 50%;
    margin-left: -5px;
    border-width: 5px;
    border-style: solid;
    border-color: rgba(18, 22, 31, 0.94) transparent transparent transparent;
}
.denah-tooltip .tooltip-action {
    display: block;
    font-size: 0.72rem;
    color: #9ec5fe;
    margin-top: 2px;
}
</style>
@endpush

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', () => {
    const wrapper = document.querySelector('.denah-interactive-wrapper');
    const tooltip = document.getElementById('denahTooltip');
    if (!wrapper || !tooltip) return;

    const links = wrapper.querySelectorAll('.denah-lab-link');
    links.forEach(link => {
        link.addEventListener('mouseenter', () => {
            const name = link.getAttribute('data-room-name');
            const code = link.getAttribute('data-room-id');
            tooltip.innerHTML = `<strong>${name} (${code})</strong><span class="tooltip-action">Klik untuk meminjam ruangan</span>`;
            tooltip.style.opacity = '1';
        });

        link.addEventListener('mousemove', (e) => {
            const rect = wrapper.getBoundingClientRect();
            const x = e.clientX - rect.left;
            const y = e.clientY - rect.top;
            tooltip.style.left = `${x}px`;
            tooltip.style.top = `${y}px`;
        });

        link.addEventListener('mouseleave', () => {
            tooltip.style.opacity = '0';
        });
    });

    // ── Click & Drag to Block Time Slots ──
    const dragPopup = document.getElementById('bookingDragPopup');
    const dragDate = document.getElementById('popupDragDate');
    const dragRoom = document.getElementById('popupDragRoom');
    const dragTime = document.getElementById('popupDragTime');
    const dragDuration = document.getElementById('popupDragDuration');
    const btnConfirmDrag = document.getElementById('btnConfirmDragBooking');
    const btnCancelDrag = document.getElementById('btnCancelDragSelection');

    let isDragging = false;
    let dragRoomId = null;
    let dragRoomName = null;
    let dragDateVal = null;
    let dragDateFormattedVal = null;
    let dragStartIdx = null;
    let dragCurrentIdx = null;

    function clearDragSelection() {
        document.querySelectorAll('.empty-slot.selected-drag-slot').forEach(el => {
            el.classList.remove('selected-drag-slot');
        });
        isDragging = false;
        dragRoomId = null;
        dragRoomName = null;
        dragDateVal = null;
        dragDateFormattedVal = null;
        dragStartIdx = null;
        dragCurrentIdx = null;
        if (dragPopup) {
            dragPopup.classList.remove('show');
        }
    }

    function updateDragSelection() {
        if (!dragRoomId || dragStartIdx === null || dragCurrentIdx === null) return;

        const min = Math.min(dragStartIdx, dragCurrentIdx);
        const max = Math.max(dragStartIdx, dragCurrentIdx);

        // Check if all cells between min and max are empty slots
        const targetCells = [];
        let isContiguous = true;
        for (let i = min; i <= max; i++) {
            const cell = document.querySelector(`.empty-slot[data-room-id="${dragRoomId}"][data-slot-idx="${i}"]`);
            if (!cell) {
                isContiguous = false;
                break;
            }
            targetCells.push(cell);
        }

        if (!isContiguous) {
            return;
        }

        // Apply visual selection
        document.querySelectorAll('.empty-slot.selected-drag-slot').forEach(el => {
            el.classList.remove('selected-drag-slot');
        });
        targetCells.forEach(cell => cell.classList.add('selected-drag-slot'));

        const firstCell = document.querySelector(`.empty-slot[data-room-id="${dragRoomId}"][data-slot-idx="${min}"]`);
        const lastCell = document.querySelector(`.empty-slot[data-room-id="${dragRoomId}"][data-slot-idx="${max}"]`);

        const startTime = firstCell.dataset.start;
        const endTime = lastCell.dataset.end;

        const countSlots = (max - min + 1);
        const totalMinutes = countSlots * 30;
        let durationText = '';
        if (totalMinutes % 60 === 0) {
            durationText = `${totalMinutes / 60} Jam`;
        } else {
            const h = Math.floor(totalMinutes / 60);
            const m = totalMinutes % 60;
            durationText = h > 0 ? `${h} Jam ${m} Mnt` : `${m} Menit`;
        }

        if (dragDate) dragDate.textContent = dragDateFormattedVal;
        if (dragRoom) dragRoom.textContent = `${dragRoomName} (${dragRoomId})`;
        if (dragTime) dragTime.textContent = `${startTime} - ${endTime}`;
        if (dragDuration) dragDuration.textContent = durationText;

        if (btnConfirmDrag) {
            btnConfirmDrag.href = `{{ route('bookings.create') }}?room_id=${encodeURIComponent(dragRoomId)}&date=${encodeURIComponent(dragDateVal)}&start=${encodeURIComponent(startTime)}&end=${encodeURIComponent(endTime)}`;
        }

        if (dragPopup) {
            dragPopup.classList.add('show');
        }
    }

    const scrollContainer = document.querySelector('.schedule-scroll-container');
    if (scrollContainer) {
        scrollContainer.addEventListener('mousedown', (e) => {
            const slot = e.target.closest('.empty-slot');
            if (!slot || e.button !== 0) return;

            e.preventDefault();
            isDragging = true;
            dragRoomId = slot.dataset.roomId;
            dragRoomName = slot.dataset.roomName;
            dragDateVal = slot.dataset.date;
            dragDateFormattedVal = slot.dataset.dateFormatted;
            dragStartIdx = parseInt(slot.dataset.slotIdx);
            dragCurrentIdx = dragStartIdx;

            updateDragSelection();
        });

        scrollContainer.addEventListener('mouseover', (e) => {
            if (!isDragging) return;
            const slot = e.target.closest('.empty-slot');
            if (!slot) return;

            if (slot.dataset.roomId === dragRoomId) {
                dragCurrentIdx = parseInt(slot.dataset.slotIdx);
                updateDragSelection();
            }
        });
    }

    document.addEventListener('mouseup', () => {
        if (isDragging) {
            isDragging = false;
        }
    });

    if (btnCancelDrag) {
        btnCancelDrag.addEventListener('click', (e) => {
            e.preventDefault();
            clearDragSelection();
        });
    }
});
</script>
@endpush
