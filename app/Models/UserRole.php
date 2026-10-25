<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserRole extends Model
{
    protected $table = 'user_role';

    public $timestamps = false;

    public $incrementing = false;

    protected $primaryKey = ['user_id', 'role_id', 'period_id'];

    protected $fillable = [
        'user_id',
        'role_id',
        'period_id',
    ];

    protected function casts(): array
    {
        return [
            'role_id' => 'integer',
            'period_id' => 'integer',
        ];
    }

    protected function setKeysForSaveQuery($query)
    {
        $query->where('user_id', '=', $this->getAttribute('user_id'))
              ->where('role_id', '=', $this->getAttribute('role_id'))
              ->where('period_id', '=', $this->getAttribute('period_id'));

        return $query;
    }

    public function fresh($with = [])
    {
        if (! $this->exists) {
            return null;
        }

        $query = static::newModelQuery()->with(is_string($with) ? func_get_args() : $with);

        return $this->setKeysForSaveQuery($query)->first();
    }

    public function refresh()
    {
        if (! $this->exists) {
            return $this;
        }

        $fresh = $this->fresh();
        if ($fresh) {
            $this->setRawAttributes($fresh->getAttributes(), true);
            $this->setRelations($fresh->getRelations());
        }

        return $this;
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function role(): BelongsTo
    {
        return $this->belongsTo(Role::class, 'role_id');
    }

    public function period(): BelongsTo
    {
        return $this->belongsTo(Period::class, 'period_id');
    }
}
