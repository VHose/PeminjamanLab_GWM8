<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreClassSlotRequest;
use App\Http\Requests\UpdateClassSlotRequest;
use App\Models\ActivityLog;
use App\Models\ClassSlot;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class ClassSlotController extends Controller
{
    public function store(StoreClassSlotRequest $request): RedirectResponse
    {
        $slot = ClassSlot::create([
            'code' => $request->input('code'),
            'start_time' => $request->input('start_time'),
            'end_time' => $request->input('end_time'),
            'is_active' => $request->boolean('is_active', true),
        ]);

        ActivityLog::record(
            $request->user()->id,
            'create',
            'class_slot',
            $slot->code,
            "Menambahkan slot jam kelas: {$slot->code} ({$slot->start_time} - {$slot->end_time}).",
            $request->ip()
        );

        return redirect()->route('admin.settings.index', ['tab' => 'slots'])
            ->with('success', 'Slot jam kelas berhasil ditambahkan.');
    }

    public function update(UpdateClassSlotRequest $request, ClassSlot $classSlot): RedirectResponse
    {
        $classSlot->update([
            'start_time' => $request->input('start_time'),
            'end_time' => $request->input('end_time'),
            'is_active' => $request->boolean('is_active', true),
        ]);

        ActivityLog::record(
            $request->user()->id,
            'update',
            'class_slot',
            $classSlot->code,
            "Memperbarui slot jam kelas: {$classSlot->code}.",
            $request->ip()
        );

        return redirect()->route('admin.settings.index', ['tab' => 'slots'])
            ->with('success', 'Slot jam kelas berhasil diperbarui.');
    }

    public function toggle(Request $request, ClassSlot $classSlot): RedirectResponse
    {
        $classSlot->update(['is_active' => ! $classSlot->is_active]);

        $statusStr = $classSlot->is_active ? 'mengaktifkan' : 'menonaktifkan';

        ActivityLog::record(
            $request->user()->id,
            'toggle_active',
            'class_slot',
            $classSlot->code,
            "Tindakan {$statusStr} slot jam kelas {$classSlot->code}.",
            $request->ip()
        );

        return back()->with('success', "Status slot jam {$classSlot->code} berhasil diubah.");
    }
}
