<?php

namespace App\Models;

use App\Constants\BookingDetailStatus;
use App\Constants\BookingStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    protected $table = 'user';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'id',
        'name',
        'email',
        'password',
        'phone',
        'study_program_id',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    /* ── Relationships ── */

    public function studyProgram()
    {
        return $this->belongsTo(StudyProgram::class);
    }

    public function roles()
    {
        return $this->belongsToMany(Role::class, 'user_role')
            ->withPivot('id', 'start_date', 'end_date')
            ->withTimestamps();
    }

    public function userRoles()
    {
        return $this->hasMany(UserRole::class);
    }

    public function bookings()
    {
        return $this->hasMany(Booking::class);
    }

    public function activityLogs()
    {
        return $this->hasMany(ActivityLog::class);
    }

    /* ── Active Role Helpers ── */

    public function activeRoles()
    {
        $today = now()->toDateString();

        return $this->belongsToMany(Role::class, 'user_role')
            ->withPivot('id', 'start_date', 'end_date')
            ->wherePivot('start_date', '<=', $today)
            ->where(function ($q) use ($today) {
                $q->whereNull('user_role.end_date')
                  ->orWhere('user_role.end_date', '>=', $today);
            })
            ->withTimestamps();
    }

    public function hasActiveRole(string ...$roleNames): bool
    {
        $today = now()->toDateString();

        $hasExplicit = $this->roles()
            ->whereIn('role.name', $roleNames)
            ->wherePivot('start_date', '<=', $today)
            ->where(function ($q) use ($today) {
                $q->whereNull('user_role.end_date')
                  ->orWhere('user_role.end_date', '>=', $today);
            })
            ->exists();

        if ($hasExplicit) {
            return true;
        }

        // Jika user tidak punya active role sama sekali, diperlakukan sebagai Visitor
        if (in_array('Visitor', $roleNames, true)) {
            $hasAnyActive = $this->roles()
                ->wherePivot('start_date', '<=', $today)
                ->where(function ($q) use ($today) {
                    $q->whereNull('user_role.end_date')
                      ->orWhere('user_role.end_date', '>=', $today);
                })
                ->exists();

            return ! $hasAnyActive;
        }

        return false;
    }

    /** Backward-compat alias for middleware and existing code. */
    public function hasRole(string ...$roles): bool
    {
        return $this->hasActiveRole(...$roles);
    }

    public function isInternal(): bool
    {
        $names = $this->activeRoles()->pluck('name');

        return $names->isNotEmpty() && $names->contains(fn ($n) => $n !== 'Visitor');
    }

    /* ── Visitor ID Generation ── */

    public static function generateVisitorId(): string
    {
        $last = static::where('id', 'like', 'V%')
            ->selectRaw("MAX(CAST(SUBSTR(id, 2) AS UNSIGNED)) as max_num")
            ->value('max_num');

        $next = ($last ?? 0) + 1;

        return 'V' . str_pad($next, 6, '0', STR_PAD_LEFT);
    }
}
