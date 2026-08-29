<?php

use App\Models\Project;
use App\Models\Resume;
use App\Models\Skill;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

test('authenticated user can create a resume', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->post(route('resumes.store'), ['title' => 'Frontend Developer'])
        ->assertRedirect();

    expect($user->resumes()->where('title', 'Frontend Developer')->exists())->toBeTrue();
});

test('resume index exposes deterministic strength from real resume sections', function () {
    $user = User::factory()->create();
    $user->profile()->create(['first_name' => 'Ada', 'last_name' => 'Lovelace']);
    $resume = $user->resumes()->create(['title' => 'Engineering Resume']);
    $skill = Skill::create(['user_id' => $user->id, 'name' => 'PHP', 'proficiency_level' => 'expert']);
    $resume->skills()->attach($skill->id, ['order' => 0]);

    $this->actingAs($user)->get(route('resumes.index'))
        ->assertInertia(fn (Assert $page) => $page
            ->where('resumes.0.strength', 33)
            ->where('resumes.0.strength_items.0.label', 'Contact information')
            ->where('resumes.0.strength_items.0.complete', true)
            ->where('resumes.0.strength_items.3.complete', true));
});

test('a new resume starts populated with the user\'s current pool', function () {
    $user = User::factory()->create();
    $skill = Skill::create(['user_id' => $user->id, 'name' => 'PHP', 'proficiency_level' => 'expert']);
    $project = Project::create(['user_id' => $user->id, 'title' => 'API', 'type' => 'project']);

    $this->actingAs($user)
        ->post(route('resumes.store'), ['title' => 'Backend Developer'])
        ->assertRedirect();

    $resume = $user->resumes()->where('title', 'Backend Developer')->first();

    expect($resume->skills->pluck('id'))->toContain($skill->id);
    expect($resume->projects->pluck('id'))->toContain($project->id);
});

test('authenticated user can rename a resume', function () {
    $user = User::factory()->create();
    $resume = $user->resumes()->create(['title' => 'Old Title']);

    $this->actingAs($user)
        ->put(route('resumes.update', $resume), ['title' => 'New Title'])
        ->assertRedirect();

    expect($resume->fresh()->title)->toBe('New Title');
});

test('authenticated user can delete a resume', function () {
    $user = User::factory()->create();
    $resume = $user->resumes()->create(['title' => 'To Delete']);

    $this->actingAs($user)
        ->delete(route('resumes.destroy', $resume))
        ->assertRedirect();

    expect(Resume::find($resume->id))->toBeNull();
});

test('a user cannot rename another user\'s resume', function () {
    $owner = User::factory()->create();
    $intruder = User::factory()->create();
    $resume = $owner->resumes()->create(['title' => 'Private']);

    $this->actingAs($intruder)
        ->put(route('resumes.update', $resume), ['title' => 'Hacked'])
        ->assertForbidden();

    expect($resume->fresh()->title)->toBe('Private');
});

test('a user cannot delete another user\'s resume', function () {
    $owner = User::factory()->create();
    $intruder = User::factory()->create();
    $resume = $owner->resumes()->create(['title' => 'Private']);

    $this->actingAs($intruder)
        ->delete(route('resumes.destroy', $resume))
        ->assertForbidden();

    expect(Resume::find($resume->id))->not->toBeNull();
});

test('duplicating a resume copies its title and item selections', function () {
    $user = User::factory()->create();
    $skill = Skill::create(['user_id' => $user->id, 'name' => 'PHP', 'proficiency_level' => 'expert']);
    $resume = $user->resumes()->create(['title' => 'Original']);
    $resume->skills()->attach($skill->id, ['order' => 0]);

    $this->actingAs($user)
        ->post(route('resumes.duplicate', $resume))
        ->assertRedirect();

    $copy = $user->resumes()->where('title', 'Original (copy)')->first();

    expect($copy)->not->toBeNull();
    expect($copy->skills->pluck('id'))->toContain($skill->id);
});

test('a user can manually add a research project to a resume', function () {
    $user = User::factory()->create();
    $resume = $user->resumes()->create(['title' => 'My Resume']);

    $this->actingAs($user)
        ->post(route('resume-editor.project.store', $resume), [
            'title' => 'Distributed Systems Paper',
            'type' => 'research',
            'description' => 'Co-authored a peer-reviewed paper.',
        ])
        ->assertRedirect();

    expect($user->projects()->where('title', 'Distributed Systems Paper')->where('type', 'research')->exists())->toBeTrue();
});

