<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CopyPeriodRolesRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasRole('Kepala_Lab', 'Admin') ?? false;
    }

    public function rules(): array
    {
        return [
            'from_period_id' => ['required', 'exists:period,id'],
            'to_period_id' => ['required', 'exists:period,id', 'different:from_period_id'],
        ];
    }

    public function messages(): array
    {
        return [
            'from_period_id.required' => 'Periode asal wajib dipilih.',
            'to_period_id.required' => 'Periode tujuan wajib dipilih.',
            'to_period_id.different' => 'Periode asal dan tujuan harus berbeda.',
        ];
    }
}
