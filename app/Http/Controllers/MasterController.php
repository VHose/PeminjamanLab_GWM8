<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\Course;
use App\Models\Period;
use App\Models\Room;
use App\Models\StudyProgram;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class MasterController extends Controller
{
    private const CONFIG = [
        'study-programs' => [
            'model' => StudyProgram::class,
            'label' => 'Program Studi',
            'pk_field' => 'id',
            'pk_label' => 'Kode Prodi (72/73/79)',
        ],
        'rooms' => [
            'model' => Room::class,
            'label' => 'Ruangan Lab',
            'pk_field' => 'id',
            'pk_label' => 'Kode Ruang (ADV1, PROG1, dst)',
        ],
        'courses' => [
            'model' => Course::class,
            'label' => 'Mata Kuliah',
            'pk_field' => 'id',
            'pk_label' => 'Kode MK (maks 15 karakter)',
        ],
    ];

    public function index(string $type): View
    {
        $config = $this->config($type);
        $records = $config['model']::query()
            ->when($type === 'courses', fn ($q) => $q->with('studyProgram'))
            ->orderBy('id')
            ->paginate(20);

        return view('master.index', compact('type', 'config', 'records'));
    }

    public function create(string $type): View
    {
        $config = $this->config($type);
        $studyPrograms = StudyProgram::where('is_active', true)->orderBy('id')->get();

        return view('master.create', compact('type', 'config', 'studyPrograms'));
    }

    public function store(Request $request, string $type): RedirectResponse
    {
        $config = $this->config($type);

        $rules = match ($type) {
            'study-programs' => [
                'id' => ['required', 'string', 'max:5', 'unique:study_program,id'],
                'name' => ['required', 'string', 'max:100', 'unique:study_program,name'],
                'color_hex' => ['required', 'string', 'regex:/^#[0-9A-Fa-f]{6}$/'],
                'is_active' => ['nullable', 'boolean'],
            ],
            'rooms' => [
                'id' => ['required', 'string', 'max:10', 'unique:room,id'],
                'name' => ['required', 'string', 'max:50'],
                'capacity' => ['nullable', 'integer', 'min:1', 'max:200'],
                'is_active' => ['nullable', 'boolean'],
            ],
            'courses' => [
                'id' => ['required', 'string', 'max:15', 'unique:course,id'],
                'study_program_id' => ['required', 'exists:study_program,id'],
                'name' => ['required', 'string', 'max:100'],
                'is_active' => ['nullable', 'boolean'],
            ],
        };

        $validated = $request->validate($rules);
        $validated['is_active'] = $request->boolean('is_active', true);

        $record = $config['model']::create($validated);

        ActivityLog::record(
            $request->user()->id,
            'create',
            $record->getTable(),
            (string) $record->id,
            "Menambahkan master data {$config['label']}: {$record->id} - {$record->name}.",
            $request->ip()
        );

        return redirect()->route('master.index', $type)->with('success', "Data {$config['label']} berhasil ditambahkan.");
    }

    public function edit(string $type, string $id): View
    {
        $config = $this->config($type);
        $record = $config['model']::findOrFail($id);
        $studyPrograms = StudyProgram::where('is_active', true)->orderBy('id')->get();

        return view('master.edit', compact('type', 'config', 'record', 'studyPrograms'));
    }

    public function update(Request $request, string $type, string $id): RedirectResponse
    {
        $config = $this->config($type);
        $record = $config['model']::findOrFail($id);

        $rules = match ($type) {
            'study-programs' => [
                'name' => ['required', 'string', 'max:100', Rule::unique('study_program', 'name')->ignore($record->id, 'id')],
                'color_hex' => ['required', 'string', 'regex:/^#[0-9A-Fa-f]{6}$/'],
                'is_active' => ['nullable', 'boolean'],
            ],
            'rooms' => [
                'name' => ['required', 'string', 'max:50'],
                'capacity' => ['nullable', 'integer', 'min:1', 'max:200'],
                'is_active' => ['nullable', 'boolean'],
            ],
            'courses' => [
                'study_program_id' => ['required', 'exists:study_program,id'],
                'name' => ['required', 'string', 'max:100'],
                'is_active' => ['nullable', 'boolean'],
            ],
        };

        $validated = $request->validate($rules);
        $validated['is_active'] = $request->boolean('is_active', true);

        $record->update($validated);

        ActivityLog::record(
            $request->user()->id,
            'update',
            $record->getTable(),
            (string) $record->id,
            "Memperbarui master data {$config['label']}: {$record->id}.",
            $request->ip()
        );

        return redirect()->route('master.index', $type)->with('success', "Data {$config['label']} berhasil diperbarui.");
    }

    public function destroy(Request $request, string $type, string $id): RedirectResponse
    {
        $config = $this->config($type);
        $record = $config['model']::findOrFail($id);

        // Aturan: data master dinonaktifkan (is_active), bukan dihapus
        $record->update(['is_active' => false]);

        ActivityLog::record(
            $request->user()->id,
            'deactivate',
            $record->getTable(),
            (string) $record->id,
            "Menonaktifkan master data {$config['label']}: {$record->id}.",
            $request->ip()
        );

        return back()->with('success', "Data {$config['label']} berhasil dinonaktifkan.");
    }

    public function toggle(Request $request, string $type, string $id): RedirectResponse
    {
        $config = $this->config($type);
        $record = $config['model']::findOrFail($id);

        $record->update(['is_active' => ! $record->is_active]);

        $statusStr = $record->is_active ? 'mengaktifkan' : 'menonaktifkan';

        ActivityLog::record(
            $request->user()->id,
            'toggle_active',
            $record->getTable(),
            (string) $record->id,
            "Tindakan {$statusStr} master data {$config['label']}: {$record->id}.",
            $request->ip()
        );

        return back()->with('success', "Status aktif data {$config['label']} berhasil diubah.");
    }

    private function config(string $type): array
    {
        abort_unless(isset(self::CONFIG[$type]), 404);

        return self::CONFIG[$type];
    }
}
