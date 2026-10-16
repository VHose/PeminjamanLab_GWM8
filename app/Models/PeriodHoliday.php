<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PeriodHoliday extends Model
{
    protected $table = 'period_holiday';

    public $timestamps = false;

    public $incrementing = false;

    protected $primaryKey = ['period_id', 'start_date'];

    protected $fillable = [
        'period_id',
        'start_date',
        'end_date',
        'note',
    ];

    protected function casts(): array
    {
        return [
            'period_id' => 'integer',
            'start_date' => 'date',
            'end_date' => 'date',
        ];
    }

    protected function setKeysForSaveQuery($query)
    {
        $startDate = $this->getAttribute('start_date');
        if ($startDate instanceof \DateTimeInterface) {
            $startDate = $startDate->format('Y-m-d');
        }

        $query->where('period_id', '=', $this->getAttribute('period_id'))
              ->whereDate('start_date', '=', $startDate);

        return $query;
    }

    public function period(): BelongsTo
    {
        return $this->belongsTo(Period::class, 'period_id');
    }
}
