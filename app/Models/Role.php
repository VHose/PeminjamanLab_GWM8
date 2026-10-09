<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Role extends Model
{
    protected $table = 'role';

    protected $fillable = ['name'];

    public function users()
    {
        return $this->belongsToMany(User::class, 'user_role')
            ->withPivot('id', 'start_date', 'end_date')
            ->withTimestamps();
    }

    public function userRoles()
    {
        return $this->hasMany(UserRole::class);
    }
}
