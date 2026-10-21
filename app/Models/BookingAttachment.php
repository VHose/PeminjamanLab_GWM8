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

    public function getFormattedSizeAttribute(): string
    {
        $bytes = (int) $this->size;
        if ($bytes >= 1048576) {
            return number_format($bytes / 1048576, 2) . ' MB';
        }
        if ($bytes >= 1024) {
            return number_format($bytes / 1024, 1) . ' KB';
        }
        return $bytes . ' B';
    }

    public function getIsImageAttribute(): bool
    {
        return str_starts_with($this->mime_type ?? '', 'image/');
    }

    public function getIsPdfAttribute(): bool
    {
        return ($this->mime_type ?? '') === 'application/pdf';
    }

    public function getIconClassAttribute(): string
    {
        if ($this->is_image) {
            return 'bi-file-earmark-image text-success';
        }
        if ($this->is_pdf) {
            return 'bi-file-earmark-pdf text-danger';
        }
        $name = strtolower($this->original_name ?? '');
        if (str_contains($this->mime_type ?? '', 'word') || str_ends_with($name, '.doc') || str_ends_with($name, '.docx')) {
            return 'bi-file-earmark-word text-primary';
        }
        return 'bi-file-earmark-text text-secondary';
    }
}
