<?php

namespace App\Http\Controllers;

use App\Http\Requests\ChangePasswordRequest;
use App\Models\ActivityLog;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;

class PasswordChangeController extends Controller
{
    public function edit(Request $request): View
    {
        return view('auth.change-password', [
            'user' => $request->user(),
            'isForced' => (bool) $request->user()->must_change_password,
        ]);
    }

    public function update(ChangePasswordRequest $request): RedirectResponse
    {
        $user = $request->user();

        $user->forceFill([
            'password' => Hash::make($request->input('password')),
            'must_change_password' => false,
        ])->save();

        ActivityLog::record(
            $user->id,
            'change_password',
            'user',
            $user->id,
            "Pengguna {$user->id} mengubah kata sandi akun.",
            $request->ip()
        );

        return redirect()->route('home')->with('success', 'Kata sandi Anda berhasil diperbarui.');
    }
}
