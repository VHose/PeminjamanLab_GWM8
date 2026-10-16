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
        $activePeriodId = Period::where('is_active', true)->value('id');

        $users = User::with(['studyProgram', 'roles' => function ($q) use ($activePeriodId) {
            if ($activePeriodId) {
                $q->wherePivot('period_id', $activePeriodId);
            }
        }])
            ->orderBy('id')
            ->paginate(20);

        $allRoles = Role::all();
        $studyPrograms = StudyProgram::where('is_active', true)->orderBy('id')->get();
        $periods = Period::orderByDesc('start_date')->get();

        return view('admin.users.index', compact('users', 'allRoles', 'studyPrograms', 'periods', 'activePeriodId'));
    }

    public function store(StoreAdminUserRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $activePeriodId = Period::where('is_active', true)->value('id');

        $user = User::create([
            'id' => $data['id'],
            'name' => $data['name'],
            'email' => $data['email'],
            'phone' => $data['phone'] ?? null,
            'password' => Hash::make($data['password']),
            'is_active' => true,
            'must_change_password' => true,
        ]);

        $adminRole = Role::where('name', 'Admin')->first();
        if ($adminRole && $activePeriodId) {
            UserRole::firstOrCreate([
                'user_id' => $user->id,
                'role_id' => $adminRole->id,
                'period_id' => $activePeriodId,
            ]);
        }

        ActivityLog::record(
            $request->user()->id,
            'create',
            'user',
            $user->id,
            "Membuat akun admin baru: {$user->id} ({$user->name}).",
            $request->ip()
        );

        return redirect()->route('admin.users.index')
            ->with('success', 'Akun admin berhasil dibuat dengan kewajiban mengganti kata sandi saat login pertama.');
    }

    public function resetPassword(AdminResetPasswordRequest $request, User $user): RedirectResponse
    {
        $mustChange = $request->boolean('must_change_password', true);

        $user->forceFill([
            'password' => Hash::make($request->input('password')),
            'must_change_password' => $mustChange,
        ])->save();

        ActivityLog::record(
            $request->user()->id,
            'reset_password',
            'user',
            $user->id,
            "Admin mereset kata sandi pengguna {$user->id} (must_change_password = " . ($mustChange ? 'true' : 'false') . ").",
            $request->ip()
        );

        return back()->with('success', "Kata sandi pengguna {$user->name} ({$user->id}) berhasil direset.");
    }

    public function toggleActive(Request $request, User $user): RedirectResponse
    {
        // Cegah admin menonaktifkan dirinya sendiri
        if ($user->id === $request->user()->id) {
            return back()->with('error', 'Anda tidak dapat menonaktifkan akun Anda sendiri.');
        }

        $user->update(['is_active' => ! $user->is_active]);

        $statusStr = $user->is_active ? 'mengaktifkan' : 'menonaktifkan';

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
