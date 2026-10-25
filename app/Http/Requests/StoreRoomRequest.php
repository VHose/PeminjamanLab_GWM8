<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreRoomRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasRole('Admin') ?? false;
    }

    public function rules(): array
    {
        return [
            'id' => ['required', 'string', 'max:8', 'unique:room,id'],
            'name' => ['required', 'string', 'max:25'],
            'capacity' => ['nullable', 'integer', 'min:1', 'max:200'],
            'is_active' => ['nullable', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'id.required' => 'Kode ruangan wajib diisi (contoh: ADV1, PROG1).',
            'id.unique' => 'Kode ruangan sudah terdaftar.',
            'name.required' => 'Nama ruangan wajib diisi.',
        ];
    }
}
