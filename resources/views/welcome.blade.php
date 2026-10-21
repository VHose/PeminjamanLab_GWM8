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
    <div class="card-header bg-white py-3 d-flex flex-wrap justify-content-between align-items-center gap-2">
        <div>
            <h2 class="h5 mb-1 fw-bold">Denah Laboratorium Komputer GWM Lantai 8</h2>
            <div class="small text-muted">
                Arahkan kursor ke ruangan <span class="badge bg-primary-subtle text-primary border border-primary-subtle">Lab</span> untuk melihat nama ruangan, lalu klik untuk langsung mengajukan peminjaman.
            </div>
        </div>
        <a href="{{ asset('images/denah-lantai-8.png') }}" target="_blank" class="btn btn-sm btn-outline-primary">
            Lihat Ukuran Penuh
        </a>
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

<div class="card shadow-sm border-0 mb-5">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-bordered align-middle mb-0 text-center" style="font-size: 0.85rem;">
                <thead class="table-light">
                    <tr>
                        <th style="width: 140px;">Jam</th>
                        @foreach($rooms as $room)
                            <th>{{ $room->id }}</th>
                        @endforeach
                    </tr>
                </thead>
                <tbody>
                    @foreach($timeSlots as $slot)
                        @php $slotStart = $slot['start']; @endphp
                        <tr>
                            <td class="table-light fw-bold text-secondary py-2" style="font-size: 0.78rem; white-space: nowrap;">{{ $slot['label'] }}</td>
                            @foreach($rooms as $room)
                                @php
                                    $cellSessions = $sessions->filter(function($s) use ($room, $slotStart) {
                                        $effRoom = $s->effectiveRoomId();
                                        $effStart = substr($s->effectiveStartTime(), 0, 5);
                                        $effEnd = substr($s->effectiveEndTime(), 0, 5);
                                        return $effRoom === $room->id
                                            && $effStart <= $slotStart
                                            && $effEnd > $slotStart;
                                    });

                                    $cellBookings = $bookings->filter(function($b) use ($room, $slotStart) {
                                        $bStart = substr($b->start_time, 0, 5);
                                        $bEnd = substr($b->end_time, 0, 5);
                                        return $b->room_id === $room->id
                                            && $bStart <= $slotStart
                                            && $bEnd > $slotStart;
                                    });
                                @endphp

                                @if($cellSessions->isNotEmpty())
                                    @php
                                        $sess = $cellSessions->first();
                                        $effStart = substr($sess->effectiveStartTime(), 0, 5);
                                        $effEnd = substr($sess->effectiveEndTime(), 0, 5);
                                        $isStart = ($effStart === $slotStart);
                                        $color = $sess->schedule->course->studyProgram->color_hex ?? '#E2E3E5';
                                    @endphp
                                    <td class="p-1" style="background-color: {{ $color }}; {{ $isStart ? '' : 'border-top: none;' }}">
                                        @if($isStart)
                                            <div class="fw-bold">
                                                {{ $sess->schedule->course->name }}
                                            </div>
                                            <div class="small text-secondary" style="font-size: 0.72rem;">
                                                {{ $effStart }} - {{ $effEnd }}
                                                @if($sess->schedule->class_code)
                                                    <span class="badge text-bg-light border">{{ $sess->schedule->class_code }}</span>
                                                @endif
                                            </div>
                                            @if($isInternal && $sess->schedule->lecturer)
                                                <div class="small text-dark mt-1">{{ $sess->schedule->lecturer->name }}</div>
                                            @endif
                                            @if($sess->note)
                                                <div class="small text-danger fw-semibold">{{ $sess->note }}</div>
                                            @endif
                                        @endif
                                    </td>
                                @elseif($cellBookings->isNotEmpty())
                                    @php
                                        $bk = $cellBookings->first();
                                        $bStart = substr($bk->start_time, 0, 5);
                                        $bEnd = substr($bk->end_time, 0, 5);
                                        $isStart = ($bStart === $slotStart);
                                    @endphp
                                    <td class="p-1" style="background-color: #BFE3F7; {{ $isStart ? '' : 'border-top: none;' }}">
                                        @if($isStart)
                                            @if($bk->status === \App\Enums\BookingDetailStatus::PENDING)
                                                <div class="fw-bold text-primary">Pending</div>
                                                <div class="small text-secondary" style="font-size: 0.70rem;">{{ $bStart }} - {{ $bEnd }}</div>
                                                <div class="small text-dark fw-semibold mt-1">{{ $bk->display_requester }}</div>
                                            @else
                                                <div class="fw-bold text-dark">{{ $bk->display_requester }}</div>
                                                <div class="small text-secondary" style="font-size: 0.72rem;">{{ $bStart }} - {{ $bEnd }}</div>
                                                @if($isInternal)
                                                    <div class="small text-muted" style="font-size: 0.70rem;">{{ Str::limit($bk->booking->purpose, 25) }}</div>
                                                @endif
                                            @endif
                                        @endif
                                    </td>
                                @else
                                    <td class="bg-white p-1"></td>
                                @endif
                            @endforeach
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection

@push('styles')
<style>
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
});
</script>
@endpush
