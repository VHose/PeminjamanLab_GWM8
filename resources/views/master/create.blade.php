@extends('layouts.app')

@section('content')
<div class="row justify-content-center">
    <div class="col-lg-8">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h1 class="h3 fw-bold mb-1">Tambah {{ $config['label'] }}</h1>
                <p class="text-secondary mb-0">Tambahkan data master baru ke dalam sistem.</p>
            </div>
            <a class="btn btn-outline-secondary" href="{{ route('master.index', $type) }}">Kembali</a>
        </div>

        <div class="card shadow-sm border-0">
            <div class="card-body p-4">
                <form method="post" action="{{ route('master.store', $type) }}">
                    @csrf

                    @foreach($config['fields'] as $field => $label)
                        <div class="mb-3">
                            <label class="form-label fw-semibold">{{ $label }}</label>

                            @if($field === 'study_program_id')
                                <select class="form-select" name="{{ $field }}" required>
                                    <option value="">-- Pilih Program Studi --</option>
                                    @foreach($studyPrograms as $program)
                                        <option value="{{ $program->id }}" @selected(old($field) == $program->id)>
                                            {{ $program->code }} — {{ $program->name }}
                                        </option>
                                    @endforeach
                                </select>
                            @elseif($field === 'active' || $field === 'is_active')
                                <input type="hidden" name="{{ $field }}" value="0">
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" name="{{ $field }}" value="1" id="activeCheck" @checked(old($field, true))>
                                    <label class="form-check-label" for="activeCheck">Status Aktif</label>
                                </div>
                            @elseif($field === 'description')
                                <textarea class="form-control" name="description" rows="3" placeholder="Deskripsi atau fasilitas ruangan...">{{ old($field) }}</textarea>
                            @elseif($field === 'color_hex')
                                <input class="form-control form-control-color" type="color" name="color_hex" value="{{ old('color_hex', '#FFF3B0') }}" title="Pilih warna">
                            @elseif(in_array($field, ['start_date', 'end_date', 'uts_start', 'uts_end', 'uas_start', 'uas_end'], true))
                                <input class="form-control" type="date" name="{{ $field }}" value="{{ old($field) }}">
                            @elseif($field === 'capacity')
                                <input class="form-control" type="number" min="1" name="capacity" value="{{ old('capacity') }}" placeholder="Jumlah kapasitas orang">
                            @elseif($field === 'email')
                                <input class="form-control" type="email" name="email" value="{{ old('email') }}" placeholder="email@example.com">
                            @else
                                <input class="form-control" type="text" name="{{ $field }}" value="{{ old($field) }}" placeholder="Masukkan {{ strtolower($label) }}...">
                            @endif
                        </div>
                    @endforeach

                    <div class="d-flex justify-content-end gap-2 mt-4">
                        <a class="btn btn-light border" href="{{ route('master.index', $type) }}">Batal</a>
                        <button type="submit" class="btn btn-primary">Simpan Data</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
