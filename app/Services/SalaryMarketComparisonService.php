<?php

namespace App\Services;

use App\Models\WorkJob;
use Illuminate\Support\Collection;

class SalaryMarketComparisonService
{
    public function __construct(private JobTitleNormalizer $normalizer) {}

    /** @return array<string, mixed> */
    public function compare(string $title, ?string $industry, ?string $level): array
    {
        $jobs = WorkJob::published()->whereNotNull('salary_start')->whereNotNull('salary_end')
            ->when($industry, fn ($query) => $query->where('industry', $industry))
            ->when($level, fn ($query) => $query->where('position_level', $level))->get();

        $matches = $jobs->map(fn (WorkJob $job) => ['job' => $job, 'score' => $this->normalizer->similarity($title, $job->title)])
            ->filter(fn (array $match) => $match['score'] > 0)->sortByDesc('score')->take(20);

        if ($matches->count() < 2) {
            return ['sufficient' => false, 'message' => 'Not enough comparable vacancies yet', 'count' => $matches->count()];
        }

        /** @var Collection<int, WorkJob> $comparable */
        $comparable = $matches->pluck('job');
        return [
            'sufficient' => true,
            'count' => $comparable->count(),
            'minimum' => (float) $comparable->min('salary_start'),
            'maximum' => (float) $comparable->max('salary_end'),
            'median' => (float) $comparable->map(fn (WorkJob $job) => ((float) $job->salary_start + (float) $job->salary_end) / 2)->median(),
        ];
    }
}
