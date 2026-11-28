<?php

namespace App\Http\Controllers;

use App\Http\Requests\AdminResetPasswordRequest;
use App\Http\Requests\StoreAdminUserRequest;
use App\Models\ActivityLog;
use App\Models\Period;
use App\Models\Role;
use App\Models\StudyProgram;
use App\Models\User;
use App\Models\UserRole;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;

class AdminUserController extends Controller
{
    public function index(Request $request): View
    {
        $allPeriods = Period::orderByDesc('start_date')->get();
        $activePeriodId = Period::where('is_active', true)->value('id') ?? $allPeriods->first()?->id;
        $selectedPeriodId = $request->query('period_id', $activePeriodId);
        $selectedPeriod = Period::find($selectedPeriodId) ?? $allPeriods->first();
        if ($selectedPeriod) {
            $selectedPeriodId = $selectedPeriod->id;
        }

        $search = $request->query('search');
        $roleFilter = $request->query('role_id');
        $prodiFilter = $request->query('study_program_id');
        $statusFilter = $request->query('is_active');

        // Query directly from User table to show all users including newly registered ones
        $query = User::with([
            'studyProgram',
            'userRoles.role',
            'roles'
        ]);

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('id', 'like', "%{$search}%")
                  ->orWhere('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('phone', 'like', "%{$search}%")
                  ->orWhereHas('studyProgram', fn ($sq) => $sq->where('name', 'like', "%{$search}%"));
            });
        }

        if ($roleFilter) {
            if ($roleFilter === 'visitor') {
                $query->whereDoesntHave('userRoles', function ($q) use ($selectedPeriodId) {
                    $q->where('period_id', $selectedPeriodId)
                      ->whereHas('role', fn ($rq) => $rq->where('name', '!=', 'Visitor'));
                });
            } else {
                $query->whereHas('userRoles', function ($q) use ($selectedPeriodId, $roleFilter) {
                    $q->where('role_id', $roleFilter)
                      ->where(function ($sub) use ($selectedPeriodId) {
                          $sub->where('period_id', $selectedPeriodId)->orWhereNull('period_id');
                      });
                });
            }
        }

        if ($prodiFilter !== null && $prodiFilter !== '') {
            $query->where('study_program_id', $prodiFilter);
        }

        if ($statusFilter !== null && $statusFilter !== '') {
            $query->where('is_active', (bool) $statusFilter);
        }

        $users = $query->orderBy('name')->get();

        $allRoles = Role::orderBy('id')->get();
        $studyPrograms = StudyProgram::orderBy('id')->get();

        return view('admin.users.index', compact(
            'users',
            'allPeriods',
            'selectedPeriod',
            'selectedPeriodId',
            'activePeriodId',
            'allRoles',
            'studyPrograms',
            'search',
            'roleFilter',
            'prodiFilter',
            'statusFilter'
        ));
    }

    public function store(StoreAdminUserRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $activePeriodId = Period::where('is_active', true)->value('id');

        $user = User::create([
            'id' => $data['id'],
            'study_program_id' => $data['study_program_id'] ?? null,
            'name' => $data['name'],
            'email' => $data['email'],
            'phone' => $data['phone'] ?? null,
            'password' => Hash::make($data['password']),
            'is_active' => true,
            'status' => 'active',
            'email_verified_at' => now(),
        ]);

        $roleId = $request->input('role_id');
        if ($roleId && $activePeriodId) {
            UserRole::firstOrCreate([
                'user_id' => $user->id,
                'role_id' => $roleId,
                'period_id' => $activePeriodId,
            ]);
        } elseif ($activePeriodId) {
            $adminRole = Role::where('name', 'Admin')->first();
            if ($adminRole) {
                UserRole::firstOrCreate([
                    'user_id' => $user->id,
                    'role_id' => $adminRole->id,
                    'period_id' => $activePeriodId,
                ]);
            }
        }

        ActivityLog::record(
            $request->user()->id,
            'create',
            'user',
            $user->id,
            "Membuat akun pengguna baru: {$user->id} ({$user->name}).",
            $request->ip()
        );

        return redirect()->route('admin.users.index')
            ->with('success', "Akun pengguna {$user->name} ({$user->id}) berhasil dibuat.");
    }

    public function resetPassword(AdminResetPasswordRequest $request, User $user): RedirectResponse
    {
        $user->forceFill([
            'password' => Hash::make($request->input('password')),
        ])->save();

        ActivityLog::record(
            $request->user()->id,
            'reset_password',
            'user',
            $user->id,
            "Admin mereset kata sandi pengguna {$user->id}.",
            $request->ip()
        );

        return back()->with('success', "Kata sandi pengguna {$user->name} ({$user->id}) berhasil direset.");
    }

    public function toggleActive(Request $request, User $user): RedirectResponse
    {
        if ($user->id === $request->user()->id) {
            return back()->with('error', 'Anda tidak dapat menonaktifkan akun Anda sendiri.');
        }

        $newActive = ! $user->is_active;
        $user->update([
            'is_active' => $newActive,
            'status' => $newActive ? 'active' : 'rejected',
        ]);

        $statusStr = $newActive ? 'mengaktifkan' : 'menonaktifkan';

        ActivityLog::record(
            $request->user()->id,
            'toggle_active',
            'user',
            $user->id,
            "Admin {$statusStr} akun pengguna {$user->id}.",
            $request->ip()
        );

        return back()->with('success', "Status aktif pengguna {$user->name} berhasil diubah.");
    }
}
