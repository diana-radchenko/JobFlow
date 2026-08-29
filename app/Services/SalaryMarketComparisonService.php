<?php

namespace App\Services;

use App\Models\WorkJob;

class SalaryMarketComparisonService
{
    private const PERIOD_MULTIPLIERS = [
        'hour' => 2080,
        'hourly' => 2080,
        'week' => 52,
        'weekly' => 52,
        'month' => 12,
        'monthly' => 12,
        'year' => 1,
        'annual' => 1,
        'annually' => 1,
        'yearly' => 1,
    ];

    public function __construct(private JobTitleNormalizer $normalizer) {}

    /** @return array<string, mixed> */
    public function compare(string $title, ?string $industry, ?string $level): array
    {
        $jobs = WorkJob::published()
            ->whereNotNull('salary_start')
            ->whereNotNull('salary_end')
            ->when($industry, fn ($query) => $query->whereIn('industry', [
                $industry,
                ...config("jobs.industry_aliases.{$industry}", []),
            ]))
            ->when($level, fn ($query) => $query->where('position_level', $level))
            ->get();

        $matches = $jobs
            ->filter(fn (WorkJob $job) => $this->normalizer->comparable($title, $job->title))
            ->map(fn (WorkJob $job) => $this->normalizeJob($job, $title))
            ->sortByDesc('similarity')
            ->take(20)
            ->values();

        // Exchange-rate guessing would manufacture data, so comparisons use the
        // currency represented by the largest real comparable group.
        $matches = $matches->groupBy('currency')->sortByDesc->count()->first() ?? collect();
        $count = $matches->count();

        if ($count === 0) {
            return [
                'sufficient' => false,
                'message' => 'No comparable JobFlow vacancies are currently available.',
                'count' => 0,
                'comparables' => [],
            ];
        }

        $low = (float) $matches->min('annual_min');
        $high = (float) $matches->max('annual_max');
        $median = (float) $matches->pluck('annual_midpoint')->median();
        $currency = $matches->first()['currency'];

        if ($count === 1) {
            return [
                'sufficient' => false,
                'message' => 'Only 1 comparable JobFlow vacancy is currently available. More data is needed to estimate a market distribution.',
                'count' => 1,
                'minimum' => $low,
                'maximum' => $high,
                'median' => $median,
                'currency' => $currency,
                'period' => 'year',
                'comparables' => $matches->all(),
            ];
        }

        return [
            'sufficient' => true,
            'count' => $count,
            'minimum' => $low,
            'maximum' => $high,
            'median' => $median,
            'currency' => $currency,
            'period' => 'year',
            'comparables' => $matches->all(),
        ];
    }

    /** @return array<string, mixed> */
    private function normalizeJob(WorkJob $job, string $requestedTitle): array
    {
        $multiplier = self::PERIOD_MULTIPLIERS[strtolower($job->salary_period ?? 'year')] ?? 1;
        $annualMin = (float) $job->salary_start * $multiplier;
        $annualMax = (float) $job->salary_end * $multiplier;

        return [
            'id' => $job->id,
            'title' => $job->title,
            'company' => $job->company,
            'annual_min' => $annualMin,
            'annual_max' => $annualMax,
            'annual_midpoint' => ($annualMin + $annualMax) / 2,
            'currency' => $job->salary_currency ?? 'USD',
            'original_period' => $job->salary_period ?? 'year',
            'similarity' => $this->normalizer->similarity($requestedTitle, $job->title),
            'reasons' => [
                'Same industry',
                'Same position level',
                'Similar core role: '.implode(', ', $this->normalizer->sharedKeywords($requestedTitle, $job->title)),
            ],
        ];
    }
}
