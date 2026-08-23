<?php

namespace App\Http\Controllers;

use App\Models\UserWorkJobApplication;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class RequestTrackerController extends Controller
{
    public function show(): Response
    {
        $applications = UserWorkJobApplication::with(['workJob', 'interviewSession'])
            ->where('user_id', auth()->id())
            ->get();

        return Inertia::render('RequestTracker', [
            'applications' => $applications,
        ]);
    }

    public function destroy(UserWorkJobApplication $userWorkJobApplication): RedirectResponse
    {
        abort_unless($userWorkJobApplication->user_id === auth()->id(), 403);

        $userWorkJobApplication->delete();

        return redirect()->route('request-tracker');
    }
}
