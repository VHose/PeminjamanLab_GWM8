<?php

namespace App\Constants;

class BookingType
{
    public const NEW_BOOKING = 0;
    public const RESCHEDULE = 1;

    public const LABELS = [
        self::NEW_BOOKING => 'Baru',
        self::RESCHEDULE => 'Ubah Jadwal',
    ];

    public static function label(int $value): string
    {
        return self::LABELS[$value] ?? 'Tidak Diketahui';
    }
}
