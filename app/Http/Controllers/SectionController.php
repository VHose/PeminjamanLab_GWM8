<?php

namespace App\Http\Controllers;

use App\Models\Course;
use App\Models\Lecturer;
use App\Models\Period;
use App\Models\Room;
use App\Models\Section;
use App\Services\ActivityLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SectionController extends Controller
{
    public function index(): View
    {
        return view('sections.index', ['sections' => Section::with(['course.studyProgram', 'room', 'lecturer', 'period'])->latest()->paginate(15)]);
    }

    public function create(): View
    {
        return $this->form();
    }

    public function edit(Section $section): View
    {
        return $this->form($section);
    }

    private function form(?Section $section = null): View
    {
        return view('sections.form', compact('section') + ['courses' => Course::with('studyProgram')->get(), 'rooms' => Room::all(), 'periods' => Period::all(), 'lecturers' => Lecturer::all()]);
    }

    public function store(Request $request, ActivityLogger $logger): RedirectResponse
    {
        $section = Section::query()->create($this->data($request));
        $logger->log($request->user()->id, 'create', 'section', $section->id);

        return redirect()->route('sections.index')->with('success', 'Jadwal kelas ditambahkan.');
    }

    public function update(Request $request, Section $section, ActivityLogger $logger): RedirectResponse
    {
        $section->update($this->data($request));
        $logger->log($request->user()->id, 'update', 'section', $section->id);

        return redirect()->route('sections.index')->with('success', 'Jadwal kelas diperbarui.');
    }

    public function destroy(Request $request, Section $section, ActivityLogger $logger): RedirectResponse
    {
        $logger->log($request->user()->id, 'delete', 'section', $section->id);
        $section->delete();

        return back()->with('success', 'Jadwal kelas dihapus.');
    }

    private function data(Request $request): array
    {
        $data = $request->validate(['course_id' => 'required|exists:course,id', 'period_id' => 'required|exists:period,id', 'room_id' => 'required|exists:room,id', 'lecturer_nik' => 'nullable|exists:lecturer,nik', 'presenter_name' => 'nullable|string|max:255', 'day_of_week' => 'required|integer|between:1,6', 'start_time' => 'required|date_format:H:i', 'end_time' => 'required|date_format:H:i|after:start_time']);
        if ($data['lecturer_nik'] ?? null) {
            $data['presenter_name'] = null;
        }

        return $data;
    }
}
