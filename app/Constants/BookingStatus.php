<?php

namespace App\Constants;

class BookingStatus
{
    public const PENDING_KAPRODI = 0;
    public const PENDING_KALAB = 1;
    public const APPROVED = 2;
    public const REJECTED = 3;
    public const CANCELLED = 4;

    public const LABELS = [
        self::PENDING_KAPRODI => 'Menunggu Kaprodi',
        self::PENDING_KALAB => 'Menunggu Kalab',
        self::APPROVED => 'Disetujui',
        self::REJECTED => 'Ditolak',
        self::CANCELLED => 'Dibatalkan',
    ];

    public static function label(int $value): string
    {
        return self::LABELS[$value] ?? 'Tidak Diketahui';
    }
}
