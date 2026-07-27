<?php

use App\Ai\Tools\SaveLeadershipActivity;
use App\Ai\Tools\SaveVolunteerExperience;
use App\Data\InterviewContextData;
use App\Data\ResumeScoreContextData;
use App\Models\LeadershipActivity;
use App\Models\User;
use App\Models\VolunteerExperience;
use Laravel\Ai\Tools\Request as ToolRequest;

/**
 * @return array<string, mixed>
 */
function activityPayload(array $overrides = []): array
{
    return array_merge([
        'organization' => 'Red Cross',
        'role' => 'Volunteer Coordinator',
        'description' => 'Ran the weekend blood drive.',
        'url' => 'https://redcross.org',
        'city' => 'Tashkent',
        'country' => 'Uzbekistan',
        'start_date' => '2022-03-01',
        'end_date' => null,
        'is_current' => true,
    ], $overrides);
}

test('user can create a volunteer experience and it is attached to the resume', function () {
    $user = User::factory()->create();
    $resume = $user->resumes()->create(['title' => 'My Resume']);

    $this->actingAs($user)
        ->post(route('resume-editor.volunteer-experience.store', $resume), activityPayload())
        ->assertRedirect();

    $item = $user->fresh()->volunteerExperiences->first();

    expect($item)->not->toBeNull();
    expect($item->organization)->toBe('Red Cross');
    expect($item->role)->toBe('Volunteer Coordinator');
    expect($item->city)->toBe('Tashkent');
    expect($item->is_current)->toBeTrue();
    expect($resume->fresh()->volunteerExperiences->pluck('id'))->toContain($item->id);
});

test('user can create a leadership activity and it is attached to the resume', function () {
    $user = User::factory()->create();
    $resume = $user->resumes()->create(['title' => 'My Resume']);

    $this->actingAs($user)
        ->post(route('resume-editor.leadership-activity.store', $resume), activityPayload([
            'organization' => 'IEEE Student Branch',
            'role' => 'President',
        ]))
        ->assertRedirect();

    $item = $user->fresh()->leadershipActivities->first();

    expect($item)->not->toBeNull();
    expect($item->organization)->toBe('IEEE Student Branch');
    expect($item->role)->toBe('President');
    expect($resume->fresh()->leadershipActivities->pluck('id'))->toContain($item->id);
});

test('organization and role are required', function () {
    $user = User::factory()->create();
    $resume = $user->resumes()->create(['title' => 'My Resume']);

    $this->actingAs($user)
        ->post(route('resume-editor.volunteer-experience.store', $resume), activityPayload([
            'organization' => '',
            'role' => '',
        ]))
        ->assertSessionHasErrors(['organization', 'role']);

    $this->actingAs($user)
        ->post(route('resume-editor.leadership-activity.store', $resume), activityPayload([
            'organization' => '',
            'role' => '',
        ]))
        ->assertSessionHasErrors(['organization', 'role']);
});

test('user can update and delete a volunteer experience', function () {
    $user = User::factory()->create();
    $resume = $user->resumes()->create(['title' => 'My Resume']);
    $item = $user->volunteerExperiences()->create(activityPayload());

    $this->actingAs($user)
        ->put(route('resume-editor.volunteer-experience.update', [$resume, $item]), activityPayload([
            'organization' => 'Habitat for Humanity',
            'role' => 'Site Lead',
        ]))
        ->assertRedirect();

    expect($item->fresh()->organization)->toBe('Habitat for Humanity');

    $this->actingAs($user)
        ->delete(route('resume-editor.volunteer-experience.destroy', [$resume, $item]))
        ->assertRedirect();

    expect(VolunteerExperience::find($item->id))->toBeNull();
});

test('user can update and delete a leadership activity', function () {
    $user = User::factory()->create();
    $resume = $user->resumes()->create(['title' => 'My Resume']);
    $item = $user->leadershipActivities()->create(activityPayload());

    $this->actingAs($user)
        ->put(route('resume-editor.leadership-activity.update', [$resume, $item]), activityPayload([
            'role' => 'Vice President',
        ]))
        ->assertRedirect();

    expect($item->fresh()->role)->toBe('Vice President');

    $this->actingAs($user)
        ->delete(route('resume-editor.leadership-activity.destroy', [$resume, $item]))
        ->assertRedirect();

    expect(LeadershipActivity::find($item->id))->toBeNull();
});

test('another user cannot update or delete these items', function () {
    $owner = User::factory()->create();
    $intruder = User::factory()->create();
    $resume = $owner->resumes()->create(['title' => 'My Resume']);
    $volunteer = $owner->volunteerExperiences()->create(activityPayload());
    $leadership = $owner->leadershipActivities()->create(activityPayload());

    $this->actingAs($intruder)
        ->put(route('resume-editor.volunteer-experience.update', [$resume, $volunteer]), activityPayload())
        ->assertForbidden();

    $this->actingAs($intruder)
        ->delete(route('resume-editor.leadership-activity.destroy', [$resume, $leadership]))
        ->assertForbidden();
});

