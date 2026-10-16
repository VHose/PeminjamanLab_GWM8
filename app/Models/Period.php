<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Period extends Model
{
    protected $table = 'period';

    protected $fillable = [
        'name',
        'start_date',
        'end_date',
        'uts_start',
        'uts_end',
        'uas_start',
        'uas_end',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'start_date' => 'date',
            'end_date' => 'date',
            'uts_start' => 'date',
            'uts_end' => 'date',
            'uas_start' => 'date',
            'uas_end' => 'date',
            'is_active' => 'boolean',
        ];
    }

    public function holidays(): HasMany
    {
        return $this->hasMany(PeriodHoliday::class, 'period_id');
    }

    public function schedules(): HasMany
    {
        return $this->hasMany(Schedule::class, 'period_id');
    }

    public function userRoles(): HasMany
    {
        return $this->hasMany(UserRole::class, 'period_id');
    }
}
