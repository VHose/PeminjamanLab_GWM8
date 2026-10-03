<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class StudyProgram extends Model
{
    protected $table = 'study_program';

    protected $fillable = ['code', 'name', 'color_hex', 'active'];

    protected function casts(): array
    {
        return ['active' => 'boolean'];
    }

    public function courses(): HasMany
    {
        return $this->hasMany(Course::class);
    }
}
