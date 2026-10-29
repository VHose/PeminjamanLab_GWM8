<?php

namespace App\Http\Controllers;

use App\Http\Requests\CopyPeriodRolesRequest;
use App\Http\Requests\UploadStaffRequest;
use App\Models\ActivityLog;
use App\Models\Period;
use App\Models\Role;
use App\Models\StudyProgram;
use App\Models\User;
use App\Models\UserRole;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\View\View;

class PeriodRoleController extends Controller
{
    public function index(Request $request): View
    {
        $allPeriods = Period::orderByDesc('start_date')->get();
        $selectedPeriodId = $request->query('period_id', Period::where('is_active', true)->value('id') ?? $allPeriods->first()?->id);

        $selectedPeriod = Period::find($selectedPeriodId);

        $userRoles = collect();
        $invalidEmailUsers = collect();

        if ($selectedPeriod) {
            $userRoles = UserRole::with(['user.studyProgram', 'role'])
                ->where('period_id', $selectedPeriod->id)
                ->get();

            // Peringatan: user yang emailnya masih berakhiran @belum-diisi.invalid
            $userIdsInPeriod = $userRoles->pluck('user_id')->unique();
            $invalidEmailUsers = User::whereIn('id', $userIdsInPeriod)
                ->where('email', 'like', '%@belum-diisi.invalid')
                ->get();
        }

        $allRoles = Role::orderBy('id')->get();
        $studyPrograms = StudyProgram::where('is_active', true)->orderBy('id')->get();

        return view('admin.period_roles.index', compact(
            'allPeriods',
            'selectedPeriod',
            'userRoles',
            'invalidEmailUsers',
            'allRoles',
            'studyPrograms'
        ));
    }

    public function copy(CopyPeriodRolesRequest $request): RedirectResponse
    {
        $fromId = $request->input('from_period_id');
        $toId = $request->input('to_period_id');

        $fromPeriod = Period::findOrFail($fromId);
        $toPeriod = Period::findOrFail($toId);

        $sourceRoles = UserRole::where('period_id', $fromId)->get();

        $copiedCount = 0;
        foreach ($sourceRoles as $sr) {
            $exists = UserRole::where([
                'user_id' => $sr->user_id,
                'role_id' => $sr->role_id,
                'period_id' => $toId,
            ])->exists();

            if (! $exists) {
                UserRole::create([
                    'user_id' => $sr->user_id,
                    'role_id' => $sr->role_id,
                    'period_id' => $toId,
                ]);
                $copiedCount++;
            }
        }

        ActivityLog::record(
            $request->user()->id,
            'copy_roles',
            'user_role',
            $toId,
            "Menyalin {$copiedCount} penugasan peran dari periode {$fromPeriod->name} ke {$toPeriod->name}.",
            $request->ip()
        );

        return redirect()->route('admin.period-roles.index', ['period_id' => $toId])
            ->with('success', "Berhasil menyalin {$copiedCount} peran staf dari periode {$fromPeriod->name}.");
    }

