<?php

use App\Models\User;
use App\Models\WorkJob;
use App\Services\SalaryMarketComparisonService;

function salaryVacancy(array $attributes = []): WorkJob
{
    return WorkJob::factory()
        ->for(User::factory()->employer()->create(), 'employer')
        ->create(array_merge([
            'title' => 'Coding Instructor',
            'industry' => 'Education & Training',
            'position_level' => 'Junior',
            'salary_start' => 50000,
            'salary_end' => 70000,
            'salary_currency' => 'USD',
            'salary_period' => 'year',
            'status' => 'published',
            'published_at' => now(),
        ], $attributes));
}

function compareSalary(string $title = 'Coding Instructor'): array
{
    return app(SalaryMarketComparisonService::class)
        ->compare($title, 'Education & Training', 'Junior');
}

test('an exact title is comparable', function () {
    salaryVacancy();

    $result = compareSalary();

    expect($result['count'])->toBe(1)
        ->and($result['comparables'][0]['title'])->toBe('Coding Instructor');
});

test('a similar normalized core title is comparable', function () {
    salaryVacancy(['title' => 'Summer Camp Coding Instructor']);
    salaryVacancy(['title' => 'Online Coding / IT Instructor & Teaching Assistant']);
    salaryVacancy(['title' => 'Programming Instructor']);

    $result = compareSalary();

    expect($result['count'])->toBe(3)
        ->and(collect($result['comparables'])->pluck('title')->all())->toContain(
            'Summer Camp Coding Instructor',
            'Online Coding / IT Instructor & Teaching Assistant',
            'Programming Instructor',
        );
});

test('a vacancy from the wrong industry is excluded', function () {
    salaryVacancy();
    salaryVacancy(['industry' => 'Healthcare']);

    expect(compareSalary()['count'])->toBe(1);
});

test('a vacancy from the wrong position level is excluded', function () {
    salaryVacancy();
    salaryVacancy(['position_level' => 'Manager']);

    expect(compareSalary()['count'])->toBe(1);
});

test('salary periods normalize to the same annual basis', function () {
    salaryVacancy(['salary_start' => 25, 'salary_end' => 35, 'salary_period' => 'hour']);
    salaryVacancy(['salary_start' => 5000, 'salary_end' => 6000, 'salary_period' => 'month']);

    $result = compareSalary();

    expect($result['minimum'])->toBe(52000.0)
        ->and($result['median'])->toBe(64200.0)
        ->and($result['maximum'])->toBe(72800.0)
        ->and($result['period'])->toBe('year');
});

test('one listing does not claim to be a market distribution', function () {
    salaryVacancy();

    $result = compareSalary();

    expect($result['sufficient'])->toBeFalse()
        ->and($result['message'])->toBe('Only 1 comparable JobFlow vacancy is currently available. More data is needed to estimate a market distribution.')
        ->and($result['minimum'])->toBe(50000.0)
        ->and($result['maximum'])->toBe(70000.0);
});

test('multiple listings produce low median and high values from real salaries', function () {
    salaryVacancy(['salary_start' => 40000, 'salary_end' => 60000]);
    salaryVacancy(['salary_start' => 60000, 'salary_end' => 80000]);
    salaryVacancy(['salary_start' => 80000, 'salary_end' => 100000]);

    $result = compareSalary();

    expect($result['sufficient'])->toBeTrue()
        ->and($result['count'])->toBe(3)
        ->and($result['minimum'])->toBe(40000.0)
        ->and($result['median'])->toBe(70000.0)
        ->and($result['maximum'])->toBe(100000.0);
});

test('an unpublished vacancy is excluded', function () {
    salaryVacancy();
    salaryVacancy(['status' => 'draft']);

    expect(compareSalary()['count'])->toBe(1);
});
