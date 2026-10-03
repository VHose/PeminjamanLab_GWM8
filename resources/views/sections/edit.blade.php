@extends('layouts.app')

@section('content')
<div class="row justify-content-center">
    <div class="col-lg-8">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h1 class="h3 fw-bold mb-1">Ubah Jadwal Kelas</h1>
                <p class="text-secondary mb-0">Perbarui informasi jadwal kelas reguler.</p>
            </div>
            <a class="btn btn-outline-secondary" href="{{ route('sections.index') }}">Kembali</a>
        </div>

        <div class="card shadow-sm border-0">
            <div class="card-body p-4">
                <form method="post" action="{{ route('sections.update', $section) }}">
                    @csrf
                    @method('PUT')

                    <div class="mb-3">
                        <label class="form-label fw-semibold">Periode Semester <span class="text-danger">*</span></label>
                        <select class="form-select" name="period_id" required>
                            @foreach($periods as $period)
                                <option value="{{ $period->id }}" @selected(old('period_id', $section->period_id) == $period->id)>{{ $period->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold">Mata Kuliah <span class="text-danger">*</span></label>
                        <select class="form-select" name="course_id" required>
                            @foreach($courses as $course)
                                <option value="{{ $course->id }}" @selected(old('course_id', $section->course_id) == $course->id)>
                                    [{{ $course->studyProgram->code ?? 'PRODI' }}] {{ $course->code }} — {{ $course->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Dosen Pengampu</label>
                            <select class="form-select" name="lecturer_nik" id="lecturerSelect">
                                <option value="">Dosen belum terdata / lainnya</option>
                                @foreach($lecturers as $lecturer)
                                    <option value="{{ $lecturer->nik }}" @selected(old('lecturer_nik', $section->lecturer_nik) == $lecturer->nik)>
                                        {{ $lecturer->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Nama Dosen / Praktisi (Jika belum terdata)</label>
                            <input class="form-control" name="presenter_name" id="presenterInput" value="{{ old('presenter_name', $section->presenter_name) }}" placeholder="Tulis nama dosen/pemateri...">
                        </div>
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-md-4">
                            <label class="form-label fw-semibold">Ruangan Laboratorium <span class="text-danger">*</span></label>
                            <select class="form-select" name="room_id" required>
                                @foreach($rooms as $room)
                                    <option value="{{ $room->id }}" @selected(old('room_id', $section->room_id) == $room->id)>{{ $room->code }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-semibold">Kode Kelas</label>
                            <input class="form-control" name="class_code" value="{{ old('class_code', $section->class_code) }}" placeholder="Contoh: IF-A / Kelas 1">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-semibold">Jenis Kelas</label>
                            <input class="form-control" name="class_type" value="{{ old('class_type', $section->class_type) }}" placeholder="Contoh: Teori / Praktikum">
                        </div>
                    </div>

                    <div class="row g-3 mb-4">
                        <div class="col-md-4">
                            <label class="form-label fw-semibold">Hari <span class="text-danger">*</span></label>
                            <select class="form-select" name="day_of_week" required>
                                @foreach(['Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'] as $idx => $d)
                                    <option value="{{ $idx + 1 }}" @selected(old('day_of_week', $section->day_of_week) == $idx + 1)>{{ $d }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-semibold">Jam Mulai <span class="text-danger">*</span></label>
                            <select class="form-select" name="start_time" required>
                                @php
                                    $curStart = substr($section->start_time, 0, 5);
                                @endphp
                                @for($h = 7; $h <= 21; $h++)
                                    @foreach([0, 30] as $m)
                                        @php
                                            $t = sprintf('%02d:%02d', $h, $m);
                                        @endphp
                                        <option value="{{ $t }}" @selected(old('start_time', $curStart) === $t)>{{ $t }}</option>
                                    @endforeach
                                @endfor
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-semibold">Jam Selesai <span class="text-danger">*</span></label>
                            <select class="form-select" name="end_time" required>
                                @php
                                    $curEnd = substr($section->end_time, 0, 5);
                                @endphp
                                @for($h = 7; $h <= 22; $h++)
                                    @foreach([0, 30] as $m)
                                        @if($h === 22 && $m === 30) @continue @endif
                                        @php
                                            $t = sprintf('%02d:%02d', $h, $m);
                                        @endphp
                                        <option value="{{ $t }}" @selected(old('end_time', $curEnd) === $t)>{{ $t }}</option>
                                    @endforeach
                                @endfor
                            </select>
                        </div>
                    </div>

                    <div class="d-flex justify-content-end gap-2">
                        <a class="btn btn-light border" href="{{ route('sections.index') }}">Batal</a>
                        <button type="submit" class="btn btn-primary">Simpan Perubahan</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', () => {
    const lecturerSelect = document.getElementById('lecturerSelect');
    const presenterInput = document.getElementById('presenterInput');

    function togglePresenter() {
        if (lecturerSelect.value !== '') {
            presenterInput.disabled = true;
            presenterInput.value = '';
        } else {
            presenterInput.disabled = false;
        }
    }

    lecturerSelect.addEventListener('change', togglePresenter);
    togglePresenter();
});
</script>
@endpush
@endsection
