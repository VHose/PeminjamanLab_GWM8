<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Auth\Events\Verified;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class EmailVerificationController extends Controller
{
    public function notice(Request $request): View|RedirectResponse
    {
        if ($request->user() && $request->user()->hasVerifiedEmail()) {
            return redirect()->route('home');
        }

        return view('auth.verify-email');
    }

    public function verify(Request $request, string $id, string $hash): RedirectResponse
    {
        $user = User::findOrFail($id);

        if (! hash_equals((string) $hash, sha1($user->getEmailForVerification()))) {
            abort(403, 'Tautan verifikasi tidak sah atau telah kedaluwarsa.');
        }

        if ($user->hasVerifiedEmail()) {
            if (auth()->check()) {
                return redirect()->route('home')->with('info', 'Alamat email Anda sudah terverifikasi.');
            }
            return redirect()->route('login')->with('info', 'Alamat email Anda sudah terverifikasi. Silakan masuk.');
        }

        if ($user->markEmailAsVerified()) {
            event(new Verified($user));
        }

        $msg = 'Email Anda berhasil diverifikasi! Akun Anda sedang menunggu persetujuan dari Kepala Lab / Staf Lab sebelum dapat mengajukan peminjaman.';

        if (auth()->check()) {
            return redirect()->route('home')->with('success', $msg);
        }

        return redirect()->route('login')->with('success', $msg . ' Silakan masuk untuk memantau status akun Anda.');
    }

    public function send(Request $request): RedirectResponse
    {
        if ($request->user()->hasVerifiedEmail()) {
            return redirect()->route('home');
        }

        $request->user()->sendEmailVerificationNotification();

        return back()->with('status', 'verification-link-sent');
    }
}
