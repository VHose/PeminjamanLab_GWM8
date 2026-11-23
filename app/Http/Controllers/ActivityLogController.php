<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ActivityLogController extends Controller
{
    public function index(Request $request): View
    {
        $search = $request->input('search');
        $actionFilter = $request->input('action');
        $dateFrom = $request->input('date_from');
        $dateTo = $request->input('date_to');

        $query = ActivityLog::with('user.roles')->latest('created_at');

        if ($actionFilter) {
            $query->where('action', $actionFilter);
        }

        if ($dateFrom) {
            $query->whereDate('created_at', '>=', $dateFrom);
        }

        if ($dateTo) {
            $query->whereDate('created_at', '<=', $dateTo);
        }

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('description', 'like', "%{$search}%")
                  ->orWhere('action', 'like', "%{$search}%")
                  ->orWhere('ip_address', 'like', "%{$search}%")
                  ->orWhereHas('user', fn ($u) => $u->where('name', 'like', "%{$search}%")->orWhere('id', 'like', "%{$search}%"));
            });
        }

        $logs = $query->take(500)->get();
        $actionList = ActivityLog::select('action')->distinct()->pluck('action');

        return view('logs.index', compact('logs', 'actionList', 'actionFilter', 'dateFrom', 'dateTo', 'search'));
    }
}
