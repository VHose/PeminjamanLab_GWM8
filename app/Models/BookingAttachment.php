<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class BookingAttachment extends Model
{
    protected $table = 'booking_attachment';

    public $timestamps = false;

    public $incrementing = false;

    protected $primaryKey = ['booking_id', 'original_name'];

    protected $fillable = [
        'booking_id',
        'original_name',
        'created_at',
    ];

    protected function casts(): array
    {
        return [
            'created_at' => 'datetime',
        ];
    }

    protected function setKeysForSaveQuery($query)
    {
        $query->where('booking_id', '=', $this->getAttribute('booking_id'))
              ->where('original_name', '=', $this->getAttribute('original_name'));

        return $query;
    }

    public function getRouteKeyName(): string
    {
        return 'original_name';
    }

    public function resolveRouteBinding($value, $field = null): ?self
    {
        return $this->where('original_name', $value)->first();
    }

    public function booking(): BelongsTo
    {
        return $this->belongsTo(Booking::class, 'booking_id');
    }

    /**
     * Path fisik file lampiran di storage/app/booking_attachments/{booking_id}/{original_name}.
     */
    public function getPathAttribute(): string
    {
        return "booking_attachments/{$this->booking_id}/{$this->original_name}";
    }

    /**
     * MIME Type berkas yang dideteksi langsung dari storage atau ekstensi file.
     */
    public function getMimeTypeAttribute(): ?string
    {
        if (Storage::disk('local')->exists($this->path)) {
            return Storage::disk('local')->mimeType($this->path);
        }

        $ext = strtolower(pathinfo($this->original_name ?? '', PATHINFO_EXTENSION));
        return match ($ext) {
            'pdf' => 'application/pdf',
            'png' => 'image/png',
            'jpg', 'jpeg' => 'image/jpeg',
            'doc' => 'application/msword',
            'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            default => 'application/octet-stream',
        };
    }

    /**
     * Ukuran berkas dalam bytes dari file fisik di storage.
     */
    public function getSizeAttribute(): int
    {
        if (Storage::disk('local')->exists($this->path)) {
            return Storage::disk('local')->size($this->path);
        }

        return 0;
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
