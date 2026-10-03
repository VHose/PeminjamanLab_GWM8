<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Period extends Model
{
    protected $table = 'period';

    protected $fillable = ['name', 'semester', 'start_date', 'end_date', 'uts_start', 'uts_end', 'uas_start', 'uas_end'];

    protected function casts(): array
    {
        return [
            'start_date' => 'date',
            'end_date' => 'date',
            'uts_start' => 'date',
            'uts_end' => 'date',
            'uas_start' => 'date',
            'uas_end' => 'date',
        ];
    }

    public function sections(): HasMany
    {
        return $this->hasMany(Section::class);
    }
}
