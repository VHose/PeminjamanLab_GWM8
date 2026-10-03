<?php

namespace App\Http\Controllers;

use App\Models\Course;
use App\Models\Lecturer;
use App\Models\Period;
use App\Models\Room;
use App\Models\StudyProgram;
use App\Services\ActivityLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class MasterController extends Controller
{
    private const CONFIG = [
        'study-programs' => [
            'model' => StudyProgram::class,
            'label' => 'Program Studi',
            'fields' => ['code' => 'Kode', 'name' => 'Nama', 'color_hex' => 'Warna Kalender', 'active' => 'Aktif'],
            'rules' => ['code' => 'required|max:10|unique:study_program,code', 'name' => 'required|max:255', 'color_hex' => 'required|regex:/^#[0-9A-Fa-f]{6}$/', 'active' => 'boolean'],
        ],
        'lecturers' => [
            'model' => Lecturer::class,
            'label' => 'Dosen',
            'fields' => ['nik' => 'NIK', 'lecturer_code' => 'Kode Dosen', 'name' => 'Nama', 'email' => 'Email', 'phone' => 'Telepon', 'active' => 'Aktif'],
            'rules' => ['nik' => 'required|max:30|unique:lecturer,nik', 'lecturer_code' => 'nullable|max:255|unique:lecturer,lecturer_code', 'name' => 'required|max:255', 'email' => 'nullable|email', 'phone' => 'nullable|max:30', 'active' => 'boolean'],
        ],
        'rooms' => [
            'model' => Room::class,
            'label' => 'Ruangan',
            'fields' => ['code' => 'Kode', 'name' => 'Nama', 'capacity' => 'Kapasitas', 'description' => 'Deskripsi', 'active' => 'Aktif'],
            'rules' => ['code' => 'required|max:20|unique:room,code', 'name' => 'nullable|max:100', 'capacity' => 'nullable|integer|min:1', 'description' => 'nullable|string', 'active' => 'boolean'],
        ],
        'courses' => [
            'model' => Course::class,
            'label' => 'Mata Kuliah',
            'fields' => ['study_program_id' => 'Program Studi', 'code' => 'Kode', 'name' => 'Nama', 'active' => 'Aktif'],
            'rules' => ['study_program_id' => 'required|exists:study_program,id', 'code' => 'required|max:30|unique:course,code', 'name' => 'required|max:255', 'active' => 'boolean'],
        ],
        'periods' => [
            'model' => Period::class,
            'label' => 'Periode',
            'fields' => ['name' => 'Nama', 'semester' => 'Semester', 'start_date' => 'Mulai', 'end_date' => 'Selesai', 'uts_start' => 'UTS Mulai', 'uts_end' => 'UTS Selesai', 'uas_start' => 'UAS Mulai', 'uas_end' => 'UAS Selesai', 'active' => 'Aktif'],
            'rules' => ['name' => 'required|max:255', 'semester' => 'required|in:odd,even', 'start_date' => 'required|date', 'end_date' => 'required|date|after:start_date', 'uts_start' => 'nullable|date', 'uts_end' => 'nullable|date|after:uts_start', 'uas_start' => 'nullable|date', 'uas_end' => 'nullable|date|after:uas_start', 'active' => 'boolean'],
        ],
    ];

    public function index(string $type): View
    {
        $config = $this->config($type);
        $records = $config['model']::query()->latest()->paginate(15);

        return view('master.index', compact('type', 'config', 'records'));
    }

    public function create(string $type): View
    {
        $config = $this->config($type);
        $studyPrograms = StudyProgram::where('active', true)->get();

        return view('master.create', compact('type', 'config', 'studyPrograms'));
    }

    public function store(Request $request, string $type, ActivityLogger $logger): RedirectResponse
    {
        $config = $this->config($type);
        $data = $request->validate($config['rules']);
        $record = $config['model']::query()->create($data);

        $logger->log($request->user()->id, 'create', $record->getTable(), $record->getKey(), "Menambah data {$config['label']}.");

        return redirect()->route('master.index', $type)->with('success', 'Data berhasil ditambahkan.');
    }

    public function edit(string $type, string $id): View
    {
        $config = $this->config($type);
        $record = $config['model']::query()->findOrFail($id);
        $studyPrograms = StudyProgram::where('active', true)->get();

        return view('master.edit', compact('type', 'config', 'record', 'studyPrograms'));
    }

    public function update(Request $request, string $type, string $id, ActivityLogger $logger): RedirectResponse
    {
        $config = $this->config($type);
        $record = $config['model']::query()->findOrFail($id);

        $rules = collect($config['rules'])->map(function ($rule, $field) use ($config, $record) {
            $tableName = $config['model']::query()->getModel()->getTable();
            return str_replace(",{$tableName},{$field}", ",{$tableName},{$field},{$record->getKey()}", $rule);
        })->all();

        $data = $request->validate($rules);
        if (in_array($type, ['lecturers', 'rooms'], true)) {
            unset($data[$type === 'lecturers' ? 'nik' : 'code']);
        }

        $record->update($data);
        $logger->log($request->user()->id, 'update', $record->getTable(), $record->getKey(), "Memperbarui data {$config['label']}.");

        return redirect()->route('master.index', $type)->with('success', 'Data berhasil diperbarui.');
    }

    public function destroy(Request $request, string $type, string $id, ActivityLogger $logger): RedirectResponse
    {
        $config = $this->config($type);
        $record = $config['model']::query()->findOrFail($id);
        $key = $record->getKey();
        $table = $record->getTable();
        $record->delete();

        $logger->log($request->user()->id, 'delete', $table, $key, "Menghapus data {$config['label']}.");

        return back()->with('success', 'Data berhasil dihapus.');
    }

    private function config(string $type): array
    {
        abort_unless(isset(self::CONFIG[$type]), 404);

        return self::CONFIG[$type];
    }
}
