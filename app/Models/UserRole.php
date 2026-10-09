<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserRole extends Model
{
    protected $table = 'user_role';

    protected $fillable = ['user_id', 'role_id', 'start_date', 'end_date'];

    protected function casts(): array
    {
        return [
            'start_date' => 'date',
            'end_date' => 'date',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function role(): BelongsTo
    {
        return $this->belongsTo(Role::class);
    }

    /** Check if this role assignment is active today. */
    public function isActive(): bool
    {
        $today = now()->toDateString();

        return $this->start_date->toDateString() <= $today
            && ($this->end_date === null || $this->end_date->toDateString() >= $today);
    }
}
