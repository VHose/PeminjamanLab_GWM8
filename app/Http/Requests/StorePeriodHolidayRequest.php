<?php

namespace App\Http\Requests;

use App\Models\Period;
use App\Models\PeriodHoliday;
use Illuminate\Foundation\Http\FormRequest;

class StorePeriodHolidayRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasRole('Admin') ?? false;
    }

    public function rules(): array
    {
        return [
            'period_id' => ['required', 'exists:period,id'],
            'start_date' => ['required', 'date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
            'note' => ['nullable', 'string', 'max:60'],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($v) {
            $periodId = $this->input('period_id');
            $startDate = $this->input('start_date');
            $endDate = $this->input('end_date') ?: $startDate;

            if (! $periodId || ! $startDate) {
                return;
            }

            $period = Period::find($periodId);
            if (! $period) {
                return;
            }

            // Pastikan libur berada di dalam rentang periode
            if ($startDate < $period->start_date->toDateString() || $endDate > $period->end_date->toDateString()) {
                $v->errors()->add('start_date', "Tanggal libur harus berada di dalam rentang periode ({$period->start_date->format('d/m/Y')} - {$period->end_date->format('d/m/Y')}).");
            }

            // Validasi tidak overlap dengan libur lain di periode yang sama
            $originalStartDate = $this->input('original_start_date'); // jika update
            $overlapQuery = PeriodHoliday::where('period_id', $periodId)
                ->where(function ($q) use ($startDate, $endDate) {
                    $q->where('start_date', '<=', $endDate)
                      ->where('end_date', '>=', $startDate);
                });

            if ($originalStartDate) {
                $overlapQuery->where('start_date', '!=', $originalStartDate);
            }

            if ($overlapQuery->exists()) {
                $v->errors()->add('start_date', 'Rentang tanggal libur bertabrakan (overlap) dengan hari libur lain pada periode ini.');
            }
        });
    }

    public function messages(): array
    {
        return [
            'period_id.required' => 'Periode wajib dipilih.',
            'start_date.required' => 'Tanggal mulai libur wajib diisi.',
            'end_date.after_or_equal' => 'Tanggal selesai libur tidak boleh lebih awal dari tanggal mulai libur.',
        ];
    }
}
