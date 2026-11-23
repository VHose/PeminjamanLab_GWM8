<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\User;
use App\Notifications\VisitorAccountApprovedNotification;
use App\Notifications\VisitorAccountRejectedNotification;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;

class VisitorApprovalController extends Controller
{
    public function index(Request $request): View
    {
        $query = User::query()->where(function ($q) {
            $q->whereHas('roles', fn ($r) => $r->where('name', 'Visitor'))
              ->orWhere('id', 'like', 'V%')
              ->orWhereNotNull('pic_or_instansi');
        });

        $counts = [
            'all' => (clone $query)->count(),
            'pending' => (clone $query)->where('status', 'pending')->count(),
            'active' => (clone $query)->where('status', 'active')->count(),
            'rejected' => (clone $query)->where('status', 'rejected')->count(),
        ];

        if ($request->filled('status') && in_array($request->input('status'), ['pending', 'active', 'rejected'])) {
            $query->where('status', $request->input('status'));
        }

        if ($request->filled('search')) {
            $search = trim($request->input('search'));
            $query->where(function ($q) use ($search) {
                $q->where('id', 'like', "%{$search}%")
                  ->orWhere('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('phone', 'like', "%{$search}%")
                  ->orWhere('pic_or_instansi', 'like', "%{$search}%");
            });
        }

        $users = $query->orderByRaw("CASE WHEN status = 'pending' THEN 0 WHEN status = 'active' THEN 1 ELSE 2 END")
            ->orderByDesc('created_at')
            ->get();

        return view('admin.visitor_approvals.index', compact('users', 'counts'));
    }

    public function approve(Request $request, User $user): RedirectResponse
    {
        $user->update([
            'status' => 'active',
            'is_active' => true,
        ]);

        ActivityLog::record(
            auth()->id(),
            'approve_visitor',
            'user',
            $user->id,
            "Pengelola (" . auth()->user()->name . ") menyetujui akun visitor {$user->name} ({$user->id}).",
            $request->ip()
        );

        try {
            $user->notify(new VisitorAccountApprovedNotification($user));
            $this->dispatchQueueWork();
        } catch (\Throwable $e) {
            Log::warning('Gagal mengirim email persetujuan visitor: ' . $e->getMessage());
        }

        return back()->with('success', "Akun visitor {$user->name} ({$user->id}) berhasil disetujui dan diaktifkan.");
    }

    public function reject(Request $request, User $user): RedirectResponse
    {
        $request->validate([
            'reason' => ['nullable', 'string', 'max:255'],
        ]);

        $reason = $request->input('reason');

        $user->update([
            'status' => 'rejected',
            'is_active' => false,
        ]);

        ActivityLog::record(
            auth()->id(),
            'reject_visitor',
            'user',
            $user->id,
            "Pengelola (" . auth()->user()->name . ") menolak akun visitor {$user->name} ({$user->id})." . ($reason ? " Alasan: {$reason}" : ''),
            $request->ip()
        );

        try {
            $user->notify(new VisitorAccountRejectedNotification($user, $reason));
            $this->dispatchQueueWork();
        } catch (\Throwable $e) {
            Log::warning('Gagal mengirim email penolakan visitor: ' . $e->getMessage());
        }

        return back()->with('success', "Akun visitor {$user->name} ({$user->id}) berhasil ditolak.");
    }

    private function dispatchQueueWork(): void
    {
        if (config('queue.default') === 'database' && ! app()->runningUnitTests()) {
            if (PHP_OS_FAMILY === 'Windows') {
                $php = PHP_BINARY;
                $artisan = base_path('artisan');
                pclose(popen("start \"\" /B \"{$php}\" \"{$artisan}\" queue:work database --stop-when-empty > NUL 2>&1", 'r'));
            } else {
                exec('php artisan queue:work database --stop-when-empty > /dev/null 2>&1 &');
            }
        }
    }
}
