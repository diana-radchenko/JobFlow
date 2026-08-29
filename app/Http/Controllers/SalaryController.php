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
            'title' => ['nullable', 'string', 'max:255'],
            'industry' => ['nullable', 'required_with:title', Rule::in(config('jobs.industries'))],
            'position_level' => ['nullable', 'required_with:title', Rule::in(['Junior', 'Middle', 'Manager'])],
        ]);
        $comparison = filled($filters['title'] ?? null)
            ? $service->compare($filters['title'], $filters['industry'] ?? null, $filters['position_level'] ?? null)
            : null;

        return Inertia::render('Salary', [
            'industries' => config('jobs.industries'),
            'positionLevels' => ['Junior', 'Middle', 'Manager'],
            'filters' => $filters,
            'comparison' => $comparison,
        ]);
    }
}
