<?php

use App\Ai\Agents\InterviewAgent;
use App\Models\InterviewSession;
use App\Models\User;
use Laravel\Ai\Prompts\AgentPrompt;

test('authenticated user can send an interview message and receive ai reply', function () {
    InterviewAgent::fake(['Welcome! Let us begin with your background.']);

    $user = User::factory()->create();
    $session = InterviewSession::create([
        'user_id' => $user->id,
        'type' => 'technical',
        'complexity' => 'beginner',
        'status' => 'in_progress',
    ]);

    $this->actingAs($user)
        ->postJson(route('interview-session.message', $session), [
            'message' => 'Hello, I am ready.',
        ])
        ->assertSuccessful()
        ->assertJsonPath('message.role', 'assistant')
        ->assertJsonPath('message.content', 'Welcome! Let us begin with your background.');

    $session->refresh();

    expect($session->conversation_id)->not->toBeNull();

    InterviewAgent::assertPrompted(function (AgentPrompt $prompt): bool {
        return $prompt->contains('Hello, I am ready.');
    });
});

test('user cannot send message to another users interview session', function () {
    InterviewAgent::fake();

    $owner = User::factory()->create();
    $other = User::factory()->create();
    $session = InterviewSession::create([
        'user_id' => $owner->id,
        'type' => 'technical',
        'complexity' => 'beginner',
        'status' => 'in_progress',
    ]);

    $this->actingAs($other)
        ->postJson(route('interview-session.message', $session), [
            'message' => 'Hello',
        ])
        ->assertForbidden();
});
