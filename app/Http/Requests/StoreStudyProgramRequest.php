<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreStudyProgramRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasRole('Admin') ?? false;
    }

    public function rules(): array
    {
        return [
            'id' => ['required', 'string', 'max:5', 'unique:study_program,id'],
            'name' => ['required', 'string', 'max:50', 'unique:study_program,name'],
            'color_hex' => ['nullable', 'string', 'max:7', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'is_active' => ['nullable', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'id.required' => 'Kode prodi wajib diisi (contoh: 72, 73, 79).',
            'id.unique' => 'Kode prodi sudah terdaftar.',
            'name.required' => 'Nama program studi wajib diisi.',
            'name.unique' => 'Nama program studi sudah terdaftar.',
        ];
    }
}
