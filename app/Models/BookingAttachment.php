<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BookingAttachment extends Model
{
    protected $table = 'booking_attachment';

    public $timestamps = false;

    protected $fillable = [
        'booking_id',
        'original_name',
        'path',
        'mime_type',
        'size',
        'created_at',
    ];

    protected function casts(): array
    {
        return [
            'size' => 'integer',
            'created_at' => 'datetime',
        ];
    }

    public function booking(): BelongsTo
    {
        return $this->belongsTo(Booking::class, 'booking_id');
    }
}
