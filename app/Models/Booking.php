<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Booking extends Model
{
    protected $table = 'booking';

    protected $fillable = ['user_id', 'parent_booking_id', 'requester_name', 'purpose', 'participant_count', 'type', 'status', 'start_datetime', 'end_datetime', 'notes', 'submitted_at'];

    protected function casts(): array
    {
        return ['start_datetime' => 'datetime', 'end_datetime' => 'datetime', 'submitted_at' => 'datetime'];
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

    public function approvals(): HasMany
    {
        return $this->hasMany(BookingApproval::class);
    }
}
