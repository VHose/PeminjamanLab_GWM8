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

<div class="card mb-4 shadow-sm border-0">
    <div class="card-header bg-white py-3">
        <h2 class="h5 mb-0 fw-bold">Denah Laboratorium Komputer GWM Lantai 8</h2>
    </div>
    <div class="card-body p-3 text-center bg-light">
        <img src="{{ asset('images/denah-lantai-8.svg') }}" alt="Denah Lab Komputer GWM Lantai 8" class="img-fluid rounded border" style="max-height: 380px;">
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
