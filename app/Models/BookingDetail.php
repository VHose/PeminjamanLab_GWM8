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

    protected $primaryKey = ['booking_id', 'room_id', 'booking_date', 'start_time'];

    protected $fillable = [
        'booking_id',
        'room_id',
        'booking_date',
        'start_time',
        'end_time',
        'status',
    ];

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
              ->where('room_id', '=', $this->getAttribute('room_id'))
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
