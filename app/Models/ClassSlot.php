<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ClassSlot extends Model
{
    protected $table = 'class_slot';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $primaryKey = 'code';

    protected $fillable = [
        'code',
        'start_time',
        'end_time',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }
}
