<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Section extends Model
{
    protected $table = 'section';

    protected $fillable = ['course_id', 'period_id', 'room_id', 'lecturer_nik', 'presenter_name', 'day_of_week', 'start_time', 'end_time'];

    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }

    public function period(): BelongsTo
    {
        return $this->belongsTo(Period::class);
    }

    public function room(): BelongsTo
    {
        return $this->belongsTo(Room::class);
    }

    public function lecturer(): BelongsTo
    {
        return $this->belongsTo(Lecturer::class, 'lecturer_nik', 'nik');
    }
}