    public function upload(UploadStaffRequest $request): RedirectResponse
    {
        $periodId = $request->input('period_id');
        $period = Period::findOrFail($periodId);

        $lines = [];
        if ($request->hasFile('staff_file')) {
            $content = file_get_contents($request->file('staff_file')->getRealPath());
            $lines = preg_split("/\r\n|\n|\r/", $content);
        } elseif ($request->filled('staff_text')) {
            $lines = preg_split("/\r\n|\n|\r/", $request->input('staff_text'));
        }

        $rolesByName = Role::all()->keyBy(fn ($r) => strtolower($r->name));
        $prodiIds = StudyProgram::pluck('id')->all();

        $processedCount = 0;
        $now = now();

        DB::transaction(function () use ($lines, $period, $rolesByName, $prodiIds, $now, &$processedCount) {
            foreach ($lines as $line) {
                $line = trim($line);
                if (empty($line) || str_starts_with($line, '#')) {
                    continue;
                }

                // Format: NIK/NRP, Nama, Email, RoleName, [KodeProdi]
                $cols = array_map('trim', str_getcsv($line));
                if (count($cols) < 3) {
                    continue;
                }

                $nik = $cols[0];
                $name = $cols[1];
                $email = $cols[2];
                $roleName = $cols[3] ?? 'Staf_Lab';
                $prodiId = $cols[4] ?? null;

                // Abaikan header row jika ada
                if (strtolower($nik) === 'nik' || strtolower($nik) === 'id') {
                    continue;
                }

                $roleKey = strtolower(str_replace(' ', '_', $roleName));
                $role = $rolesByName->get($roleKey) ?? $rolesByName->get('staf_lab');
                if (! $role) {
                    continue;
                }

                if (! $prodiId) {
                    $prefix = substr($nik, 0, 2);
                    $prodiId = in_array($prefix, $prodiIds, true) ? $prefix : '00';
                }

                $user = User::find($nik);
                if (! $user) {
                    $user = User::create([
                        'id' => $nik,
                        'study_program_id' => $prodiId,
                        'name' => $name,
                        'email' => $email,
                        'password' => Hash::make(Str::random(32)),
                        'is_active' => true,
                        'must_change_password' => true,
                    ]);
                } else {
                    $user->update([
                        'name' => $name,
                        'email' => $email,
                        'study_program_id' => $prodiId ?? $user->study_program_id,
                    ]);
                }

                UserRole::firstOrCreate([
                    'user_id' => $user->id,
                    'role_id' => $role->id,
                    'period_id' => $period->id,
                ]);

                $processedCount++;
            }
        });

        ActivityLog::record(
            $request->user()->id,
            'upload_staff',
            'user_role',
            $period->id,
            "Mengunggah {$processedCount} daftar staf untuk periode {$period->name}.",
            $request->ip()
        );

        return redirect()->route('admin.period-roles.index', ['period_id' => $period->id])
            ->with('success', "Berhasil memproses {$processedCount} data staf untuk periode {$period->name}.");
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'period_id' => ['required', 'exists:period,id'],
            'user_id' => ['required', 'string', 'max:10'],
            'name' => ['nullable', 'string', 'max:80'],
            'email' => ['nullable', 'email', 'max:70'],
            'role_id' => ['required', 'exists:role,id'],
            'study_program_id' => ['nullable', 'exists:study_program,id'],
        ]);

        $user = User::find($validated['user_id']);
        if (! $user) {
            $user = User::create([
                'id' => $validated['user_id'],
                'name' => $validated['name'] ?? $validated['user_id'],
                'email' => $validated['email'] ?? ($validated['user_id'] . '@belum-diisi.invalid'),
                'study_program_id' => $validated['study_program_id'] ?? null,
                'password' => Hash::make(Str::random(32)),
                'is_active' => true,
                'must_change_password' => true,
            ]);
        }

        UserRole::firstOrCreate([
            'user_id' => $user->id,
            'role_id' => $validated['role_id'],
            'period_id' => $validated['period_id'],
        ]);

        ActivityLog::record(
            $request->user()->id,
            'assign_role',
            'user_role',
            $validated['period_id'],
            "Menugaskan role kepada {$user->id} di periode {$validated['period_id']}.",
            $request->ip()
        );

        return back()->with('success', 'Peran berhasil ditambahkan.');
    }

    public function destroy(Request $request, int $periodId, string $userId, int $roleId): RedirectResponse
    {
        UserRole::where([
            'period_id' => $periodId,
            'user_id' => $userId,
            'role_id' => $roleId,
        ])->delete();

        ActivityLog::record(
            $request->user()->id,
            'remove_role',
            'user_role',
            $periodId,
            "Menghapus peran role_id {$roleId} dari user {$userId} pada periode {$periodId}.",
            $request->ip()
        );

        return back()->with('success', 'Peran staf berhasil dihapus dari periode.');
    }
}
