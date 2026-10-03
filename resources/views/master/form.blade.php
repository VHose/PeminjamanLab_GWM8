@extends('layouts.app')
@section('content')
<h1 class="h3">{{ $record ? 'Ubah' : 'Tambah' }} {{ $config['label'] }}</h1>
<form class="card card-body" method="post" action="{{ $record ? route('master.update', [$type, $record->getKey()]) : route('master.store', $type) }}">
@csrf @if($record) @method('PUT') @endif
@foreach($config['fields'] as $field => $label)
<label class="form-label">{{ $label }}</label>
@if($field === 'study_program_id')
<select class="form-select mb-3" name="{{ $field }}" required>@foreach($studyPrograms as $program)<option value="{{ $program->id }}" @selected(old($field, $record?->$field) == $program->id)>{{ $program->name }}</option>@endforeach</select>
@elseif($field === 'semester')
<select class="form-select mb-3" name="semester"><option value="odd" @selected(old('semester',$record?->semester)==='odd')>Ganjil</option><option value="even" @selected(old('semester',$record?->semester)==='even')>Genap</option></select>
@elseif($field === 'active')
<input type="hidden" name="active" value="0"><div class="form-check mb-3"><input class="form-check-input" type="checkbox" name="active" value="1" @checked(old('active',$record?->active ?? true))><label class="form-check-label">Aktif</label></div>
@elseif($field === 'description')
<textarea class="form-control mb-3" name="description">{{ old($field,$record?->$field) }}</textarea>
@else
<input class="form-control mb-3" type="{{ in_array($field,['start_date','end_date','uts_start','uts_end','uas_start','uas_end']) ? 'date' : ($field === 'color_hex' ? 'color' : ($field === 'email' ? 'email' : 'text')) }}" name="{{ $field }}" value="{{ old($field,$record?->$field) }}" @readonly($record && (($type === 'lecturers' && $field === 'nik') || ($type === 'rooms' && $field === 'code')))>
@endif
@endforeach
<button class="btn btn-primary">Simpan</button></form>
@endsection
