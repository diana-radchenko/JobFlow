<?php

use App\Ai\Agents\InterviewAgent;
use App\Models\InterviewSession;
use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Inertia\Testing\AssertableInertia as Assert;

function feedbackTestResult(): string
{
    return "## Overall Assessment\n\nRelevant answers.\n\n## Strengths\n\n- Clear examples\n- Sound reasoning\n\n## Areas to Improve\n\n- Quantify outcomes\n\n## Recommendation\n\nKeep practicing.";
}

function feedbackTestSession(User $user, string $mode = 'text'): InterviewSession
{
    return InterviewSession::create([
        'user_id' => $user->id,
        'resume_id' => $user->resumes()->create(['title' => 'Feedback Resume'])->id,
        'mode' => $mode,
        'type' => 'technical',
        'complexity' => 'advanced',
        'status' => 'in_progress',
    ]);
}

function answerFeedbackTestInterview($test, User $user, InterviewSession $session): void
{
    $test->actingAs($user)->postJson(route('interview-session.message', $session), ['intent' => 'start'])
        ->assertSuccessful()->assertJsonPath('question_number', 1);
    foreach (range(1, 6) as $number) {
        $test->postJson(route('interview-session.message', $session), ['intent' => 'answer', 'message' => "Saved answer {$number}"])
            ->assertSuccessful()->assertJsonPath('session_status', $number === 6 ? 'completed' : 'in_progress');
    }
}

it('saves all six answers before evaluating once and makes repeated completion idempotent', function (string $mode) {
    $calls = 0;
    InterviewAgent::fake(function (string $prompt) use (&$calls) {
        if (str_contains($prompt, 'The mock interview is complete.')) {
            $calls++;

            return feedbackTestResult();
        }

        return 'Describe a relevant project decision.';
    });
    $user = User::factory()->create();
    $session = feedbackTestSession($user, $mode);
    answerFeedbackTestInterview($this, $user, $session);
    expect($calls)->toBe(0);
    expect($session->refresh()->feedback_status)->toBe('pending');
    // Count persisted questions, not the SDK's separate conversation-title request.
    expect(DB::table('agent_conversation_messages')->where('conversation_id', $session->conversation_id)
        ->where('role', 'assistant')->count())->toBe(6);
    $answers = DB::table('agent_conversation_messages')->where('conversation_id', $session->conversation_id)
        ->where('role', 'user')->where('content', 'like', 'Saved answer %')->pluck('content')->all();
    expect($answers)->toHaveCount(6)->toContain('Saved answer 6');

    $this->get(route('interview-session.results', $session))->assertInertia(fn (Assert $page) => $page
        ->where('result', null)->where('session.feedback_status', 'pending'));
    $this->postJson(route('interview-session.message', $session), ['intent' => 'answer', 'message' => 'Saved answer 6'])
        ->assertSuccessful()->assertJsonPath('session_status', 'completed');
    $this->postJson(route('interview-session.feedback', $session))
        ->assertSuccessful()->assertJsonPath('result', feedbackTestResult());
    $this->post(route('interview-session.complete', $session))->assertRedirect(route('interview-session.results', $session));
    $this->postJson(route('interview-session.feedback', $session))->assertSuccessful()->assertJsonPath('feedback_status', 'ready');
    expect($calls)->toBe(1);
    expect(DB::table('agent_conversation_messages')->where('conversation_id', $session->conversation_id)
        ->where('role', 'assistant')->count())->toBe(7);
    expect(DB::table('agent_conversation_messages')->where('conversation_id', $session->conversation_id)
        ->where('content', 'Saved answer 6')->count())->toBe(1);
    $this->get(route('interview-session.results', $session))->assertInertia(fn (Assert $page) => $page->where('result', feedbackTestResult()));
})->with(['text', 'live']);

it('retains all answers after failed feedback and retries only feedback', function () {
    $evaluationCalls = 0;
    InterviewAgent::fake(function (string $prompt) use (&$evaluationCalls) {
        if (str_contains($prompt, 'The mock interview is complete.')) {
            if (++$evaluationCalls === 1) {
                throw new RuntimeException('Simulated provider failure');
            }

            return feedbackTestResult();
        }

        return 'Describe a relevant project decision.';
    });
    $user = User::factory()->create();
    $session = feedbackTestSession($user);
    answerFeedbackTestInterview($this, $user, $session);
    $session->refresh();
    $messagesBefore = DB::table('agent_conversation_messages')->where('conversation_id', $session->conversation_id)->pluck('content')->all();
    $this->postJson(route('interview-session.feedback', $session))->assertUnprocessable()
        ->assertJsonPath('message', "Your interview was saved, but we couldn't generate feedback yet.");
    expect($session->refresh()->status)->toBe('completed')->and($session->feedback_status)->toBe('failed');
    expect(DB::table('agent_conversation_messages')->where('conversation_id', $session->conversation_id)->pluck('content')->all())->toBe($messagesBefore);
    $this->get(route('interview-session.results', $session))->assertInertia(fn (Assert $page) => $page->where('result', null)->where('session.feedback_status', 'failed'));
    $this->postJson(route('interview-session.feedback', $session))->assertSuccessful()->assertJsonPath('result', feedbackTestResult());
    $this->postJson(route('interview-session.feedback', $session))->assertSuccessful();
    expect($evaluationCalls)->toBe(2)->and($session->refresh()->feedback_status)->toBe('ready');
    expect(DB::table('agent_conversation_messages')->where('conversation_id', $session->conversation_id)
        ->where('role', 'assistant')->count())->toBe(7);
});

