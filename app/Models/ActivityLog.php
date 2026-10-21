<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ActivityLog extends Model
{
    protected $table = 'activity_log';

    public $timestamps = false;

    public $incrementing = false;

    protected $primaryKey = 'created_at';

    protected $keyType = 'string';

    protected $fillable = [
        'created_at',
        'user_id',
        'action',
        'subject_type',
        'subject_id',
        'description',
        'ip_address',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function getCreatedAtAttribute($value)
    {
        return $value ? Carbon::parse($value) : null;
    }

    /**
     * Helper untuk mencatat log aktivitas dengan presisi mikrodetik pada PK created_at.
     */
    public static function record(
        ?string $userId,
        string $action,
        string $subjectType,
        string|int $subjectId,
        ?string $description = null,
        ?string $ipAddress = null
    ): self {
        $timestamp = Carbon::now()->format('Y-m-d H:i:s.u');

        // Pastikan tidak ada collision PK mikrodetik jika terjadi insert beruntun cepat
        while (static::where('created_at', $timestamp)->exists()) {
            $timestamp = Carbon::parse($timestamp)->addMicroseconds(1)->format('Y-m-d H:i:s.u');
        }

        return static::create([
            'created_at' => $timestamp,
            'user_id' => $userId,
            'action' => substr($action, 0, 30),
            'subject_type' => substr($subjectType, 0, 30),
            'subject_id' => substr((string) $subjectId, 0, 30),
            'description' => $description ? substr($description, 0, 255) : null,
            'ip_address' => $ipAddress ?? request()?->ip(),
        ]);
    }
}
