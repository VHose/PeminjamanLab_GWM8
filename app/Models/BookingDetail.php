<?php

namespace App\Models;

use App\Constants\BookingDetailStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BookingDetail extends Model
{
    protected $table = 'booking_detail';

    protected $fillable = [
        'booking_id',
        'room_id',
        'start_datetime',
        'end_datetime',
        'status',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'start_datetime' => 'datetime',
            'end_datetime' => 'datetime',
            'status' => 'integer',
        ];
    }

    public function booking(): BelongsTo
    {
        return $this->belongsTo(Booking::class);
    }

    public function room(): BelongsTo
    {
        return $this->belongsTo(Room::class);
    }

    public function getStatusLabelAttribute(): string
    {
        return BookingDetailStatus::label($this->status);
    }
}
