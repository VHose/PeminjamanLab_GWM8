<?php

namespace App\Models;

use App\Constants\ScheduleType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Section extends Model
{
    protected $table = 'section';

    protected $fillable = [
        'course_id',
        'period_id',
        'room_id',
        'lecturer_nik',
        'class_code',
        'day_of_week',
        'start_time',
        'end_time',
        'schedule_type',
        'quota',
    ];

    protected function casts(): array
    {
        return [
            'day_of_week' => 'integer',
            'schedule_type' => 'integer',
            'quota' => 'integer',
        ];
    }

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

    /** lecturer_nik now FK → user.id */
    public function lecturer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'lecturer_nik');
    }

    public function isExam(): bool
    {
        return $this->schedule_type === ScheduleType::EXAM;
    }
}
