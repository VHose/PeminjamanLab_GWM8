<?php

namespace App\Http\Controllers;

use App\Constants\ScheduleType;
use App\Models\Course;
use App\Models\Period;
use App\Models\Room;
use App\Models\Section;
use App\Models\User;
use App\Services\ActivityLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SectionController extends Controller
{
    public function index(): View
    {
        $sections = Section::with(['course.studyProgram', 'room', 'lecturer', 'period'])
            ->latest()
            ->paginate(15);

        return view('sections.index', compact('sections'));
    }

    public function create(): View
    {
        $courses = Course::where('active', true)->with('studyProgram')->get();
        $rooms = Room::where('active', true)->orderBy('code')->get();
        $periods = Period::where('is_active', true)->get();
        
        $today = now()->toDateString();
        $lecturers = User::whereHas('roles', function ($q) use ($today) {
            $q->where('name', 'Dosen')
              ->where('start_date', '<=', $today)
              ->where(function ($sub) use ($today) {
                  $sub->whereNull('end_date')->orWhere('end_date', '>=', $today);
              });
        })->get();

        return view('sections.create', compact('courses', 'rooms', 'periods', 'lecturers'));
    }

    public function edit(Section $section): View
    {
        $courses = Course::where('active', true)->with('studyProgram')->get();
        $rooms = Room::where('active', true)->orderBy('code')->get();
        $periods = Period::where('is_active', true)->get();
        
        $today = now()->toDateString();
        $lecturers = User::whereHas('roles', function ($q) use ($today) {
            $q->where('name', 'Dosen')
              ->where('start_date', '<=', $today)
              ->where(function ($sub) use ($today) {
                  $sub->whereNull('end_date')->orWhere('end_date', '>=', $today);
              });
        })->get();

        return view('sections.edit', compact('section', 'courses', 'rooms', 'periods', 'lecturers'));
    }

    public function store(Request $request, ActivityLogger $logger): RedirectResponse
    {
        $section = Section::query()->create($this->validateData($request));
        $logger->log($request->user()->id, 'create', 'section', (string) $section->id, 'Membuat jadwal kelas baru.');

        return redirect()->route('sections.index')->with('success', 'Jadwal kelas berhasil ditambahkan.');
    }

    public function update(Request $request, Section $section, ActivityLogger $logger): RedirectResponse
    {
        $section->update($this->validateData($request));
        $logger->log($request->user()->id, 'update', 'section', (string) $section->id, 'Memperbarui jadwal kelas.');

        return redirect()->route('sections.index')->with('success', 'Jadwal kelas berhasil diperbarui.');
    }

    public function destroy(Request $request, Section $section, ActivityLogger $logger): RedirectResponse
    {
        $id = (string) $section->id;
        $section->delete();
        $logger->log($request->user()->id, 'delete', 'section', $id, 'Menghapus jadwal kelas.');

        return back()->with('success', 'Jadwal kelas berhasil dihapus.');
    }

    private function validateData(Request $request): array
    {
        $data = $request->validate([
            'course_id' => 'required|exists:course,id',
            'period_id' => 'required|exists:period,id',
            'room_id' => 'required|exists:room,id',
            'lecturer_nik' => 'nullable|exists:user,id',
            'class_code' => 'required|string|max:10',
            'day_of_week' => 'required|integer|between:1,7',
            'start_time' => 'required|date_format:H:i',
            'end_time' => 'required|date_format:H:i|after:start_time',
            'schedule_type' => 'required|integer|in:0,1',
            'quota' => 'nullable|integer|min:1',
        ]);

        return $data;
    }
}
