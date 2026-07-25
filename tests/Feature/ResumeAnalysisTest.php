<?php

use App\Ai\Agents\ResumeAnalysisAgent;
use App\Models\User;
use Laravel\Ai\Prompts\AgentPrompt;

test('resume analysis endpoint prompts the agent and returns the structured analysis', function () {
    ResumeAnalysisAgent::fake();

    $user = User::factory()->create();
    $resume = $user->resumes()->create(['title' => 'My Resume']);

    $this->actingAs($user)
        ->postJson(route('resume-analysis.store'), [
            'resume_id' => $resume->id,
        ])
        ->assertSuccessful()
        ->assertJsonStructure([
            'strengths',
            'weaknesses',
            'recommendations',
            'professionalSummary',
        ]);

    ResumeAnalysisAgent::assertPrompted(function (AgentPrompt $prompt): bool {
        return $prompt->contains('Analyze this resume');
    });
});

test('resume analysis endpoint forbids analyzing another users resume', function () {
    $owner = User::factory()->create();
    $resume = $owner->resumes()->create(['title' => 'My Resume']);
    $intruder = User::factory()->create();

    $this->actingAs($intruder)
        ->postJson(route('resume-analysis.store'), [
            'resume_id' => $resume->id,
        ])
        ->assertForbidden();
});
