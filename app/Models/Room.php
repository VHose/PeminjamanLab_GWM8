<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Room extends Model
{
    protected $table = 'room';

    protected $fillable = ['name', 'capacity', 'description'];

    public function sections(): HasMany
    {
        return $this->hasMany(Section::class);
    }

    public function bookingRooms(): HasMany
    {
        return $this->hasMany(BookingRoom::class);
    }
}
