<?php

namespace App\Http\Controllers;

use App\Models\WorkJob;
use Illuminate\Http\RedirectResponse;

class SavedJobController extends Controller
{
    public function store(WorkJob $job): RedirectResponse
    {
        abort_unless($job->user_id !== null && $job->status === 'published', 404);
        auth()->user()->savedWorkJobs()->syncWithoutDetaching([$job->id]);

        return back();
    }

    public function destroy(WorkJob $job): RedirectResponse
    {
        auth()->user()->savedWorkJobs()->detach($job->id);

        return back();
    }
}
