<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
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
        'study_program_id',
        'name',
        'email',
        'phone',
        'password',
        'is_active',
        'must_change_password',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'must_change_password' => 'boolean',
            'password' => 'hashed',
        ];
    }

    /* ── Relationships ── */

    public function studyProgram(): BelongsTo
    {
        return $this->belongsTo(StudyProgram::class, 'study_program_id');
    }

    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class, 'user_role', 'user_id', 'role_id')
            ->withPivot('period_id');
    }

    public function userRoles(): HasMany
    {
        return $this->hasMany(UserRole::class, 'user_id');
    }

    public function schedules(): HasMany
    {
        return $this->hasMany(Schedule::class, 'user_id');
    }

    public function bookings(): HasMany
    {
        return $this->hasMany(Booking::class, 'user_id');
    }

    public function activityLogs(): HasMany
    {
        return $this->hasMany(ActivityLog::class, 'user_id');
    }

    /* ── Active Role Helpers (Per Periode) ── */

    public function activePeriodId(): ?int
    {
        return Period::where('is_active', true)->value('id');
    }

    public function activeRoles(?int $periodId = null)
    {
        $targetPeriodId = $periodId ?? $this->activePeriodId();

        $query = $this->belongsToMany(Role::class, 'user_role', 'user_id', 'role_id')
            ->withPivot('period_id');

        if ($targetPeriodId !== null) {
            $query->wherePivot('period_id', $targetPeriodId);
        }

        return $query;
    }

    public function hasActiveRole(string ...$roleNames): bool
    {
        $targetPeriodId = $this->activePeriodId();

        $query = $this->roles()->whereIn('role.name', $roleNames);
        if ($targetPeriodId !== null) {
            $query->wherePivot('period_id', $targetPeriodId);
        }

        $hasExplicit = $query->exists();
        if ($hasExplicit) {
            return true;
        }

        // Jika user tidak punya role aktif pada periode ini, fallback sebagai Visitor
        if (in_array('Visitor', $roleNames, true)) {
            $anyRoleQuery = $this->roles();
            if ($targetPeriodId !== null) {
                $anyRoleQuery->wherePivot('period_id', $targetPeriodId);
            }
            return ! $anyRoleQuery->exists();
        }

        return false;
    }

    /** Backward-compat alias for middleware and existing code. */
    public function hasRole(string ...$roles): bool
    {
        return $this->hasActiveRole(...$roles);
    }

    public function isInternal(?int $periodId = null): bool
    {
        $names = $this->activeRoles($periodId)->pluck('name');

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
