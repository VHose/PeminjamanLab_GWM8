<?php

namespace App\Constants;

use App\Enums\BookingStatus as BookingStatusEnum;

class BookingStatus
{
    public const PENDING_KAPRODI = 0;
    public const PENDING_KALAB = 1;
    public const APPROVED = 2;
    public const REJECTED = 3;
    public const CANCELLED = 4;
    public const CHANGE_REQUESTED = 5;

    public const LABELS = [
        self::PENDING_KAPRODI => 'Menunggu Kaprodi',
        self::PENDING_KALAB => 'Menunggu Kalab',
        self::APPROVED => 'Disetujui',
        self::REJECTED => 'Ditolak',
        self::CANCELLED => 'Dibatalkan',
        self::CHANGE_REQUESTED => 'Perubahan Diajukan',
    ];

    public static function label(int $value): string
    {
        return BookingStatusEnum::tryFrom($value)?->label() ?? self::LABELS[$value] ?? 'Tidak Diketahui';
    }
}
