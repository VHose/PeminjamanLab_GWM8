<?php

namespace App\Constants;

class ScheduleType
{
    public const REGULAR = 0;
    public const EXAM = 1;

    public const LABELS = [
        self::REGULAR => 'Reguler',
        self::EXAM => 'Ujian',
    ];

    public static function label(int $value): string
    {
        return self::LABELS[$value] ?? 'Tidak Diketahui';
    }
}
