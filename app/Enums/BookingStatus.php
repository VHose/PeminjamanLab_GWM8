<?php

namespace App\Enums;

enum BookingStatus: int
{
    case PENDING_PRODI = 0;
    case PENDING_LAB = 1;
    case APPROVED = 2;
    case REJECTED = 3;
    case CANCELLED = 4;
    case CHANGE_REQUESTED = 5;

    public const PENDING_KAPRODI = self::PENDING_PRODI;
    public const PENDING_KALAB = self::PENDING_LAB;

    public function label(): string
    {
        return match ($this) {
            self::PENDING_PRODI => 'Menunggu Kaprodi',
            self::PENDING_LAB => 'Menunggu Kalab',
            self::APPROVED => 'Disetujui',
            self::REJECTED => 'Ditolak',
            self::CANCELLED => 'Dibatalkan',
            self::CHANGE_REQUESTED => 'Perubahan Diajukan',
        };
    }
}
