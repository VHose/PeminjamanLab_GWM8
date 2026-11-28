<?php

namespace App\Models;

use App\Enums\ScheduleType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Schedule extends Model
{
    protected $table = 'schedule';

    protected $attributes = [
        'sks' => '0',
    ];

    protected $fillable = [
        'period_id',
        'course_id',
        'class_code',
        'type',
        'day_of_week',
        'user_id',
        'room_id',
        'start_time',
        'end_time',
        'notes',
        'sks',
    ];

    protected function casts(): array
    {
        return [
            'type' => ScheduleType::class,
            'day_of_week' => 'integer',
        ];
    }

    public function period(): BelongsTo
    {
        return $this->belongsTo(Period::class, 'period_id');
    }

    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class, 'course_id');
    }

    public function lecturer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function room(): BelongsTo
    {
        return $this->belongsTo(Room::class, 'room_id');
    }

    public function sessions(): HasMany
    {
        return $this->hasMany(ScheduleSession::class, 'schedule_id');
    }

    /**
     * Mengembalikan seluruh jadwal kuliah yang digabung (slot induk periode, hari, ruangan, dan jam yang sama).
     */
    public function combinedSchedules()
    {
        return static::with(['course', 'lecturer', 'room'])
            ->where('period_id', $this->period_id)
            ->where('day_of_week', $this->day_of_week)
            ->where('room_id', $this->room_id)
            ->where('start_time', $this->start_time)
            ->where('end_time', $this->end_time)
            ->orderBy('id')
            ->get();
    }
}
