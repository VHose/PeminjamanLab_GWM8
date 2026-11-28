<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ScheduleSession extends Model
{
    protected $table = 'schedule_session';

    public $timestamps = true;

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

    protected function getKeyForSelectQuery()
    {
        return $this->getAttribute('schedule_id');
    }

    public function fresh($with = [])
    {
        if (! $this->exists) {
            return null;
        }

        return static::with(is_string($with) ? func_get_args() : $with)
            ->where('schedule_id', $this->schedule_id)
            ->where('week_no', $this->week_no)
            ->first();
    }

    public function refresh()
    {
        if (! $this->exists) {
            return $this;
        }

        $fresh = static::where('schedule_id', $this->schedule_id)
            ->where('week_no', $this->week_no)
            ->first();

        if ($fresh) {
            $this->setRawAttributes($fresh->getAttributes(), true);
            $this->load(array_keys($this->relations));
        }

        return $this;
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

    /**
     * Mengembalikan seluruh schedule_id dalam kelompok kelas gabungan (Gabung) yang sama.
     *
     * @return array<int, int>
     */
    public function combinedScheduleIds(): array
    {
        $sched = $this->schedule;
        if (! $sched) {
            return [(int) $this->schedule_id];
        }

        return Schedule::where('period_id', $sched->period_id)
            ->where('day_of_week', $sched->day_of_week)
            ->where('room_id', $sched->room_id)
            ->where('start_time', $sched->start_time)
            ->where('end_time', $sched->end_time)
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->all();
    }

    /**
     * Mengembalikan seluruh sesi pertemuan (termasuk sesi ini) dalam kelompok kelas gabungan pada minggu yang sama.
     */
    public function combinedSessions()
    {
        $ids = $this->combinedScheduleIds();

        return static::with(['schedule.course', 'schedule.lecturer', 'schedule.room', 'room'])
            ->whereIn('schedule_id', $ids)
            ->where('week_no', $this->week_no)
            ->orderBy('schedule_id')
            ->get();
    }

    /**
     * Mengembalikan tanggal sesi default berdasarkan jadwal induk dan kalender periode.
     */
    public function defaultSessionDate(): ?string
    {
        $period = $this->schedule?->period;
        if (! $period) {
            return null;
        }

        static $calendarCache = [];
        $periodId = $period->id;

        if (! isset($calendarCache[$periodId])) {
            $calendarService = app(\App\Services\PeriodWeekCalendar::class);
            $holidays = $period->holidays()->get(['start_date', 'end_date', 'note'])->toArray();
            $calendar = $calendarService->buildCalendar(
                $period->start_date->toDateString(),
                $period->end_date->toDateString(),
                $period->uts_start?->toDateString(),
                $period->uts_end?->toDateString(),
                $period->uas_start?->toDateString(),
                $period->uas_end?->toDateString(),
                $holidays
            );
            $calendarCache[$periodId] = array_values(array_filter($calendar, fn ($w) => $w['type'] === \App\Enums\WeekType::KULIAH));
        }

        $kuliahWeeks = $calendarCache[$periodId];
        $targetWeek = collect($kuliahWeeks)->firstWhere('meeting_no', $this->week_no);

        if (! $targetWeek) {
            return null;
        }

        $dayIndex = ($this->schedule?->day_of_week ?? 1) - 1;
        return $targetWeek['days'][$dayIndex]['date'] ?? null;
    }
}
