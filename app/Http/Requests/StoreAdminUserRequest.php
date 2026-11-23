<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreAdminUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasRole('Admin') || $this->user()?->hasRole('Kepala_Lab');
    }

    public function rules(): array
    {
        return [
            'id' => ['required', 'string', 'max:10', 'unique:user,id'],
            'name' => ['required', 'string', 'max:80'],
            'email' => ['required', 'email', 'max:70', 'unique:user,email'],
            'phone' => ['nullable', 'string', 'max:15'],
            'study_program_id' => ['nullable', 'exists:study_program,id'],
            'role_id' => ['nullable', 'exists:role,id'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ];
    }

    public function messages(): array
    {
        return [
            'id.required' => 'NIK/ID Admin wajib diisi.',
            'id.unique' => 'NIK/ID sudah terdaftar.',
            'email.required' => 'Email wajib diisi.',
            'email.unique' => 'Email sudah terdaftar.',
            'password.required' => 'Password wajib diisi.',
            'password.min' => 'Password minimal 8 karakter.',
            'password.confirmed' => 'Konfirmasi password tidak cocok.',
        ];
    }
}
