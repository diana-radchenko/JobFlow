<?php

namespace App\Http\Controllers;

use Inertia\Inertia;
use Inertia\Response;

class JobSelectionController extends Controller
{
    public function jobSelection(): Response
    {
        return Inertia::render('JobSelection');
    }
}
