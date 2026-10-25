<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateCourseRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasRole('Admin') ?? false;
    }

    public function rules(): array
    {
        return [
            'study_program_id' => ['required', 'exists:study_program,id'],
            'name' => ['required', 'string', 'max:60'],
            'is_active' => ['nullable', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'study_program_id.required' => 'Program studi wajib dipilih.',
            'name.required' => 'Nama mata kuliah wajib diisi.',
        ];
    }
}
