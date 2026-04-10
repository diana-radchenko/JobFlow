<?php

namespace App\Http\Controllers;

use App\Models\UserWorkJobApplication;
use Inertia\Inertia;
use Inertia\Response;

class RequestTrackerController extends Controller
{
    public function show(): Response
    {
        $applications = UserWorkJobApplication::with(['workJob'])
            ->where('user_id', auth()->id())
            ->get();

        return Inertia::render('RequestTracker', [
            'applications' => $applications,
        ]);
    }
}
