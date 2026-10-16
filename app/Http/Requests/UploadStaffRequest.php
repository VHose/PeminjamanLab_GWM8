<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UploadStaffRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasRole('Kepala_Lab', 'Admin') ?? false;
    }

    public function rules(): array
    {
        return [
            'period_id' => ['required', 'exists:period,id'],
            'staff_file' => ['nullable', 'file', 'mimes:csv,txt', 'max:2048'],
            'staff_text' => ['nullable', 'string'],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($v) {
            if (!$this->hasFile('staff_file') && empty(trim($this->input('staff_text', '')))) {
                $v->errors()->add('staff_file', 'Silakan unggah berkas CSV atau tempel teks daftar staf.');
            }
        });
    }

    public function messages(): array
    {
        return [
            'period_id.required' => 'Periode semester wajib dipilih.',
            'staff_file.mimes' => 'Berkas harus berupa file CSV atau TXT.',
        ];
    }
}
