<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Lecturer extends Model
{
    protected $table = 'lecturer';

    protected $primaryKey = 'nik';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = ['nik', 'name'];

    public function sections(): HasMany
    {
        return $this->hasMany(Section::class, 'lecturer_nik', 'nik');
    }
}
