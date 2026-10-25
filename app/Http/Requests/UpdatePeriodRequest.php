<?php

namespace App\Http\Requests;

use Carbon\Carbon;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdatePeriodRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasRole('Admin') ?? false;
    }

    public function rules(): array
    {
        $periodId = $this->route('period')?->id ?? $this->route('id');

        return [
            'name' => ['required', 'string', 'max:20', Rule::unique('period', 'name')->ignore($periodId)],
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date', 'after:start_date'],
            'uts_start' => ['nullable', 'date'],
            'uts_end' => ['nullable', 'date', 'required_with:uts_start', 'after_or_equal:uts_start'],
            'uas_start' => ['nullable', 'date'],
            'uas_end' => ['nullable', 'date', 'required_with:uas_start', 'after_or_equal:uas_start'],
            'is_active' => ['nullable', 'boolean'],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($v) {
            $startDate = $this->input('start_date');
            $endDate = $this->input('end_date');
            $utsStart = $this->input('uts_start');
            $utsEnd = $this->input('uts_end');
            $uasStart = $this->input('uas_start');
            $uasEnd = $this->input('uas_end');

            if ($startDate) {
                $startCarbon = Carbon::parse($startDate);
                if ($startCarbon->dayOfWeekIso !== 1) {
                    $v->errors()->add('start_date', 'Tanggal mulai periode (start_date) wajib jatuh pada hari Senin.');
                }
            }

            if ($startDate && $endDate) {
                if ($utsStart && ($utsStart < $startDate || $utsStart > $endDate)) {
                    $v->errors()->add('uts_start', 'Tanggal mulai UTS harus berada di dalam rentang periode.');
                }
                if ($utsEnd && ($utsEnd < $startDate || $utsEnd > $endDate)) {
                    $v->errors()->add('uts_end', 'Tanggal selesai UTS harus berada di dalam rentang periode.');
                }
                if ($uasStart && ($uasStart < $startDate || $uasStart > $endDate)) {
                    $v->errors()->add('uas_start', 'Tanggal mulai UAS harus berada di dalam rentang periode.');
                }
                if ($uasEnd && ($uasEnd < $startDate || $uasEnd > $endDate)) {
                    $v->errors()->add('uas_end', 'Tanggal selesai UAS harus berada di dalam rentang periode.');
                }
                if ($utsEnd && $uasStart && $uasStart <= $utsEnd) {
                    $v->errors()->add('uas_start', 'Rentang UAS harus dimulai setelah rentang UTS selesai.');
                }
            }
        });
    }

    public function messages(): array
    {
        return [
            'name.required' => 'Nama periode wajib diisi.',
            'name.unique' => 'Nama periode sudah digunakan.',
            'start_date.required' => 'Tanggal mulai periode wajib diisi.',
            'end_date.required' => 'Tanggal akhir periode wajib diisi.',
            'end_date.after' => 'Tanggal akhir periode harus setelah tanggal mulai.',
        ];
    }
}
