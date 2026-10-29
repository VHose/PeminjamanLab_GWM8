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
        'pic_name',
        'requester_name',
        'pic_phone',
        'purpose',
        'status',
        'decision_note',
        'notes',
    ];

    public function setRequesterNameAttribute($value): void
    {
        $this->attributes['pic_name'] = $value;
    }

    public function setNotesAttribute($value): void
    {
        $this->attributes['decision_note'] = $value;
    }

    public function getNotesAttribute(): ?string
    {
        return $this->decision_note;
    }

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

    public function requester(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
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
