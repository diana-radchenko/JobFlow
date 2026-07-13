<?php

use App\Models\User;
use App\Models\WorkExperience;

test('authenticated user can create work experience with city country and remote flag', function () {
    $user = User::factory()->create();
    $resume = $user->resumes()->create(['title' => 'My Resume']);

    $this->actingAs($user)
        ->post(route('resume-editor.work-experience.store', $resume), [
            'company_name' => 'Acme Corp',
            'job_title' => 'Engineer',
            'city' => 'Berlin',
            'country' => 'Germany',
            'is_remote' => true,
            'start_date' => '2020-01-15',
            'end_date' => null,
            'is_current' => true,
            'description' => 'Shipped features.',
        ])
        ->assertRedirect();

    $exp = $user->fresh()->workExperiences->first();

    expect($exp)->not->toBeNull();
    expect($exp->company_name)->toBe('Acme Corp');
    expect($exp->job_title)->toBe('Engineer');
    expect($exp->city)->toBe('Berlin');
    expect($exp->country)->toBe('Germany');
    expect($exp->is_remote)->toBeTrue();
    expect($exp->description)->toBe('Shipped features.');
});

test('authenticated user can update work experience location fields', function () {
    $user = User::factory()->create();
    $resume = $user->resumes()->create(['title' => 'My Resume']);
    $workExperience = WorkExperience::create([
        'user_id' => $user->id,
        'company_name' => 'Old Co',
        'job_title' => 'Dev',
        'city' => 'Paris',
        'country' => 'France',
        'is_remote' => false,
        'start_date' => '2019-06-01',
        'end_date' => '2021-12-31',
        'is_current' => false,
    ]);

    $this->actingAs($user)
        ->put(route('resume-editor.work-experience.update', [$resume, $workExperience]), [
            'company_name' => 'New Co',
            'job_title' => 'Senior Dev',
            'city' => 'Amsterdam',
            'country' => 'Netherlands',
            'is_remote' => true,
            'start_date' => '2019-06-01',
            'end_date' => '2021-12-31',
            'is_current' => false,
            'description' => null,
        ])
        ->assertRedirect();

    $workExperience->refresh();

    expect($workExperience->company_name)->toBe('New Co');
    expect($workExperience->city)->toBe('Amsterdam');
    expect($workExperience->country)->toBe('Netherlands');
    expect($workExperience->is_remote)->toBeTrue();
});
