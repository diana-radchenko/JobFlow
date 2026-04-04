<?php

namespace App\Http\Controllers;

use App\Http\Requests\JobSelectionRequest;
use App\Models\WorkJob;
use Inertia\Inertia;
use Inertia\Response;

class JobSelectionController extends Controller
{
    public function jobSelection(JobSelectionRequest $request): Response
    {
        $query = WorkJob::query();

        if ($request->filled('region') && $request->region !== 'does-not-matter') {
            $query->where('location', 'like', '%'.$request->region.'%');
        }

        if ($request->filled('incomeLevel') && $request->incomeLevel !== 'does-not-matter') {
            if ($request->incomeLevel === 'own' && $request->filled('ownSalary')) {
                $query->where('salary_start', '>=', $request->ownSalary);
            } elseif (is_numeric($request->incomeLevel)) {
                $query->where('salary_start', '>=', $request->incomeLevel);
            }
        }

        $jobs = $query->get();

        return Inertia::render('JobSelection', [
            'jobs' => $jobs,
            'filters' => $request->only(['incomeLevel', 'region', 'ownSalary']),
        ]);
    }
}
