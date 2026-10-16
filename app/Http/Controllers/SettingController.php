<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdateSettingsRequest;
use App\Models\ActivityLog;
use App\Models\ClassSlot;
use App\Models\Period;
use App\Models\Setting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SettingController extends Controller
{
    public function index(Request $request): View
    {
        $settings = Setting::getAll();
        $classSlots = ClassSlot::orderBy('start_time')->get();
        $periods = Period::withCount('holidays')->withCount('schedules')->orderByDesc('start_date')->get();

        $activeTab = $request->query('tab', 'general');

        return view('admin.settings.index', compact('settings', 'classSlots', 'periods', 'activeTab'));
    }

    public function update(UpdateSettingsRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        $keysToSave = [
            'booking_block_minutes',
            'lab_open_time',
            'lab_close_time',
            'lab_open_time_saturday',
            'lab_close_time_saturday',
            'booking_max_days_ahead',
            'booking_max_duration_hours',
        ];

        foreach ($keysToSave as $key) {
            Setting::set($key, $validated[$key] ?? null);
        }

        ActivityLog::record(
            $request->user()->id,
            'update',
            'setting',
            'system_settings',
            'Memperbarui pengaturan sistem dan aturan peminjaman lab.',
            $request->ip()
        );

        return redirect()->route('admin.settings.index', ['tab' => 'general'])
            ->with('success', 'Pengaturan sistem berhasil disimpan.');
    }
}
