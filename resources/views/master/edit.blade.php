@extends('layouts.app')

@section('content')
<div class="row justify-content-center">
    <div class="col-lg-8">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h1 class="h3 fw-bold mb-1">Ubah {{ $config['label'] }}: {{ $record->id }}</h1>
                <p class="text-secondary mb-0">Perbarui data master sistem.</p>
            </div>
            <a class="btn btn-outline-secondary" href="{{ route('master.index', $type) }}">Kembali</a>
        </div>

        <div class="card shadow-sm border-0">
            <div class="card-body p-4">
                <form method="post" action="{{ route('master.update', [$type, $record->id]) }}">
                    @csrf
                    @method('PUT')

                    <div class="mb-3">
                        <label class="form-label fw-semibold">{{ $config['pk_label'] }}</label>
                        <input type="text" class="form-control bg-light" value="{{ $record->id }}" disabled readonly>
                        <div class="form-text">Kode identitas natural tidak dapat diubah setelah dibuat.</div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold">Nama {{ $config['label'] }} <span class="text-danger">*</span></label>
                        <input type="text" name="name" class="form-control" value="{{ old('name', $record->name) }}" required maxlength="100">
                    </div>

                    @if($type === 'study-programs')
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Warna Jadwal <span class="text-danger">*</span></label>
                            <div class="d-flex align-items-center gap-2">
                                <input type="color" name="color_hex" class="form-control form-control-color" value="{{ old('color_hex', $record->color_hex ?? '#E2E3E5') }}" title="Pilih warna untuk jadwal prodi ini">
                                <input type="text" class="form-control" style="max-width:120px;" value="{{ old('color_hex', $record->color_hex ?? '#E2E3E5') }}" readonly id="colorHexText">
                            </div>
                            <div class="form-text">Warna yang tampil di grid jadwal untuk mata kuliah prodi ini.</div>
                        </div>
                    @endif

                    @if($type === 'rooms')
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Kapasitas (Orang)</label>
                            <input type="number" name="capacity" class="form-control" value="{{ old('capacity', $record->capacity) }}" min="1" max="200">
                        </div>
                    @elseif($type === 'courses')
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Program Studi <span class="text-danger">*</span></label>
                            <select name="study_program_id" class="form-select" required>
                                @foreach($studyPrograms as $sp)
                                    <option value="{{ $sp->id }}" @selected(old('study_program_id', $record->study_program_id) == $sp->id)>
                                        {{ $sp->id }} — {{ $sp->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    @endif

                    <div class="form-check form-switch mb-4">
                        <input class="form-check-input" type="checkbox" name="is_active" value="1" id="activeCheck" @checked(old('is_active', $record->is_active))>
                        <label class="form-check-label fw-semibold" for="activeCheck">Status Aktif</label>
                    </div>

                    <div class="d-flex justify-content-end gap-2">
                        <a class="btn btn-outline-secondary" href="{{ route('master.index', $type) }}">Batal</a>
                        <button type="submit" class="btn btn-primary px-4">Simpan Perubahan</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection

@if($type === 'study-programs')
@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const picker = document.querySelector('input[name="color_hex"]');
    const text = document.getElementById('colorHexText');
    if (picker && text) {
        picker.addEventListener('input', function() {
            text.value = this.value.toUpperCase();
        });
    }
});
</script>
@endpush
@endif
