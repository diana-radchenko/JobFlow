<?php

use App\Models\InterviewSession;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

test('authenticated user can start a live interview session', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)
        ->post(route('interview-session.store'), [
            'type' => 'resume-based',
            'complexity' => 'advanced',
            'mode' => 'live',
        ]);

    $session = InterviewSession::query()->whereBelongsTo($user)->first();

    expect($session)->not->toBeNull();
    expect($session->mode)->toBe('live');

    $response->assertRedirect(route('interview-session.show', $session));
});

test('live interview sessions render the live interview page', function () {
    $user = User::factory()->create();
    $session = InterviewSession::create([
        'user_id' => $user->id,
        'type' => 'technical',
        'complexity' => 'beginner',
        'mode' => 'live',
        'status' => 'in_progress',
    ]);

    $this->actingAs($user)
        ->get(route('interview-session.show', $session))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Interview/Live')
            ->where('session.mode', 'live'),
        );
});

test('text interview sessions still render the chat page', function () {
    $user = User::factory()->create();
    $session = InterviewSession::create([
        'user_id' => $user->id,
        'type' => 'technical',
        'complexity' => 'beginner',
        'mode' => 'text',
        'status' => 'in_progress',
    ]);

    $this->actingAs($user)
        ->get(route('interview-session.show', $session))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Interview/Chat')
            ->where('session.mode', 'text'),
        );
});
