<?php

use App\Models\Skill;
use App\Models\User;

test('toggling a skill excludes it from a resume without deleting it', function () {
    $user = User::factory()->create();
    $skill = Skill::create(['user_id' => $user->id, 'name' => 'PHP', 'proficiency_level' => 'expert']);
    $resume = $user->resumes()->create(['title' => 'My Resume']);
    $resume->skills()->attach($skill->id, ['order' => 0]);

    $this->actingAs($user)
        ->post(route('resume-editor.items.toggle', [$resume, 'skill', $skill]))
        ->assertRedirect();

    expect($resume->skills()->where('skill_id', $skill->id)->exists())->toBeFalse();
    expect(Skill::find($skill->id))->not->toBeNull();

    $this->actingAs($user)
        ->post(route('resume-editor.items.toggle', [$resume, 'skill', $skill]))
        ->assertRedirect();

    expect($resume->skills()->where('skill_id', $skill->id)->exists())->toBeTrue();
});

test('reordering persists the new order for included items', function () {
    $user = User::factory()->create();
    $skillA = Skill::create(['user_id' => $user->id, 'name' => 'PHP', 'proficiency_level' => 'expert']);
    $skillB = Skill::create(['user_id' => $user->id, 'name' => 'JavaScript', 'proficiency_level' => 'advanced']);
    $resume = $user->resumes()->create(['title' => 'My Resume']);
    $resume->skills()->attach([$skillA->id => ['order' => 0], $skillB->id => ['order' => 1]]);

    $this->actingAs($user)
        ->post(route('resume-editor.items.reorder', [$resume, 'skill']), [
            'ids' => [$skillB->id, $skillA->id],
        ])
        ->assertRedirect();

    $ordered = $resume->skills()->orderByPivot('order')->pluck('skills.id');

    expect($ordered->first())->toBe($skillB->id);
    expect($ordered->last())->toBe($skillA->id);
});

test('deleting a skill removes it from every resume that included it', function () {
    $user = User::factory()->create();
    $skill = Skill::create(['user_id' => $user->id, 'name' => 'PHP', 'proficiency_level' => 'expert']);
    $resumeA = $user->resumes()->create(['title' => 'Resume A']);
    $resumeB = $user->resumes()->create(['title' => 'Resume B']);
    $resumeA->skills()->attach($skill->id, ['order' => 0]);
    $resumeB->skills()->attach($skill->id, ['order' => 0]);

    $this->actingAs($user)
        ->delete(route('resume-editor.skill.destroy', [$resumeA, $skill]))
        ->assertRedirect();

    expect($resumeA->skills()->where('skill_id', $skill->id)->exists())->toBeFalse();
    expect($resumeB->skills()->where('skill_id', $skill->id)->exists())->toBeFalse();
});

test('a user cannot toggle another user\'s skill into their own resume', function () {
    $owner = User::factory()->create();
    $intruder = User::factory()->create();
    $skill = Skill::create(['user_id' => $owner->id, 'name' => 'PHP', 'proficiency_level' => 'expert']);
    $intruderResume = $intruder->resumes()->create(['title' => 'Intruder Resume']);

    $this->actingAs($intruder)
        ->post(route('resume-editor.items.toggle', [$intruderResume, 'skill', $skill]))
        ->assertNotFound();

    expect($intruderResume->skills()->where('skill_id', $skill->id)->exists())->toBeFalse();
});
