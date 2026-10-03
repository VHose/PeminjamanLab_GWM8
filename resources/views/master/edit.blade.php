@extends('layouts.app')

@section('content')
<div class="row justify-content-center">
    <div class="col-lg-8">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h1 class="h3 fw-bold mb-1">Ubah {{ $config['label'] }}</h1>
                <p class="text-secondary mb-0">Perbarui data master yang tersimpan di sistem.</p>
            </div>
            <a class="btn btn-outline-secondary" href="{{ route('master.index', $type) }}">Kembali</a>
        </div>

        <div class="card shadow-sm border-0">
            <div class="card-body p-4">
                <form method="post" action="{{ route('master.update', [$type, $record->getKey()]) }}">
                    @csrf
                    @method('PUT')

                    @foreach($config['fields'] as $field => $label)
                        <div class="mb-3">
                            <label class="form-label fw-semibold">{{ $label }}</label>

                            @if($field === 'study_program_id')
                                <select class="form-select" name="{{ $field }}" required>
                                    @foreach($studyPrograms as $program)
                                        <option value="{{ $program->id }}" @selected(old($field, $record->$field) == $program->id)>
                                            {{ $program->code }} — {{ $program->name }}
                                        </option>
                                    @endforeach
                                </select>
                            @elseif($field === 'semester')
                                <select class="form-select" name="semester" required>
                                    <option value="odd" @selected(old('semester', $record->semester) === 'odd')>Ganjil</option>
                                    <option value="even" @selected(old('semester', $record->semester) === 'even')>Genap</option>
                                </select>
                            @elseif($field === 'active')
                                <input type="hidden" name="active" value="0">
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" name="active" value="1" id="activeCheck" @checked(old('active', $record->active))>
                                    <label class="form-check-label" for="activeCheck">Status Aktif</label>
                                </div>
                            @elseif($field === 'description')
                                <textarea class="form-control" name="description" rows="3">{{ old($field, $record->$field) }}</textarea>
                            @elseif($field === 'color_hex')
                                <input class="form-control form-control-color" type="color" name="color_hex" value="{{ old('color_hex', $record->$field) }}">
                            @elseif(in_array($field, ['start_date', 'end_date', 'uts_start', 'uts_end', 'uas_start', 'uas_end'], true))
                                <input class="form-control" type="date" name="{{ $field }}" value="{{ old($field, $record->$field ? \Carbon\Carbon::parse($record->$field)->format('Y-m-d') : '') }}">
                            @elseif($field === 'capacity')
                                <input class="form-control" type="number" min="1" name="capacity" value="{{ old('capacity', $record->$field) }}">
                            @elseif($field === 'email')
                                <input class="form-control" type="email" name="email" value="{{ old('email', $record->$field) }}">
                            @else
                                @php
                                    $isReadonly = ($type === 'lecturers' && $field === 'nik') || ($type === 'rooms' && $field === 'code');
                                @endphp
                                <input class="form-control" type="text" name="{{ $field }}" value="{{ old($field, $record->$field) }}" @readonly($isReadonly)>
                                @if($isReadonly)
                                    <div class="form-text text-muted">Field kunci utama ini tidak dapat diubah setelah dibuat.</div>
                                @endif
                            @endif
                        </div>
                    @endforeach

                    <div class="d-flex justify-content-end gap-2 mt-4">
                        <a class="btn btn-light border" href="{{ route('master.index', $type) }}">Batal</a>
                        <button type="submit" class="btn btn-primary">Simpan Perubahan</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
