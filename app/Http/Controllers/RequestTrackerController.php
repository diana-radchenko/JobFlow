<?php

namespace App\Http\Controllers;

use Inertia\Inertia;
use Inertia\Response;

class RequestTrackerController extends Controller
{
    public function show(): Response
    {
        return Inertia::render('RequestTracker');
    }
}
