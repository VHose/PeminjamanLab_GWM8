<?php

namespace App\Http\Controllers;

use App\Models\BookingRoom;
use App\Models\Period;
use App\Models\Room;
use App\Models\Section;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(Request $request): View
    {
        $period = Period::latest('start_date')->first();
        $week = max(1, (int) $request->input('week', 1));
        $day = (int) $request->input('day', 1);
        $date = $period ? Carbon::parse($period->start_date)->addWeeks($week - 1)->addDays($day - 1) : now();
        $rooms = Room::when($request->room_id, fn ($q, $id) => $q->whereKey($id))->orderBy('name')->get();
        $sections = $period ? Section::with('course.studyProgram', 'lecturer')->where(['period_id' => $period->id, 'day_of_week' => $day])->get() : collect();
        $bookings = BookingRoom::with('booking')->whereDate('start_datetime', $date)->whereHas('booking', fn ($q) => $q->whereIn('status', ['pending', 'approved']))->get();

        return view('welcome', compact('period', 'week', 'day', 'date', 'rooms', 'sections', 'bookings'));
    }
}
