<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BookingApproval extends Model
{
    protected $table = 'booking_approval';

    protected $fillable = ['booking_id', 'approver_id', 'level', 'status', 'notes', 'decided_at'];

    protected function casts(): array
    {
        return ['decided_at' => 'datetime'];
    }

    public function booking(): BelongsTo
    {
        return $this->belongsTo(Booking::class);
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approver_id');
    }
}
