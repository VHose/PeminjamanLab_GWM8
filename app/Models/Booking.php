<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Booking extends Model
{
    protected $table = 'booking';

    protected $fillable = ['user_id', 'parent_booking_id', 'requester_name', 'purpose', 'participant_count', 'type', 'status', 'notes', 'submitted_at'];

    protected function casts(): array
    {
        return ['submitted_at' => 'datetime'];
    }

    public function requester(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function parentBooking(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_booking_id');
    }

    public function changeRequests(): HasMany
    {
        return $this->hasMany(self::class, 'parent_booking_id');
    }

    public function roomBookings(): HasMany
    {
        return $this->hasMany(BookingRoom::class);
    }

    public function rooms(): BelongsToMany
    {
        return $this->belongsToMany(Room::class, 'booking_room')->withPivot(['start_datetime', 'end_datetime'])->withTimestamps();
    }

    public function approvals(): HasMany
    {
        return $this->hasMany(BookingApproval::class);
    }
}
