<?php

namespace App\Models;

/**
 * Backward compatibility alias for Schedule
 */
class Section extends Schedule
{
    protected $table = 'schedule';

    protected $fillable = [
        'period_id',
        'course_id',
        'class_code',
        'type',
        'schedule_type',
        'day_of_week',
        'user_id',
        'lecturer_nik',
        'room_id',
        'start_time',
        'end_time',
    ];

    public function setLecturerNikAttribute($value): void
    {
        $this->attributes['user_id'] = $value;
    }

    public function setScheduleTypeAttribute($value): void
    {
        $this->attributes['type'] = $value;
    }

    /** Map legacy attribute name */
    public function getScheduleTypeAttribute()
    {
        return $this->type?->value ?? 0;
    }
}