test('toggling excludes an item from the resume without deleting it', function () {
    $user = User::factory()->create();
    $resume = $user->resumes()->create(['title' => 'My Resume']);
    $volunteer = $user->volunteerExperiences()->create(activityPayload());
    $leadership = $user->leadershipActivities()->create(activityPayload());

    $resume->volunteerExperiences()->attach($volunteer->id, ['order' => 0]);
    $resume->leadershipActivities()->attach($leadership->id, ['order' => 0]);

    $this->actingAs($user)
        ->post(route('resume-editor.items.toggle', [$resume, 'volunteer-experience', $volunteer->id]))
        ->assertRedirect();

    $this->actingAs($user)
        ->post(route('resume-editor.items.toggle', [$resume, 'leadership-activity', $leadership->id]))
        ->assertRedirect();

    expect($resume->fresh()->volunteerExperiences)->toBeEmpty();
    expect($resume->fresh()->leadershipActivities)->toBeEmpty();
    expect(VolunteerExperience::find($volunteer->id))->not->toBeNull();
    expect(LeadershipActivity::find($leadership->id))->not->toBeNull();
});

test('new and duplicated resumes carry both sections across', function () {
    $user = User::factory()->create();
    $user->volunteerExperiences()->create(activityPayload());
    $user->leadershipActivities()->create(activityPayload());

    $this->actingAs($user)
        ->post(route('resumes.store'), ['title' => 'From Pool'])
        ->assertRedirect();

    $resume = $user->fresh()->resumes()->latest('id')->first();

    expect($resume->volunteerExperiences)->toHaveCount(1);
    expect($resume->leadershipActivities)->toHaveCount(1);

    $this->actingAs($user)
        ->post(route('resumes.duplicate', $resume))
        ->assertRedirect();

    $copy = $user->fresh()->resumes()->latest('id')->first();

    expect($copy->volunteerExperiences)->toHaveCount(1);
    expect($copy->leadershipActivities)->toHaveCount(1);
});

test('resume editor payload exposes both sections with inclusion flags', function () {
    $user = User::factory()->create();
    $resume = $user->resumes()->create(['title' => 'My Resume']);
    $volunteer = $user->volunteerExperiences()->create(activityPayload());
    $user->leadershipActivities()->create(activityPayload());

    $resume->volunteerExperiences()->attach($volunteer->id, ['order' => 0]);

    $this->actingAs($user)
        ->get(route('resume-editor.show', $resume))
        ->assertInertia(fn ($page) => $page
            ->has('volunteerExperiences', 1)
            ->has('leadershipActivities', 1)
            ->where('volunteerExperiences.0.included', true)
            ->where('leadershipActivities.0.included', false)
        );
});

test('ai resume context includes both sections', function () {
    $user = User::factory()->create();
    $resume = $user->resumes()->create(['title' => 'My Resume']);
    $volunteer = $user->volunteerExperiences()->create(activityPayload());
    $leadership = $user->leadershipActivities()->create(activityPayload([
        'organization' => 'Debate Club',
        'role' => 'Captain',
    ]));

    $resume->volunteerExperiences()->attach($volunteer->id, ['order' => 0]);
    $resume->leadershipActivities()->attach($leadership->id, ['order' => 0]);

    $resumeContext = json_decode(
        ResumeScoreContextData::fromResume($resume, job: [])->resumeContext(),
        true,
    );

    expect($resumeContext['volunteer_experiences'][0]['organization'])->toBe('Red Cross');
    expect($resumeContext['leadership_activities'][0]['role'])->toBe('Captain');

    $interviewContext = json_decode(
        InterviewContextData::fromUser($user->fresh())->resumeContext(),
        true,
    );

    expect($interviewContext['volunteer_experiences'][0]['organization'])->toBe('Red Cross');
    expect($interviewContext['leadership_activities'][0]['organization'])->toBe('Debate Club');
});

test('ai tools save and attach both section types', function () {
    $user = User::factory()->create();
    $resume = $user->resumes()->create(['title' => 'My Resume']);

    (new SaveVolunteerExperience($resume))->handle(new ToolRequest(activityPayload()));
    (new SaveLeadershipActivity($resume))->handle(new ToolRequest(activityPayload([
        'organization' => 'Robotics Club',
        'role' => 'Team Captain',
    ])));

    $resume->refresh();

    expect($resume->volunteerExperiences)->toHaveCount(1);
    expect($resume->volunteerExperiences->first()->organization)->toBe('Red Cross');
    expect($resume->leadershipActivities)->toHaveCount(1);
    expect($resume->leadershipActivities->first()->role)->toBe('Team Captain');
});
