<?php

namespace App\Http\Controllers;

use App\Services\SalaryMarketComparisonService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class SalaryController extends Controller
{
    public function __invoke(Request $request, SalaryMarketComparisonService $service): Response
    {
        $filters = $request->validate([
            'application_id' => ['nullable', 'integer'],
            'title' => ['nullable', 'string', 'max:255'],
            'industry' => ['nullable', 'required_with:title', Rule::in(config('jobs.industries'))],
            'position_level' => ['nullable', 'required_with:title', Rule::in(['Junior', 'Middle', 'Manager'])],
        ]);
        $applications = $request->user()->applications()
            ->with('workJob:id,title,company,industry,position_level,salary_start,salary_end,salary_currency,salary_period')
            ->latest()
            ->get();
        $selectedApplication = filled($filters['application_id'] ?? null)
            ? $applications->firstWhere('id', (int) $filters['application_id'])
            : null;
        abort_if(filled($filters['application_id'] ?? null) && ! $selectedApplication, 403);

        if ($selectedApplication?->workJob) {
            $filters['title'] = $selectedApplication->workJob->title;
            $filters['industry'] = $selectedApplication->workJob->industry;
            $filters['position_level'] = $selectedApplication->workJob->position_level;
        }
        $comparison = filled($filters['title'] ?? null)
            ? $service->compare($filters['title'], $filters['industry'] ?? null, $filters['position_level'] ?? null)
            : null;

        return Inertia::render('Salary', [
            'resumes' => $request->user()->resumes()->latest('updated_at')->get(['id', 'title']),
            'industries' => config('jobs.industries'),
            'positionLevels' => ['Junior', 'Middle', 'Manager'],
            'filters' => $filters,
            'comparison' => $comparison,
            'applications' => $applications,
            'selectedApplicationId' => $selectedApplication?->id,
        ]);
    }
}

