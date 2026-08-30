<?php

it('opts only interview content into the local readability scale', function (string $page) {
    $source = file_get_contents(resource_path('js/pages/'.$page));

    expect($source)
        ->toContain('css/interview-readability.css')
        ->toContain('class="')
        ->toContain('interview-readability ');
})->with([
    'InterviewPreparation.vue',
    'Interview/Prep.vue',
    'Interview/Chat.vue',
    'Interview/Live.vue',
    'Interview/Results.vue',
]);

it('keeps readability rules scoped and compact without changing layout', function () {
    $css = file_get_contents(resource_path('css/interview-readability.css'));
    preg_match_all('/([^{}]+)\{/', $css, $matches);

    foreach ($matches[1] as $selectors) {
        foreach (explode(',', $selectors) as $selector) {
            expect(trim($selector))->toStartWith('.interview-readability');
        }
    }

    expect($css)
        ->toContain('--text-xs: 0.8125rem')
        ->toContain('font-size: 0.875rem')
        ->toContain('font-size: 0.9375rem')
        ->not->toMatch('/(?:^|[;{])\s*(?:width|height|padding|margin|display)\s*:/');

    foreach (['UpcomingInterviewRow.vue', 'InterviewHistoryRow.vue'] as $component) {
        expect(file_get_contents(resource_path('js/components/interview/'.$component)))
            ->not->toContain('text-[10px]')
            ->not->toContain('text-[11px]');
    }

    expect(file_get_contents(resource_path('css/app.css')))
        ->not->toContain('interview-readability');
});
