<?php

use App\Models\User;

test('authenticated user can save personal info with required fields', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->post(route('resume-editor.personal-info.update'), [
            'first_name' => 'Jane',
            'last_name' => 'Doe',
            'middle_name' => 'Q',
            'date_of_birth' => '1990-05-15',
            'city' => 'NYC',
            'country' => 'USA',
            'phone' => '+1234567890',
            'linkedin_url' => 'https://linkedin.com/in/jane',
        ])
        ->assertRedirect();

    $profile = $user->fresh()->profile;

    expect($profile)->not->toBeNull();
    expect($profile->first_name)->toBe('Jane');
    expect($profile->last_name)->toBe('Doe');
    expect($profile->middle_name)->toBe('Q');
    expect($profile->city)->toBe('NYC');
    expect($profile->country)->toBe('USA');
    expect($profile->phone)->toBe('+1234567890');
    expect($profile->linkedin_url)->toBe('https://linkedin.com/in/jane');
    expect($profile->date_of_birth?->format('Y-m-d'))->toBe('1990-05-15');
});

test('personal info validation requires first name last name city and country', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->from(route('resume-editor'))
        ->post(route('resume-editor.personal-info.update'), [])
        ->assertSessionHasErrors(['first_name', 'last_name', 'city', 'country']);
});

test('user can update existing profile', function () {
    $user = User::factory()->create();
    $user->profile()->create([
        'first_name' => 'Old',
        'last_name' => 'Name',
        'city' => 'Old City',
        'country' => 'Old Country',
    ]);

    $this->actingAs($user)
        ->post(route('resume-editor.personal-info.update'), [
            'first_name' => 'New',
            'last_name' => 'Person',
            'middle_name' => null,
            'date_of_birth' => null,
            'city' => 'Boston',
            'country' => 'USA',
            'phone' => null,
            'linkedin_url' => null,
        ])
        ->assertRedirect();

    $profile = $user->fresh()->profile;
    expect($profile->first_name)->toBe('New');
    expect($profile->last_name)->toBe('Person');
    expect($profile->city)->toBe('Boston');
});
