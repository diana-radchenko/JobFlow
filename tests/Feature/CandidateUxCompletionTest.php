<?php

test('career development exposes real article destinations without audiobooks', function () {
    $page = file_get_contents(resource_path('js/pages/Development.vue'));

    expect($page)
        ->toContain('https://proximus.talent-pool.com/freelance')
        ->toContain('https://www.weforum.org/publications/the-future-of-jobs-report-2025/')
        ->toContain('https://www.aarp.org/work/job-search/in-demand-job-fields-workers-over-50/')
        ->not->toContain('Audiobooks')
        ->not->toContain('/articles/');
});

test('application tracker uses clearer visible terminology without changing its route', function () {
    $sidebar = file_get_contents(resource_path('js/components/AppSidebar.vue'));
    $page = file_get_contents(resource_path('js/pages/RequestTracker.vue'));

    expect($sidebar)->toContain("title: 'Application Tracker'")
        ->and($page)->toContain('Application Tracker')
        ->and(route('request-tracker'))->toEndWith('/request-tracker');
});

test('candidate date displays use the English locale', function () {
    foreach ([
        resource_path('js/pages/Dashboard.vue'),
        resource_path('js/pages/InterviewPreparation.vue'),
        resource_path('js/pages/RequestTracker.vue'),
        resource_path('js/pages/Resumes/Index.vue'),
        resource_path('js/pages/Chat/Index.vue'),
    ] as $path) {
        $source = file_get_contents($path);

        expect($source)
            ->not->toContain('Intl.DateTimeFormat(undefined')
            ->not->toContain('toLocaleDateString(undefined')
            ->not->toContain('toLocaleDateString([]')
            ->not->toContain('toLocaleDateString()')
            ->not->toContain('toLocaleString([]')
            ->not->toContain('toLocaleString()');
    }
});

