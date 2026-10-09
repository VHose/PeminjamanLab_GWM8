@extends('layouts.app')

@section('content')
<div class="row align-items-center mb-4">
    <div class="col-md-8">
        <h1 class="h3 fw-bold mb-1">Jadwal Penggunaan Ruangan Lab GWM Lantai 8</h1>
        <p class="text-secondary mb-0">
            Tanggal: <span class="fw-semibold text-dark">{{ $date->translatedFormat('l, d F Y') }}</span>
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

<div class="card mb-4 shadow-sm border-0">
    <div class="card-body p-3">
        <form method="get" action="{{ route('home') }}">
            <div class="row g-3 align-items-end">
                <div class="col-md-3">
                    <label class="form-label fw-semibold">1. Periode</label>
                    <select name="period_id" class="form-select" onchange="this.form.submit()">
                        @foreach($allPeriods as $p)
                            <option value="{{ $p->id }}" @selected($period && $period->id == $p->id)>{{ $p->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label fw-semibold">2. Minggu Pertemuan</label>
                    <select name="week" class="form-select">
                        @foreach($weeks as $wNumber => $wLabel)
                            <option value="{{ $wNumber }}" @selected($week == $wNumber)>{{ $wLabel }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label fw-semibold">3. Hari</label>
                    <select name="day" class="form-select">
                        @foreach(['Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'] as $index => $dayName)
                            <option value="{{ $index + 1 }}" @selected($day == $index + 1)>{{ $dayName }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label fw-semibold">4. Laboratorium</label>
                    <select name="room_id" class="form-select">
                        <option value="">Semua Lab (13 Ruangan)</option>
                        @foreach($allRooms as $r)
                            <option value="{{ $r->id }}" @selected($selectedRoomId == $r->id)>{{ $r->code }}</option>
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
            <span class="d-inline-flex align-items-center gap-1">
                <span class="d-inline-block border" style="width: 18px; height: 18px; background-color: #FFF3B0;"></span>
                <span>S1 Teknik Informatika</span>
            </span>
            <span class="d-inline-flex align-items-center gap-1">
                <span class="d-inline-block border" style="width: 18px; height: 18px; background-color: #FBEFD1;"></span>
                <span>S1 Sistem Informasi</span>
            </span>
            <span class="d-inline-flex align-items-center gap-1">
                <span class="d-inline-block border" style="width: 18px; height: 18px; background-color: #FFB366;"></span>
                <span>S2 Magister Ilmu Komputer</span>
            </span>
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

@if($isExamPeriodWithoutSchedule)
    <div class="alert alert-warning mb-4">
        Minggu yang dipilih berada pada rentang UTS/UAS dan jadwal ujian belum tersedia.
    </div>
@endif

<div class="card shadow-sm border-0">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-bordered align-middle mb-0 text-center" style="font-size: 0.85rem;">
                <thead class="table-light">
                    <tr>
                        <th style="width: 90px;">Jam</th>
                        @foreach($rooms as $room)
                            <th>{{ $room->code }}</th>
                        @endforeach
                    </tr>
                </thead>
                <tbody>
                    @foreach($timeSlots as $slotTime)
                        <tr>
                            <td class="table-light fw-bold text-secondary py-2">{{ $slotTime }}</td>
                            @foreach($rooms as $room)
                                @php
                                    $cellSections = $sections->filter(function($s) use ($room, $slotTime) {
                                        return $s->room_id === $room->id
                                            && substr($s->start_time, 0, 5) <= $slotTime
                                            && substr($s->end_time, 0, 5) > $slotTime;
                                    });

                                    $cellBookings = $bookings->filter(function($b) use ($room, $slotTime) {
                                        return $b->room_id === $room->id
                                            && $b->start_datetime->format('H:i') <= $slotTime
                                            && $b->end_datetime->format('H:i') > $slotTime;
                                    });
                                @endphp

                                @if($cellSections->isNotEmpty())
                                    @php
                                        $sec = $cellSections->first();
                                        $isStart = substr($sec->start_time, 0, 5) === $slotTime;
                                    @endphp
                                    <td class="p-1" style="background-color: {{ $sec->course->studyProgram->color_hex ?? '#FFF3B0' }}; {{ $isStart ? '' : 'border-top: none;' }}">
                                        @if($isStart)
                                            <div class="fw-bold">
                                                @if($cellSections->count() > 1)
                                                    {{ $cellSections->pluck('course.name')->unique()->join(' / ') }}
                                                @else
                                                    {{ $sec->course->name }}
                                                @endif
                                            </div>
                                            <div class="small text-secondary" style="font-size: 0.72rem;">{{ substr($sec->start_time, 0, 5) }} - {{ substr($sec->end_time, 0, 5) }}</div>
                                            @if($isInternal && $sec->display_lecturer)
                                                <div class="small text-dark">{{ $sec->display_lecturer }}</div>
                                                @if($sec->class_code)
                                                    <span class="badge text-bg-light border" style="font-size: 0.68rem;">{{ $sec->class_code }}</span>
                                                @endif
                                            @endif
                                        @endif
                                    </td>
                                @elseif($cellBookings->isNotEmpty())
                                    @php
                                        $bk = $cellBookings->first();
                                        $isStart = $bk->start_datetime->format('H:i') === $slotTime;
                                    @endphp
                                    <td class="p-1" style="background-color: #BFE3F7; {{ $isStart ? '' : 'border-top: none;' }}">
                                        @if($isStart)
                                            @if($bk->status === \App\Constants\BookingDetailStatus::PENDING)
                                                <div class="fw-bold text-primary">Pending ({{ $bk->queue_position ?? 1 }})</div>
                                                <div class="small text-secondary" style="font-size: 0.70rem; line-height: 1.2;">Jika ingin meminjam pada jam yang sama akan masuk ke antrian selanjutnya</div>
                                                <div class="small text-muted" style="font-size: 0.70rem;">{{ $bk->start_datetime->format('H:i') }} - {{ $bk->end_datetime->format('H:i') }}</div>
                                                @if($isInternal)
                                                    <div class="small text-dark fw-semibold mt-1">{{ $bk->display_requester }}</div>
                                                @endif
                                            @else
                                                <div class="fw-bold text-dark">{{ $bk->display_requester }}</div>
                                                <div class="small text-secondary" style="font-size: 0.72rem;">{{ $bk->start_datetime->format('H:i') }} - {{ $bk->end_datetime->format('H:i') }}</div>
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
