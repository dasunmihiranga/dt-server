<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Spatie\Activitylog\Models\Activity;

class ActivityController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        
        $activities = Activity::where('causer_id', $user->id)
            ->where('causer_type', 'App\Models\User')
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        return response()->json([
            'activities' => $activities,
        ]);
    }

    public function show(Request $request, $id)
    {
        $user = $request->user();
        
        $activity = Activity::where('causer_id', $user->id)
            ->where('causer_type', 'App\Models\User')
            ->findOrFail($id);

        return response()->json([
            'activity' => $activity,
        ]);
    }
}
