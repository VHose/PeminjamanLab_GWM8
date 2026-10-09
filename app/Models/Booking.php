<?php

namespace App\Models;

use App\Constants\BookingStatus;
use App\Constants\BookingType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Booking extends Model
{
    protected $table = 'booking';

    protected $fillable = [
        'user_id',
        'parent_booking_id',
        'requester_name',
        'purpose',
        'type',
        'status',
        'notes',
        'submitted_at',
    ];

    protected function casts(): array
    {
        return [
            'type' => 'integer',
            'status' => 'integer',
            'submitted_at' => 'datetime',
        ];
    }

    /* ── Relationships ── */

    public function requester(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_booking_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_booking_id');
    }

    public function details(): HasMany
    {
        return $this->hasMany(BookingDetail::class);
    }

    /* ── Accessors ── */

    public function getStatusLabelAttribute(): string
    {
        return BookingStatus::label($this->status);
    }

    public function getTypeLabelAttribute(): string
    {
        return BookingType::label($this->type);
    }
}
