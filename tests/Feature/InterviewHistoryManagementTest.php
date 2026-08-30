<?php

use App\Models\InterviewSession;
use App\Models\InterviewSessionEvent;
use App\Models\User;
use App\Models\UserWorkJobApplication;
use App\Models\WorkJob;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Inertia\Testing\AssertableInertia as Assert;

function createCompletedAiInterview(User $user, array $attributes = []): InterviewSession
{
    return InterviewSession::create([
        'user_id' => $user->id,
        'type' => 'technical',
        'complexity' => 'intermediate',
        'mode' => 'text',
        'status' => 'completed',
        ...$attributes,
    ]);
}

test('candidate can delete only their own completed ai interview and its private conversation', function () {
    $candidate = User::factory()->create();
    $resume = $candidate->resumes()->create(['title' => 'Interview Resume']);
    $job = WorkJob::create([
        'title' => 'Software Engineer',
        'company' => 'Acme',
        'description' => 'Build software',
        'contacts' => 'jobs@example.com',
        'location' => 'Remote',
        'technologies' => ['PHP'],
    ]);
    $application = UserWorkJobApplication::create([
        'user_id' => $candidate->id,
        'work_job_id' => $job->id,
        'resume_id' => $resume->id,
        'status' => 'applied',
    ]);
    $conversationId = (string) Str::uuid();
    $session = createCompletedAiInterview($candidate, [
        'resume_id' => $resume->id,
        'work_job_id' => $job->id,
        'conversation_id' => $conversationId,
    ]);

    DB::table('agent_conversations')->insert([
        'id' => $conversationId,
        'user_id' => $candidate->id,
        'title' => 'Completed AI interview',
        'created_at' => now(),
        'updated_at' => now(),
    ]);
    DB::table('agent_conversation_messages')->insert([
        'id' => (string) Str::uuid(),
        'conversation_id' => $conversationId,
        'user_id' => $candidate->id,
        'agent' => 'App\\Ai\\Agents\\InterviewAgent',
        'role' => 'assistant',
        'content' => 'Saved result',
        'attachments' => '[]',
        'tool_calls' => '[]',
        'tool_results' => '[]',
        'usage' => '{}',
        'meta' => '{}',
        'created_at' => now(),
        'updated_at' => now(),
    ]);
    InterviewSessionEvent::create([
        'interview_session_id' => $session->id,
        'changed_by' => $candidate->id,
        'action' => 'completed',
    ]);

    $this->actingAs($candidate)
        ->delete(route('interview-session.destroy', $session))
        ->assertRedirect();

    $this->assertModelMissing($session);
    $this->assertModelExists($resume);
    $this->assertModelExists($job);
    $this->assertModelExists($application);
    expect(InterviewSessionEvent::where('interview_session_id', $session->id)->exists())->toBeFalse()
        ->and(DB::table('agent_conversations')->where('id', $conversationId)->exists())->toBeFalse()
        ->and(DB::table('agent_conversation_messages')->where('conversation_id', $conversationId)->exists())->toBeFalse();
});

test('deleting an interview preserves a conversation referenced by another feature', function () {
    $candidate = User::factory()->create();
    $conversationId = (string) Str::uuid();
    $resume = $candidate->resumes()->create([
        'title' => 'Resume with AI history',
        'ai_conversation_id' => $conversationId,
    ]);
    $session = createCompletedAiInterview($candidate, [
        'conversation_id' => $conversationId,
    ]);

    DB::table('agent_conversations')->insert([
        'id' => $conversationId,
        'user_id' => $candidate->id,
        'title' => 'Shared conversation',
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $this->actingAs($candidate)
        ->delete(route('interview-session.destroy', $session))
        ->assertRedirect();

    $this->assertModelExists($resume);
    expect(DB::table('agent_conversations')->where('id', $conversationId)->exists())->toBeTrue();
});

test('candidate cannot delete another candidates interview', function () {
    $owner = User::factory()->create();
    $otherCandidate = User::factory()->create();
    $session = createCompletedAiInterview($owner);

    $this->actingAs($otherCandidate)
        ->delete(route('interview-session.destroy', $session))
        ->assertForbidden();

    $this->assertModelExists($session);
});

test('employer cannot use the candidate interview history delete route', function () {
    $candidate = User::factory()->create();
    $employer = User::factory()->employer()->create();
    $session = createCompletedAiInterview($candidate);

    $this->actingAs($employer)
        ->delete(route('interview-session.destroy', $session))
        ->assertForbidden();

    $this->assertModelExists($session);
});

test('candidate cannot delete active or employer scheduled interview records', function () {
    $candidate = User::factory()->create();
    $employer = User::factory()->employer()->create();
    $activeSession = InterviewSession::create([
        'user_id' => $candidate->id,
        'type' => 'behavioral',
        'complexity' => 'beginner',
        'mode' => 'text',
        'status' => 'in_progress',
    ]);
    $scheduledInterview = InterviewSession::create([
        'user_id' => $candidate->id,
        'employer_id' => $employer->id,
        'type' => 'job_interview',
        'complexity' => 'standard',
        'mode' => 'scheduled',
        'status' => 'completed',
        'scheduled_at' => now()->subDay(),
        'timezone' => 'America/Chicago',
    ]);

    $this->actingAs($candidate)
        ->delete(route('interview-session.destroy', $activeSession))
        ->assertUnprocessable();
    $this->actingAs($candidate)
        ->delete(route('interview-session.destroy', $scheduledInterview))
        ->assertUnprocessable();

    $this->assertModelExists($activeSession);
    $this->assertModelExists($scheduledInterview);
});

test('interview center history is candidate scoped newest first and excludes scheduled records', function () {
    $candidate = User::factory()->create();
    $otherCandidate = User::factory()->create();
    $older = createCompletedAiInterview($candidate, ['created_at' => now()->subDays(2)]);
    $newer = createCompletedAiInterview($candidate, ['created_at' => now()->subDay()]);
    createCompletedAiInterview($otherCandidate);
    InterviewSession::create([
        'user_id' => $candidate->id,
        'type' => 'job_interview',
        'complexity' => 'standard',
        'mode' => 'scheduled',
        'status' => 'completed',
        'scheduled_at' => now()->subDay(),
    ]);

    $this->actingAs($candidate)
        ->get(route('interview-preparation'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('InterviewPreparation')
            ->has('pastSessions.data', 2)
            ->where('pastSessions.data.0.id', $newer->id)
            ->where('pastSessions.data.1.id', $older->id));
});