it('does not call the provider while another request owns the interview lock', function () {
    InterviewAgent::fake()->preventStrayPrompts();
    $user = User::factory()->create();
    $session = feedbackTestSession($user);
    $session->update(['status' => 'completed', 'feedback_status' => 'pending']);
    $lock = Cache::lock("interview-session:{$session->id}", 90);
    expect($lock->get())->toBeTrue();
    try {
        $this->actingAs($user)->postJson(route('interview-session.feedback', $session))
            ->assertStatus(202)->assertJsonPath('feedback_status', 'generating');
        InterviewAgent::assertNeverPrompted();
    } finally {
        $lock->release();
    }
});

it('lists both active modes and ending either unlocks only that mode without calling ai', function (string $endedMode) {
    InterviewAgent::fake()->preventStrayPrompts();
    $user = User::factory()->create();
    $text = feedbackTestSession($user, 'text');
    $voice = feedbackTestSession($user, 'live');
    $other = feedbackTestSession(User::factory()->create());
    $ended = $endedMode === 'text' ? $text : $voice;
    $remaining = $endedMode === 'text' ? $voice : $text;

    $this->actingAs($user)->get(route('interview-preparation'))->assertInertia(fn (Assert $page) => $page
        ->has('activeSessions', 2)->where('activeSessions.0.id', $text->id)->where('activeSessions.1.id', $voice->id));
    $this->post(route('interview-session.complete', $ended), ['return_to_setup' => true])->assertRedirect(route('interview-preparation'));
    $this->get(route('interview-preparation'))->assertInertia(fn (Assert $page) => $page
        ->has('activeSessions', 1)->where('activeSessions.0.id', $remaining->id));
    expect($ended->refresh()->status)->toBe('completed')->and($remaining->refresh()->status)->toBe('in_progress')->and($other->refresh()->status)->toBe('in_progress');
    InterviewAgent::assertNeverPrompted();

    $this->post(route('interview-session.store'), [
        'resume_id' => $ended->resume_id, 'mode' => $endedMode, 'type' => 'technical', 'complexity' => 'advanced',
    ])->assertRedirect();
    expect(InterviewSession::where('user_id', $user->id)->where('mode', $endedMode)->where('status', 'in_progress')->count())->toBe(1);
})->with(['text', 'live']);

it('keeps completed legacy results available and never evaluates them again', function () {
    $providerCalls = 0;
    InterviewAgent::fake(function () use (&$providerCalls): string {
        $providerCalls++;

        return 'Legacy final assessment without Markdown headings.';
    });
    $user = User::factory()->create();
    $session = feedbackTestSession($user);
    $response = (new InterviewAgent('technical', 'advanced'))->forUser($user)->prompt('Legacy evaluation');
    $fixtureCalls = $providerCalls;
    $session->update(['conversation_id' => $response->conversationId, 'status' => 'completed']);
    $this->actingAs($user)->get(route('interview-session.results', $session))->assertInertia(fn (Assert $page) => $page->where('result', 'Legacy final assessment without Markdown headings.'));
    $this->post(route('interview-session.complete', $session))->assertRedirect();
    $this->postJson(route('interview-session.feedback', $session))->assertSuccessful()->assertJsonPath('result', 'Legacy final assessment without Markdown headings.');
    expect($providerCalls)->toBe($fixtureCalls);
});

it('rejects feedback from another user and for an unfinished interview', function () {
    InterviewAgent::fake()->preventStrayPrompts();
    $owner = User::factory()->create();
    $session = feedbackTestSession($owner);
    $this->actingAs(User::factory()->create())->postJson(route('interview-session.feedback', $session))->assertForbidden();
    $this->actingAs($owner)->postJson(route('interview-session.feedback', $session))->assertConflict();
    InterviewAgent::assertNeverPrompted();
});

it('instructs semantic final headings and bullets without changing question instructions', function () {
    $instructions = (new InterviewAgent('technical', 'advanced', finalEvaluation: true))->instructions();
    foreach (['Overall Assessment', 'Strengths', 'Areas to Improve', 'Recommendation'] as $heading) {
        expect($instructions)->toContain('## '.$heading);
    }
    expect($instructions)->toContain('Markdown bullet')->toContain('Do not ask another question');
    expect((new InterviewAgent('technical', 'advanced'))->instructions())->toContain('question 1 of 6')->toContain('Do not include feedback, coaching, hints');
});
