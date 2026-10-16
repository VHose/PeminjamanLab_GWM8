<?php

namespace App\Http\Controllers;

use App\Http\Requests\StorePeriodHolidayRequest;
use App\Models\ActivityLog;
use App\Models\Period;
use App\Models\PeriodHoliday;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PeriodHolidayController extends Controller
{
    public function index(Period $period): View
    {
        $holidays = $period->holidays()->orderBy('start_date')->get();

        return view('admin.holidays.index', compact('period', 'holidays'));
    }

    public function store(StorePeriodHolidayRequest $request, Period $period): RedirectResponse
    {
        $startDate = $request->input('start_date');
        $endDate = $request->input('end_date') ?: $startDate;
        $note = $request->input('note');

        $holiday = PeriodHoliday::create([
            'period_id' => $period->id,
            'start_date' => $startDate,
            'end_date' => $endDate,
            'note' => $note,
        ]);

        ActivityLog::record(
            $request->user()->id,
            'create',
            'period_holiday',
            "{$period->id}_{$startDate}",
            "Menambahkan hari libur pada periode {$period->name}: {$startDate} s/d {$endDate} ({$note}).",
            $request->ip()
        );

        return back()->with('success', 'Hari libur berhasil ditambahkan.');
    }

    public function destroy(Request $request, Period $period, string $startDate): RedirectResponse
    {
        $holiday = PeriodHoliday::where('period_id', $period->id)
            ->whereDate('start_date', $startDate)
            ->firstOrFail();

        $holiday->delete();

        ActivityLog::record(
            $request->user()->id,
            'delete',
            'period_holiday',
            "{$period->id}_{$startDate}",
            "Menghapus hari libur ({$startDate}) pada periode {$period->name}.",
            $request->ip()
        );

        return back()->with('success', 'Hari libur berhasil dihapus.');
    }
}
