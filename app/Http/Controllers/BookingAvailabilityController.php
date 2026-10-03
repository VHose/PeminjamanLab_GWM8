<?php

namespace App\Http\Controllers;

use App\Models\Room;
use App\Services\BookingService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BookingAvailabilityController extends Controller
{
    public function __invoke(Request $request, BookingService $service): JsonResponse
    {
        $data = $request->validate(['room_id' => 'required|exists:room,id', 'date' => 'required|date']);

        return response()->json(['unavailable' => $service->unavailableRanges(Room::findOrFail($data['room_id'])->id, Carbon::parse($data['date']))]);
    }
}
