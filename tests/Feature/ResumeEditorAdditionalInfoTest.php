<?php

use App\Models\User;

test('authenticated user can save additional info with languages and interests as lists', function () {
    $user = User::factory()->create();
    $resume = $user->resumes()->create(['title' => 'My Resume']);

    $this->actingAs($user)
        ->post(route('resume-editor.additional-info.update', $resume), [
            'languages' => ['English', 'Spanish'],
            'certifications' => 'AWS Certified Solutions Architect',
            'interests' => ['Machine Learning', 'Open Source'],
            'notes' => 'Available for remote work',
        ])
        ->assertRedirect();

    $additionalInfo = $resume->fresh()->additionalInformation;

    expect($additionalInfo)->not->toBeNull();
    expect($additionalInfo->languages)->toBe(['English', 'Spanish']);
    expect($additionalInfo->interests)->toBe(['Machine Learning', 'Open Source']);
    expect($additionalInfo->certifications)->toBe('AWS Certified Solutions Architect');
});

test('additional info languages and interests must be arrays of strings', function () {
    $user = User::factory()->create();
    $resume = $user->resumes()->create(['title' => 'My Resume']);

    $this->actingAs($user)
        ->from(route('resume-editor.show', $resume))
        ->post(route('resume-editor.additional-info.update', $resume), [
            'languages' => 'English, Spanish',
        ])
        ->assertSessionHasErrors(['languages']);
});
