<?php

namespace App\Http\Controllers;

use App\Services\SalaryMarketComparisonService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class SalaryController extends Controller
{
    public function __invoke(Request $request, SalaryMarketComparisonService $service): Response
    {
        $comparison = $request->filled('title') ? $service->compare((string) $request->title, $request->industry, $request->position_level) : null;
        return Inertia::render('Salary', [
            'resumes' => $request->user()->resumes()->latest('updated_at')->get(['id', 'title']),
            'jobs' => \App\Models\WorkJob::published()->whereNotNull('salary_start')->whereNotNull('salary_end')->latest()->get(),
            'industries' => config('jobs.industries'), 'positionLevels' => config('jobs.position_levels'),
            'comparison' => $comparison,
        ]);
    }
}
