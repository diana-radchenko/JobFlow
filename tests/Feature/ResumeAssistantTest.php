<?php

use App\Ai\Agents\ResumeBuilderAgent;
use App\Ai\Tools\SaveProject;
use App\Ai\Tools\SaveSkill;
use App\Ai\Tools\SaveWorkExperience;
use App\Enums\ProjectType;
use App\Models\User;
use Laravel\Ai\Prompts\AgentPrompt;
use Laravel\Ai\Tools\Request;

test('save work experience tool creates the record and attaches it to the resume', function () {
    $user = User::factory()->create();
    $resume = $user->resumes()->create(['title' => 'My Resume']);

    (new SaveWorkExperience($resume))->handle(new Request([
        'company_name' => 'Acme Corp',
        'job_title' => 'Engineer',
        'city' => 'Berlin',
        'is_remote' => true,
        'start_date' => '2020-01-15',
        'is_current' => true,
        'description' => 'Shipped features.',
    ]));

    $exp = $user->fresh()->workExperiences->first();

    expect($exp)->not->toBeNull()
        ->and($exp->company_name)->toBe('Acme Corp')
        ->and($exp->is_remote)->toBeTrue();
    expect($resume->workExperiences()->whereKey($exp->id)->exists())->toBeTrue();
});

test('save project tool accepts the research type', function () {
    $user = User::factory()->create();
    $resume = $user->resumes()->create(['title' => 'My Resume']);

    (new SaveProject($resume))->handle(new Request([
        'title' => 'Distributed Systems Paper',
        'type' => 'research',
        'description' => 'Co-authored a peer-reviewed paper.',
    ]));

    $project = $user->fresh()->projects->first();

    expect($project)->not->toBeNull()
        ->and($project->type)->toBe(ProjectType::Research);
    expect($resume->projects()->whereKey($project->id)->exists())->toBeTrue();
});

test('save skill tool scopes the skill to the resume owner', function () {
    $user = User::factory()->create();
    $resume = $user->resumes()->create(['title' => 'My Resume']);

    (new SaveSkill($resume))->handle(new Request([
        'name' => 'Laravel',
        'proficiency_level' => 'expert',
    ]));

    $skill = $resume->skills()->first();

    expect($skill)->not->toBeNull()
        ->and($skill->name)->toBe('Laravel')
        ->and($skill->user_id)->toBe($user->id);
});

test('assistant message endpoint prompts the agent and returns its reply', function () {
    ResumeBuilderAgent::fake(['Great, let us start with your work experience.']);

    $user = User::factory()->create();
    $resume = $user->resumes()->create(['title' => 'My Resume']);

    $this->actingAs($user)
        ->postJson(route('resume-editor.assistant.message', $resume), [
            'message' => 'Hi, I want to build my resume.',
        ])
        ->assertSuccessful()
        ->assertJsonPath('message.role', 'assistant')
        ->assertJsonPath('message.content', 'Great, let us start with your work experience.');

    ResumeBuilderAgent::assertPrompted(function (AgentPrompt $prompt): bool {
        return $prompt->contains('build my resume');
    });
});

test('assistant message endpoint forbids acting on another users resume', function () {
    $owner = User::factory()->create();
    $resume = $owner->resumes()->create(['title' => 'My Resume']);
    $intruder = User::factory()->create();

    $this->actingAs($intruder)
        ->postJson(route('resume-editor.assistant.message', $resume), [
            'message' => 'Hijack attempt.',
        ])
        ->assertForbidden();
});

test('assistant page renders as its own standalone inertia page', function () {
    $user = User::factory()->create();
    $resume = $user->resumes()->create(['title' => 'My Resume']);

    $this->actingAs($user)
        ->get(route('resume-editor.assistant', $resume))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('ResumeAiAssistant')
            ->where('resume.id', $resume->id)
        );
});

test('assistant page forbids viewing another users resume', function () {
    $owner = User::factory()->create();
    $resume = $owner->resumes()->create(['title' => 'My Resume']);
    $intruder = User::factory()->create();

    $this->actingAs($intruder)
        ->get(route('resume-editor.assistant', $resume))
        ->assertForbidden();
});
