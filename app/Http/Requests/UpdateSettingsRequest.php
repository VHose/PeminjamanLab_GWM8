<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateSettingsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasRole('Admin') ?? false;
    }

    public function rules(): array
    {
        return [
            'booking_block_minutes' => ['required', 'integer', 'in:15,30,45,60'],
            'lab_open_time' => ['required', 'date_format:H:i'],
            'lab_close_time' => ['required', 'date_format:H:i', 'after:lab_open_time'],
            'lab_open_time_saturday' => ['nullable', 'date_format:H:i'],
            'lab_close_time_saturday' => ['nullable', 'date_format:H:i', 'required_with:lab_open_time_saturday'],
            'booking_max_days_ahead' => ['required', 'integer', 'min:1', 'max:180'],
            'booking_max_duration_hours' => ['required', 'integer', 'min:1', 'max:12'],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($v) {
            $openSat = $this->input('lab_open_time_saturday');
            $closeSat = $this->input('lab_close_time_saturday');

            if ($openSat && $closeSat && $closeSat <= $openSat) {
                $v->errors()->add('lab_close_time_saturday', 'Jam tutup hari Sabtu harus lebih besar dari jam buka hari Sabtu.');
            }
        });
    }

    public function messages(): array
    {
        return [
            'booking_block_minutes.required' => 'Blok menit peminjaman wajib diisi.',
            'lab_open_time.required' => 'Jam buka lab (Senin-Jumat) wajib ditentukan.',
            'lab_close_time.required' => 'Jam tutup lab (Senin-Jumat) wajib ditentukan.',
            'lab_close_time.after' => 'Jam tutup lab harus setelah jam buka lab.',
            'booking_max_days_ahead.required' => 'Batas maksimal pemesanan hari ke depan wajib diisi.',
            'booking_max_duration_hours.required' => 'Durasi maksimal peminjaman (jam) wajib diisi.',
        ];
    }
}
