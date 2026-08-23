<?php

use App\Models\AwardHonor;
use App\Models\Language;
use App\Models\Publication;
use App\Models\User;

test('candidate can add publication award and language to a resume', function () {
    $user = User::factory()->create();
    $resume = $user->resumes()->create(['title' => 'Academic Resume']);

    $this->actingAs($user)->post(route('resume-editor.publication.store', $resume), [
        'title' => 'Responsible AI in Hiring',
        'publisher' => 'Career Technology Journal',
        'publication_date' => '2026-04-10',
        'url' => 'https://example.com/responsible-ai',
        'description' => 'A peer-reviewed article.',
    ])->assertRedirect();

    $this->actingAs($user)->post(route('resume-editor.award-honor.store', $resume), [
        'title' => 'Best Student Project',
        'issuer' => 'University Innovation Lab',
        'awarded_date' => '2026-05-20',
    ])->assertRedirect();

    $this->actingAs($user)->post(route('resume-editor.language.store', $resume), [
        'name' => 'English',
        'proficiency' => 'Fluent',
    ])->assertRedirect();

    expect($resume->fresh()->publications)->toHaveCount(1)
        ->and($resume->fresh()->awardHonors)->toHaveCount(1)
        ->and($resume->fresh()->languages)->toHaveCount(1);
});

test('candidate can update and delete the new resume entries', function () {
    $user = User::factory()->create();
    $resume = $user->resumes()->create(['title' => 'Resume']);
    $publication = Publication::create(['user_id' => $user->id, 'title' => 'Draft']);
    $award = AwardHonor::create(['user_id' => $user->id, 'title' => 'Nominee']);
    $language = Language::create(['user_id' => $user->id, 'name' => 'French', 'proficiency' => 'Basic']);

    $this->actingAs($user)->put(route('resume-editor.publication.update', [$resume, $publication]), [
        'title' => 'Published Article',
    ])->assertRedirect();
    $this->actingAs($user)->put(route('resume-editor.language.update', [$resume, $language]), [
        'name' => 'French', 'proficiency' => 'Intermediate',
    ])->assertRedirect();
    $this->actingAs($user)->delete(route('resume-editor.award-honor.destroy', [$resume, $award]))->assertRedirect();

    expect($publication->fresh()->title)->toBe('Published Article')
        ->and($language->fresh()->proficiency)->toBe('Intermediate')
        ->and(AwardHonor::find($award->id))->toBeNull();
});

test('candidate cannot modify another user new resume entries', function () {
    $owner = User::factory()->create();
    $intruder = User::factory()->create();
    $resume = $owner->resumes()->create(['title' => 'Private']);
    $publication = Publication::create(['user_id' => $owner->id, 'title' => 'Private Paper']);

    $this->actingAs($intruder)->put(route('resume-editor.publication.update', [$resume, $publication]), [
        'title' => 'Changed',
    ])->assertForbidden();

    expect($publication->fresh()->title)->toBe('Private Paper');
});

