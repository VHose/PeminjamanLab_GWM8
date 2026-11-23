<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureVisitorApproved
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user) {
            return redirect()->route('login');
        }

        // Akun internal (Admin, Kalab, Kaprodi, Dosen, Staflab) dikecualikan
        if ($user->isInternal()) {
            return $next($request);
        }

        // Tahap 1: Verifikasi Email
        if (! $user->hasVerifiedEmail()) {
            return redirect()->route('verification.notice')->with(
                'warning',
                'Harap verifikasi alamat email Anda terlebih dahulu sebelum mengajukan peminjaman laboratorium.'
            );
        }

        // Tahap 2: Persetujuan Manual Kalab / Staf Lab
        if ($user->status === 'rejected') {
            return redirect()->route('bookings.index')->with(
                'error',
                'Akun pendaftaran Anda tidak disetujui / ditolak oleh pengelola laboratorium. Silakan hubungi Kepala Lab atau Staf Lab.'
            );
        }

        if ($user->status !== 'active' || ! $user->is_active) {
            return redirect()->route('bookings.index')->with(
                'warning',
                'Akun Anda saat ini masih berstatus "Menunggu Persetujuan" dari Kepala Lab / Staf Lab. Anda belum dapat mengajukan peminjaman laboratorium sampai akun Anda disetujui.'
            );
        }

        return $next($request);
    }
}
