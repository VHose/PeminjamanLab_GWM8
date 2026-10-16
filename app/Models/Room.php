<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Room extends Model
{
    protected $table = 'room';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = ['id', 'name', 'capacity', 'is_active'];

    protected function casts(): array
    {
        return [
            'capacity' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    public function schedules(): HasMany
    {
        return $this->hasMany(Schedule::class, 'room_id');
    }

    public function scheduleSessions(): HasMany
    {
        return $this->hasMany(ScheduleSession::class, 'room_id');
    }

    public function bookingDetails(): HasMany
    {
        return $this->hasMany(BookingDetail::class, 'room_id');
    }
}
