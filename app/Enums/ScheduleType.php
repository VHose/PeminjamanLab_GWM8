<?php

namespace App\Enums;

enum ScheduleType: int
{
    case THEORY = 0;
    case PRACTICUM = 1;

    public function label(): string
    {
        return match ($this) {
            self::THEORY => 'Teori',
            self::PRACTICUM => 'Praktikum',
        };
    }
}
