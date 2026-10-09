<?php

namespace App\Constants;

class BookingDetailStatus
{
    public const PENDING = 0;
    public const APPROVED = 1;
    public const REJECTED = 2;
    public const CANCELLED = 3;

    public const LABELS = [
        self::PENDING => 'Menunggu',
        self::APPROVED => 'Disetujui',
        self::REJECTED => 'Ditolak',
        self::CANCELLED => 'Dibatalkan',
    ];

    public static function label(int $value): string
    {
        return self::LABELS[$value] ?? 'Tidak Diketahui';
    }
}
