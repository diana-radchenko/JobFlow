<?php

use App\Ai\Agents\InterviewAgent;
use App\Models\InterviewSession;
use App\Models\User;
use Illuminate\Support\Str;
use Laravel\Ai\Prompts\AgentPrompt;

test('completing an interview prompts ai for a final evaluation', function () {
    InterviewAgent::fake();

    /** @var User $user */
    $user = User::factory()->create();
    $session = InterviewSession::create([
        'user_id' => $user->id,
        'conversation_id' => (string) Str::uuid(),
        'type' => 'technical',
        'complexity' => 'advanced',
        'status' => 'in_progress',
    ]);

    $this->actingAs($user)
        ->post(route('interview-session.complete', $session))
        ->assertRedirect(route('interview-preparation'));

    $session->refresh();

    expect($session->status)->toBe('completed');

    InterviewAgent::assertPrompted(function (AgentPrompt $prompt): bool {
        return $prompt->contains('The interview is now complete.');
    });
});

test('completing an interview without conversation still requests final evaluation', function () {
    InterviewAgent::fake();

    /** @var User $user */
    $user = User::factory()->create();
    $session = InterviewSession::create([
        'user_id' => $user->id,
        'conversation_id' => null,
        'type' => 'behavioral',
        'complexity' => 'beginner',
        'status' => 'in_progress',
    ]);

    $this->actingAs($user)
        ->post(route('interview-session.complete', $session))
        ->assertRedirect(route('interview-preparation'));

    $session->refresh();

    expect($session->status)->toBe('completed');
    expect($session->conversation_id)->not->toBeNull();

    InterviewAgent::assertPrompted(function (AgentPrompt $prompt): bool {
        return $prompt->contains('top 3 actionable improvements');
    });
});
