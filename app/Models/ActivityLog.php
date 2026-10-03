<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ActivityLog extends Model
{
    protected $table = 'activity_log';

    protected $fillable = ['user_id', 'action', 'entity_type', 'entity_id', 'description'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
