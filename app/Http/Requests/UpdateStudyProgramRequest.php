<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateStudyProgramRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasRole('Admin') ?? false;
    }

    public function rules(): array
    {
        $id = $this->route('study_program')?->id ?? $this->route('id');

        return [
            'name' => ['required', 'string', 'max:50', Rule::unique('study_program', 'name')->ignore($id, 'id')],
            'color_hex' => ['nullable', 'string', 'max:7', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'is_active' => ['nullable', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'Nama program studi wajib diisi.',
            'name.unique' => 'Nama program studi sudah terdaftar.',
        ];
    }
}
