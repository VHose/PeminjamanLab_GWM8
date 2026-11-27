<?php

namespace App\Http\Controllers;

use App\Http\Requests\StorePeriodRequest;
use App\Http\Requests\UpdatePeriodRequest;
use App\Models\ActivityLog;
use App\Models\Period;
use App\Services\ScheduleSessionGenerator;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class PeriodController extends Controller
{
    public function index(): View
    {
        $periods = Period::withCount('holidays')->withCount('schedules')->orderByDesc('start_date')->get();

        return view('admin.periods.index', compact('periods'));
    }

    public function create(): View
    {
        return view('admin.periods.create');
    }

    public function store(StorePeriodRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $isActive = $request->boolean('is_active');

        DB::transaction(function () use ($data, $isActive, $request) {
            if ($isActive) {
                Period::where('is_active', true)->update(['is_active' => false]);
            }

            $period = Period::create($data + ['is_active' => $isActive]);

            ActivityLog::record(
                $request->user()->id,
                'create',
                'period',
                $period->id,
                "Menambahkan periode baru: {$period->name}.",
                $request->ip()
            );
        });

        return redirect()->route('admin.periods.index')
            ->with('success', 'Periode semester berhasil ditambahkan.');
    }

    public function edit(Period $period): View
    {
        return view('admin.periods.edit', compact('period'));
    }

    public function update(UpdatePeriodRequest $request, Period $period): RedirectResponse
    {
        $data = $request->validated();
        $isActive = $request->boolean('is_active');

        DB::transaction(function () use ($period, $data, $isActive, $request) {
            if ($isActive && ! $period->is_active) {
                Period::where('id', '!=', $period->id)->update(['is_active' => false]);
            }

            $period->update($data + ['is_active' => $isActive]);

            ActivityLog::record(
                $request->user()->id,
                'update',
                'period',
                $period->id,
                "Memperbarui periode: {$period->name}.",
                $request->ip()
            );
        });

        return redirect()->route('admin.periods.index')
            ->with('success', 'Periode semester berhasil diperbarui.');
    }

    public function toggleActive(Request $request, Period $period): RedirectResponse
    {
        DB::transaction(function () use ($period, $request) {
            if (! $period->is_active) {
                Period::where('id', '!=', $period->id)->update(['is_active' => false]);
                $period->update(['is_active' => true]);
                $actionNote = "Mengaktifkan periode: {$period->name}.";
            } else {
                $period->update(['is_active' => false]);
                $actionNote = "Menonaktifkan periode: {$period->name}.";
            }

            ActivityLog::record(
                $request->user()->id,
                'toggle_active',
                'period',
                $period->id,
                $actionNote,
                $request->ip()
            );
        });

        return back()->with('success', 'Status aktif periode berhasil diperbarui.');
    }

    public function destroy(Request $request, Period $period): RedirectResponse
    {
        // Aturan: data master dinonaktifkan (is_active), bukan dihapus
        $period->update(['is_active' => false]);

        ActivityLog::record(
            $request->user()->id,
            'deactivate',
            'period',
            $period->id,
            "Menonaktifkan periode: {$period->name}.",
            $request->ip()
        );

        return back()->with('success', "Periode {$period->name} dinonaktifkan.");
    }

    public function regenerateSessions(
        Request $request,
        Period $period,
        ScheduleSessionGenerator $generator
    ): RedirectResponse {
        $result = $generator->generateForPeriod($period);

        ActivityLog::record(
            $request->user()->id,
            'regenerate',
            'schedule_session',
            $period->id,
            "Regenerate jadwal pertemuan periode {$period->name}: {$result['created']} sesi dibuat, {$result['skipped']} sesi dilewati/dipertahankan.",
            $request->ip()
        );

        return back()->with('success', "Generate pertemuan jadwal selesai: {$result['created']} sesi baru ditambahkan, {$result['skipped']} sesi yang sudah ada dipertahankan.");
    }
}
