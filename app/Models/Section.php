<?php

namespace App\Models;

/**
 * Backward compatibility alias for Schedule
 */
class Section extends Schedule
{
    protected $table = 'schedule';

    /** Map legacy attribute name */
    public function getScheduleTypeAttribute()
    {
        return $this->type?->value ?? 0;
    }
}
