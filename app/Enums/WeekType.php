<?php

namespace App\Enums;

enum WeekType: string
{
    case KULIAH = 'KULIAH';
    case UTS = 'UTS';
    case UAS = 'UAS';
    case LIBUR = 'LIBUR';

    public function label(): string
    {
        return match ($this) {
            self::KULIAH => 'Kuliah',
            self::UTS => 'UTS',
            self::UAS => 'UAS',
            self::LIBUR => 'Libur',
        };
    }
}
