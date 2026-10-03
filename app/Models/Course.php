<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Course extends Model
{
    protected $table = 'course';

    protected $fillable = ['study_program_id', 'code', 'name'];

    public function studyProgram(): BelongsTo
    {
        return $this->belongsTo(StudyProgram::class);
    }

    public function sections(): HasMany
    {
        return $this->hasMany(Section::class);
    }
}
