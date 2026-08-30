<?php

use App\Ai\Agents\InterviewAgent;
use App\Models\InterviewSession;
use App\Models\User;
use Illuminate\Support\Str;
use Laravel\Ai\Audio;
use Laravel\Ai\Prompts\AgentPrompt;
use Laravel\Ai\Prompts\AudioPrompt;

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
        ->assertRedirect(route('interview-session.results', $session));

    $session->refresh();

    expect($session->status)->toBe('completed');

    InterviewAgent::assertPrompted(function (AgentPrompt $prompt): bool {
        return $prompt->contains('The mock interview is complete.');
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
        ->assertRedirect(route('interview-session.results', $session));

    $session->refresh();

    expect($session->status)->toBe('completed');
    expect($session->conversation_id)->not->toBeNull();

    InterviewAgent::assertPrompted(function (AgentPrompt $prompt): bool {
        return $prompt->contains('Overall Assessment')
            && $prompt->contains('Areas to Improve')
            && $prompt->contains('Recommendation');
    });
});

test('generating interview audio returns speech content for the session owner', function () {
    Audio::fake();

    /** @var User $user */
    $user = User::factory()->create();
    $session = InterviewSession::create([
        'user_id' => $user->id,
        'conversation_id' => null,
        'type' => 'technical',
        'complexity' => 'advanced',
        'status' => 'in_progress',
    ]);

    $this->actingAs($user)
        ->postJson(route('interview-session.audio', $session), [
            'content' => 'Tell me about your Laravel experience.',
        ])
        ->assertSuccessful()
        ->assertHeader('Content-Type', 'audio/mpeg')
        ->assertContent('fake-audio-content');

    Audio::assertGenerated(function (AudioPrompt $prompt): bool {
        return $prompt->contains('Laravel experience')
            && $prompt->isFemale()
            && str_contains($prompt->instructions ?? '', 'technical interviewer');
    });
});
