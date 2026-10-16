<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Setting extends Model
{
    protected $table = 'setting';

    public $incrementing = false;

    protected $primaryKey = 'key';

    protected $keyType = 'string';

    public $timestamps = false;

    protected $fillable = [
        'key',
        'value',
        'updated_at',
    ];

    protected function casts(): array
    {
        return [
            'updated_at' => 'datetime',
        ];
    }

    public static function get(string $key, mixed $default = null): mixed
    {
        $setting = static::query()->find($key);

        return $setting !== null ? $setting->value : $default;
    }

    public static function set(string $key, mixed $value): static
    {
        return static::query()->updateOrCreate(
            ['key' => $key],
            [
                'value' => $value !== null ? (string) $value : null,
                'updated_at' => now(),
            ]
        );
    }

    public static function getAll(): array
    {
        return static::query()->pluck('value', 'key')->all();
    }

    /**
     * Memeriksa apakah parameter booking wajib sudah diisi oleh admin:
     * lab_open_time, lab_close_time, booking_max_days_ahead, booking_max_duration_hours.
     */
    public static function isBookingConfigured(): bool
    {
        $requiredKeys = [
            'lab_open_time',
            'lab_close_time',
            'booking_max_days_ahead',
            'booking_max_duration_hours',
        ];

        $settings = static::getAll();
        foreach ($requiredKeys as $key) {
            if (empty($settings[$key])) {
                return false;
            }
        }

        return true;
    }
}
