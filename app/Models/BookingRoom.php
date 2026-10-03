<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BookingRoom extends Model
{
    protected $table = 'booking_room';

    protected $fillable = ['booking_id', 'room_id', 'start_datetime', 'end_datetime'];

    protected function casts(): array
    {
        return ['start_datetime' => 'datetime', 'end_datetime' => 'datetime'];
    }

    public function booking(): BelongsTo
    {
        return $this->belongsTo(Booking::class);
    }

    public function room(): BelongsTo
    {
        return $this->belongsTo(Room::class);
    }
}
