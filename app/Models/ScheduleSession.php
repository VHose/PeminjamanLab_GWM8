<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ScheduleSession extends Model
{
    protected $table = 'schedule_session';

    public $timestamps = false;

    public $incrementing = false;

    protected $primaryKey = ['schedule_id', 'week_no'];

    protected $fillable = [
        'schedule_id',
        'week_no',
        'session_date',
        'is_active',
        'room_id',
        'start_time',
        'end_time',
        'note',
    ];

    protected function casts(): array
    {
        return [
            'week_no' => 'integer',
            'session_date' => 'date',
            'is_active' => 'boolean',
        ];
    }

    protected function setKeysForSaveQuery($query)
    {
        $query->where('schedule_id', '=', $this->getAttribute('schedule_id'))
              ->where('week_no', '=', $this->getAttribute('week_no'));

        return $query;
    }

    public function schedule(): BelongsTo
    {
        return $this->belongsTo(Schedule::class, 'schedule_id');
    }

    public function room(): BelongsTo
    {
        return $this->belongsTo(Room::class, 'room_id');
    }

    public function effectiveRoomId(): string
    {
        return $this->room_id ?? $this->schedule->room_id;
    }

    public function effectiveStartTime(): string
    {
        return $this->start_time ?? $this->schedule->start_time;
    }

    public function effectiveEndTime(): string
    {
        return $this->end_time ?? $this->schedule->end_time;
    }
}
