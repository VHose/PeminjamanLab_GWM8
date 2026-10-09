<?php

namespace App\Http\Controllers;

use App\Models\Role;
use App\Models\StudyProgram;
use App\Models\User;
use App\Models\UserRole;
use App\Services\ActivityLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\View\View;

class AdminUserController extends Controller
{
    public function index(Request $request): View
    {
        $users = User::with(['roles', 'studyProgram'])
            ->latest()
            ->paginate(15);

        $allRoles = Role::all();
        $studyPrograms = StudyProgram::where('active', true)->get();

        return view('admin.users.index', compact('users', 'allRoles', 'studyPrograms'));
    }

    public function store(Request $request, ActivityLogger $logger): RedirectResponse
    {
        $data = $request->validate([
            'id' => 'required|string|max:30|unique:user,id', // NIK
            'name' => 'required|string|max:100',
            'email' => 'required|email|max:100|unique:user,email',
            'phone' => 'nullable|string|max:20',
            'study_program_id' => 'nullable|exists:study_program,id',
            'role_id' => 'required|exists:role,id',
            'start_date' => 'required|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
        ]);

        // Password awal acak
        $randomPassword = Str::random(16);

        $user = User::create([
            'id' => $data['id'],
            'name' => $data['name'],
            'email' => $data['email'],
            'phone' => $data['phone'] ?? null,
            'study_program_id' => $data['study_program_id'] ?? null,
            'password' => Hash::make($randomPassword),
        ]);

        $userRole = UserRole::create([
            'user_id' => $user->id,
            'role_id' => $data['role_id'],
            'start_date' => $data['start_date'],
            'end_date' => $data['end_date'] ?? null,
        ]);

        $logger->log($request->user()->id, 'create', 'user', $user->id, "Membuat akun user baru ({$user->name}).");
        $logger->log($request->user()->id, 'assign_role', 'user_role', (string) $userRole->id, "Memberikan role kepada user {$user->id}.", [
            'role_id' => $data['role_id'],
            'start_date' => $data['start_date'],
            'end_date' => $data['end_date'] ?? null,
        ]);

        return redirect()->route('admin.users.index')->with('success', 'Akun berhasil dibuat dengan password acak. Pengguna dapat menggunakan fitur Lupa Password untuk mengatur kata sandi.');
    }

    public function assignRole(Request $request, User $user, ActivityLogger $logger): RedirectResponse
    {
        $data = $request->validate([
            'role_id' => 'required|exists:role,id',
            'start_date' => 'required|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
        ]);

        $userRole = UserRole::create([
            'user_id' => $user->id,
            'role_id' => $data['role_id'],
            'start_date' => $data['start_date'],
            'end_date' => $data['end_date'] ?? null,
        ]);

        $logger->log($request->user()->id, 'assign_role', 'user_role', (string) $userRole->id, "Menambahkan role baru untuk {$user->name}.", [
            'role_id' => $data['role_id'],
            'start_date' => $data['start_date'],
            'end_date' => $data['end_date'] ?? null,
        ]);

        return back()->with('success', 'Role berhasil ditambahkan ke user.');
    }

    public function deactivateRole(Request $request, UserRole $userRole, ActivityLogger $logger): RedirectResponse
    {
        $userRole->update([
            'end_date' => now()->toDateString(),
        ]);

        $logger->log($request->user()->id, 'deactivate_role', 'user_role', (string) $userRole->id, "Menonaktifkan role {$userRole->role->name} untuk user {$userRole->user_id}.");

        return back()->with('success', 'Role berhasil dinonaktifkan.');
    }
}
