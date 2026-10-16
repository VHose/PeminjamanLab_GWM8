<?php

namespace App\Models;

use App\Enums\BookingStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Booking extends Model
{
    protected $table = 'booking';

    protected $fillable = [
        'user_id',
        'created_by',
        'pic_name',
        'pic_phone',
        'purpose',
        'status',
        'decision_note',
    ];

    protected function casts(): array
    {
        return [
            'status' => BookingStatus::class,
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function details(): HasMany
    {
        return $this->hasMany(BookingDetail::class, 'booking_id');
    }

    public function attachments(): HasMany
    {
        return $this->hasMany(BookingAttachment::class, 'booking_id');
    }

    public function getStatusLabelAttribute(): string
    {
        return $this->status?->label() ?? 'Tidak Diketahui';
    }

    /** Backward compatibility alias for requester_name */
    public function getRequesterNameAttribute(): string
    {
        return $this->pic_name ?? $this->user?->name ?? '-';
    }
}
