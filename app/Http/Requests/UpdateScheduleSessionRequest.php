<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class UpdateScheduleSessionRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()?->hasRole('Admin') ?? false;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'session_date'   => ['nullable', 'date_format:Y-m-d'],
            'room_id'        => ['nullable', 'string', 'exists:room,id', 'max:8'],
            'start_time'     => ['nullable', 'date_format:H:i'],
            'end_time'       => ['nullable', 'date_format:H:i'],
            'note'           => ['nullable', 'string', 'max:100'],
            'clear_override' => ['nullable', 'boolean'],
        ];
    }

    /**
     * Configure the validator instance.
     */
    public function withValidator(Validator $validator): void
    {
        $validator->after(function ($validator) {
            if ($this->boolean('clear_override')) {
                return;
            }

            if ($this->filled('session_date')) {
                $targetDate = \Carbon\Carbon::parse($this->input('session_date'));
                if ($targetDate->isSunday()) {
                    $validator->errors()->add('session_date', 'Pertemuan kuliah tidak dapat dipindahkan ke hari Minggu.');
                }

                $session = \App\Models\ScheduleSession::with('schedule.period')
                    ->where('schedule_id', $this->route('scheduleId'))
                    ->where('week_no', $this->route('weekNo'))
                    ->first();

                if ($session && $session->schedule?->period) {
                    $periodStart = $session->schedule->period->start_date;
                    $periodEnd = $session->schedule->period->end_date;
                    if ($targetDate->lt($periodStart) || $targetDate->gt($periodEnd)) {
                        $validator->errors()->add('session_date', "Tanggal harus berada dalam rentang periode akademik ({$periodStart->format('d/m/Y')} - {$periodEnd->format('d/m/Y')}).");
                    }
                }
            }

            $startTime = $this->input('start_time');
            $endTime = $this->input('end_time');

            // Jika salah satu dari start_time atau end_time diisi, keduanya harus diisi
            if (($startTime && ! $endTime) || (! $startTime && $endTime)) {
                $validator->errors()->add('time', 'Jam mulai dan jam selesai harus diisi bersamaan.');
            }

            // end_time harus lebih besar dari start_time jika keduanya diisi
            if ($startTime && $endTime && $endTime <= $startTime) {
                $validator->errors()->add('end_time', 'Jam selesai harus lebih besar dari jam mulai.');
            }
        });
    }

    /**
     * Get custom error messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'session_date.date_format' => 'Format tanggal tidak valid (gunakan format YYYY-MM-DD).',
            'room_id.exists'         => 'Ruangan yang dipilih tidak valid atau tidak ditemukan.',
            'room_id.max'            => 'ID ruangan tidak boleh melebihi 8 karakter.',
            'start_time.date_format' => 'Format jam mulai tidak valid (gunakan format HH:mm, contoh 08:00).',
            'end_time.date_format'   => 'Format jam selesai tidak valid (gunakan format HH:mm, contoh 10:00).',
            'end_time.after'         => 'Jam selesai harus lebih besar dari jam mulai.',
            'note.max'               => 'Catatan override maksimal 100 karakter.',
        ];
    }
}
