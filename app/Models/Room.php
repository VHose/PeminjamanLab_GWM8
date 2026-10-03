<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Room extends Model
{
    protected $table = 'room';

    protected $fillable = ['code', 'name', 'capacity', 'description', 'active'];

    public function sections(): HasMany
    {
        return $this->hasMany(Section::class);
    }

    public function bookingRooms(): HasMany
    {
        return $this->hasMany(BookingRoom::class);
    }

    public function bookings(): BelongsToMany
    {
        return $this->belongsToMany(Booking::class, 'booking_room')->withPivot(['start_datetime', 'end_datetime'])->withTimestamps();
    }
}
