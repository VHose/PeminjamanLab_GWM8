<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ActivityLog extends Model
{
    protected $table = 'activity_log';

    public $timestamps = false;

    protected $fillable = ['user_id', 'action', 'entity_type', 'entity_id', 'data', 'created_at'];

    protected function casts(): array
    {
        return [
            'data' => 'array',
            'created_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Override to only set created_at, since there is no updated_at column.
     */
    public static function boot(): void
    {
        parent::boot();

        static::creating(function (self $model) {
            $model->created_at = $model->created_at ?? now();
        });
    }
}
