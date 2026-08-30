<?php

use App\Ai\Agents\InterviewAgent;
use App\Models\InterviewSession;
use App\Models\User;
use Illuminate\Support\Str;
use Laravel\Ai\Audio;
use Laravel\Ai\Prompts\AgentPrompt;
use Laravel\Ai\Prompts\AudioPrompt;
use Laravel\Ai\Responses\AudioResponse;
use Laravel\Ai\Responses\Data\Meta;

test('completing an interview saves immediately and feedback is requested separately', function () {
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
    expect($session->feedback_status)->toBe('pending');
    InterviewAgent::assertNeverPrompted();

    $this->actingAs($user)->postJson(route('interview-session.feedback', $session))->assertSuccessful();

    InterviewAgent::assertPrompted(function (AgentPrompt $prompt): bool {
        return $prompt->contains('The mock interview is complete.');
    });
});

test('an ended interview without conversation can request feedback', function () {
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
    InterviewAgent::assertNeverPrompted();
    $this->actingAs($user)->postJson(route('interview-session.feedback', $session))->assertSuccessful();
    $session->refresh();
    expect($session->conversation_id)->not->toBeNull();

    InterviewAgent::assertPrompted(function (AgentPrompt $prompt): bool {
        return $prompt->contains('Overall Assessment')
            && $prompt->contains('Areas to Improve')
            && $prompt->contains('Recommendation');
    });
});

test('generating interview audio returns speech content for the session owner', function () {
    Audio::fake([new AudioResponse(base64_encode('fake-audio-content'), new Meta('openai', 'gpt-4o-mini-tts'), 'audio/mpeg')]);

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
