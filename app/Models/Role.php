<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Role extends Model
{
    protected $table = 'role';

    public $timestamps = false;

    protected $fillable = ['name'];

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'user_role', 'role_id', 'user_id')
            ->withPivot('period_id');
    }

    public function userRoles(): HasMany
    {
        return $this->hasMany(UserRole::class, 'role_id');
    }
}
