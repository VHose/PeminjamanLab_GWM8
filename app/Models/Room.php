<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Room extends Model
{
    protected $table = 'room';

    protected $fillable = ['code', 'name', 'capacity', 'active'];

    protected function casts(): array
    {
        return ['active' => 'boolean'];
    }

    public function sections(): HasMany
    {
        return $this->hasMany(Section::class);
    }

    public function bookingDetails(): HasMany
    {
        return $this->hasMany(BookingDetail::class);
    }
}
