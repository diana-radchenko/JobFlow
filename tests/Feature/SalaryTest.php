<?php

use App\Models\User;

test('guests are redirected to the login page from salary', function () {
    $response = $this->get(route('salary'));
    $response->assertRedirect(route('login'));
});

test('authenticated users can visit the salary page', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $response = $this->get(route('salary'));
    $response->assertOk();
});

test('salary page shares the users resumes for the AI review card', function () {
    $user = User::factory()->create();
    $resume = $user->resumes()->create(['title' => 'My Resume']);

    $this->actingAs($user)
        ->get(route('salary'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('Salary')
            ->has('resumes', 1)
            ->where('resumes.0.id', $resume->id)
            ->where('resumes.0.title', $resume->title)
        );
});
