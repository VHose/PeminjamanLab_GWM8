<?php

namespace App\Http\Controllers;

use App\Imports\ScheduleImport;
use App\Models\ActivityLog;
use App\Models\Period;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Maatwebsite\Excel\Facades\Excel;

class ScheduleImportController extends Controller
{
    public function index(): View
    {
        $allPeriods = Period::orderByDesc('start_date')->get();
        $activePeriod = Period::where('is_active', true)->first() ?? $allPeriods->first();

        return view('admin.schedules.import', compact('allPeriods', 'activePeriod'));
    }

    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'period_id' => ['required', 'exists:period,id'],
            'file' => ['required', 'file', 'mimes:xlsx,xls,csv,txt', 'max:10240'],
        ], [
            'period_id.required' => 'Pilih periode semester terlebih dahulu.',
            'file.required' => 'File Excel / CSV jadwal wajib diunggah.',
            'file.mimes' => 'Format file harus berupa Excel (.xlsx, .xls) atau CSV (.csv).',
            'file.max' => 'Ukuran file maksimal adalah 10 MB.',
        ]);

        $period = Period::findOrFail($request->input('period_id'));
        $import = new ScheduleImport($period);

        try {
            Excel::import($import, $request->file('file'));

            ActivityLog::record(
                $request->user()->id,
                'import',
                'schedule',
                (string) $period->id,
                "Import jadwal kuliah untuk periode {$period->name}. Berhasil: {$import->getImportedCount()} jadwal lab.",
                $request->ip()
            );

            return redirect()->route('admin.schedules.import')
                ->with('success', "Jadwal berhasil diimport! Sebanyak {$import->getImportedCount()} jadwal perkuliahan Lab GWM Lt. 8 telah dimasukkan dan sesi mingguan berhasil digenerate otomatis.");
        } catch (\Throwable $e) {
            return back()->withInput()->with('error', 'Gagal memproses file: ' . $e->getMessage());
        }
    }
}
