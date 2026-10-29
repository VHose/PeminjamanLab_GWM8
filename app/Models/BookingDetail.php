<?php

namespace App\Models;

use App\Enums\BookingDetailStatus;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BookingDetail extends Model
{
    protected $table = 'booking_detail';

    public $timestamps = false;

    public $incrementing = false;

    protected $primaryKey = ['booking_id', 'booking_date', 'start_time'];

    protected $fillable = [
        'booking_id',
        'room_id',
        'booking_date',
        'start_time',
        'end_time',
        'status',
        'start_datetime',
        'end_datetime',
    ];

    public function setStartDatetimeAttribute($value): void
    {
        $dt = Carbon::parse($value);
        $this->attributes['booking_date'] = $dt->toDateString();
        $this->attributes['start_time'] = $dt->format('H:i:s');
    }

    public function setEndDatetimeAttribute($value): void
    {
        $dt = Carbon::parse($value);
        $this->attributes['end_time'] = $dt->format('H:i:s');
    }

    public function fresh($with = [])
    {
        if (! $this->exists) {
            return null;
        }

        $query = static::newModelQuery()->with(is_string($with) ? func_get_args() : $with);

        return $this->setKeysForSaveQuery($query)->first();
    }

    public function refresh()
    {
        if (! $this->exists) {
            return $this;
        }

        $fresh = $this->fresh();
        if ($fresh) {
            $this->setRawAttributes($fresh->getAttributes(), true);
            $this->setRelations($fresh->getRelations());
        }

        return $this;
    }

    public function getRouteKey(): string
    {
        $dateStr = $this->booking_date instanceof \DateTimeInterface 
            ? $this->booking_date->format('Y-m-d') 
            : (string) $this->booking_date;

        return "{$this->booking_id}_{$dateStr}_{$this->start_time}";
    }

    public function resolveRouteBinding($value, $field = null): ?self
    {
        if (is_string($value) && str_contains($value, '_')) {
            $parts = explode('_', $value);
            if (count($parts) === 3) {
                return $this->where('booking_id', $parts[0])
                    ->where('booking_date', $parts[1])
                    ->where('start_time', $parts[2])
                    ->first();
            }
            if (count($parts) === 4) {
                return $this->where('booking_id', $parts[0])
                    ->where('room_id', $parts[1])
                    ->where('booking_date', $parts[2])
                    ->where('start_time', $parts[3])
                    ->first();
            }
        }

        return $this->where('booking_id', $value)->first();
    }

    protected function casts(): array
    {
        return [
            'booking_date' => 'date',
            'status' => BookingDetailStatus::class,
        ];
    }

    protected function setKeysForSaveQuery($query)
    {
        $date = $this->getAttribute('booking_date');
        if ($date instanceof \DateTimeInterface) {
            $date = $date->format('Y-m-d');
        }

        $query->where('booking_id', '=', $this->getAttribute('booking_id'))
              ->where('booking_date', '=', $date)
              ->where('start_time', '=', $this->getAttribute('start_time'));

        return $query;
    }

    public function booking(): BelongsTo
    {
        return $this->belongsTo(Booking::class, 'booking_id');
    }

    public function room(): BelongsTo
    {
        return $this->belongsTo(Room::class, 'room_id');
    }

    public function getStatusLabelAttribute(): string
    {
        return $this->status?->label() ?? 'Tidak Diketahui';
    }

    public function getStartDatetimeAttribute(): Carbon
    {
        return Carbon::parse($this->booking_date->toDateString() . ' ' . $this->start_time);
    }

    public function getEndDatetimeAttribute(): Carbon
    {
        return Carbon::parse($this->booking_date->toDateString() . ' ' . $this->end_time);
    }
}
