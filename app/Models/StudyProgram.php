<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class StudyProgram extends Model
{
    protected $table = 'study_program';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = ['id', 'name', 'color_hex', 'is_active'];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    public function courses(): HasMany
    {
        return $this->hasMany(Course::class, 'study_program_id');
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class, 'study_program_id');
    }
}
