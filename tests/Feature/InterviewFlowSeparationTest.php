<?php

use App\Ai\Agents\InterviewAgent;
use App\Ai\Agents\InterviewPrepAgent;
use App\Models\InterviewSession;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;
use Laravel\Ai\Prompts\AgentPrompt;

test('candidate can open a separate ai preparation workspace and request coaching', function () {
    InterviewPrepAgent::fake(['## Preparation Plan\n- Review your strongest project.']);

    $user = User::factory()->create();
    $resume = $user->resumes()->create(['title' => 'Software Engineer Resume']);
    $context = [
        'type' => 'technical',
        'complexity' => 'intermediate',
        'mode' => 'text',
        'resume_id' => $resume->id,
    ];

    $this->actingAs($user)
        ->get(route('interview-prep.show', $context))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Interview/Prep')
            ->where('context.resume_title', 'Software Engineer Resume')
            ->where('context.type', 'technical'));

    $this->actingAs($user)
        ->postJson(route('interview-prep.guidance'), $context)
        ->assertOk()
        ->assertJsonPath('guidance', '## Preparation Plan\n- Review your strongest project.');

    InterviewPrepAgent::assertPrompted(fn (AgentPrompt $prompt): bool => $prompt->contains('focused preparation plan'));
});

test('text mock interview asks six questions without coaching and then exposes results', function () {
    InterviewAgent::fake([
        'Question one?',
        'Question two?',
        'Question three?',
        'Question four?',
        'Question five?',
        'Question six?',
        "## Overall Assessment\nStrong foundation.\n\n## Strengths\nClear examples.\n\n## Areas to Improve\nAdd more detail.\n\n## Recommendation\nKeep practicing.",
    ]);

    $user = User::factory()->create();
    $resume = $user->resumes()->create(['title' => 'Technical Resume']);
    $session = InterviewSession::create([
        'user_id' => $user->id,
        'resume_id' => $resume->id,
        'type' => 'technical',
        'complexity' => 'advanced',
        'mode' => 'text',
        'status' => 'in_progress',
    ]);

    $this->actingAs($user)
        ->postJson(route('interview-session.message', $session), ['intent' => 'start'])
        ->assertOk()
        ->assertJsonPath('message.content', 'Question one?')
        ->assertJsonPath('question_number', 1)
        ->assertJsonPath('session_status', 'in_progress');

    foreach (range(1, 5) as $answerNumber) {
        $this->actingAs($user)
            ->postJson(route('interview-session.message', $session), [
                'intent' => 'answer',
                'message' => "Answer {$answerNumber}",
            ])
            ->assertOk()
            ->assertJsonPath('question_number', $answerNumber + 1)
            ->assertJsonPath('session_status', 'in_progress');
    }

    $this->actingAs($user)
        ->postJson(route('interview-session.message', $session), [
            'intent' => 'answer',
            'message' => 'Final answer',
        ])
        ->assertOk()
        ->assertJsonPath('question_number', 6)
        ->assertJsonPath('session_status', 'completed');

    expect($session->refresh()->status)->toBe('completed');
    $this->actingAs($user)
        ->postJson(route('interview-session.feedback', $session))
        ->assertSuccessful()
        ->assertJsonPath('feedback_status', 'ready');
    expect((new InterviewAgent('technical', 'advanced'))->instructions())
        ->toContain('Do not include feedback, coaching, hints')
        ->not->toContain('providing constructive feedback');

    $this->actingAs($user)
        ->get(route('interview-session.results', $session))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Interview/Results')
            ->where('result', fn ($result) => str_contains($result, 'Overall Assessment')));
});

test('interview results remain private and incomplete sessions return to the mock interview', function () {
    $owner = User::factory()->create();
    $otherUser = User::factory()->create();
    $session = InterviewSession::create([
        'user_id' => $owner->id,
        'type' => 'behavioral',
        'complexity' => 'beginner',
        'mode' => 'text',
        'status' => 'in_progress',
    ]);

    $this->actingAs($otherUser)
        ->get(route('interview-session.results', $session))
        ->assertForbidden();

    $this->actingAs($owner)
        ->get(route('interview-session.results', $session))
        ->assertRedirect(route('interview-session.show', $session));
});
