<?php

use App\Ai\Agents\ResumeSalaryAnalysisAgent;
use App\Models\User;
use Laravel\Ai\Prompts\AgentPrompt;

test('resume salary analysis endpoint prompts the agent and returns strengths, weaknesses, and an expected salary range', function () {
    ResumeSalaryAnalysisAgent::fake();

    $user = User::factory()->create();
    $resume = $user->resumes()->create(['title' => 'My Resume']);

    $this->actingAs($user)
        ->postJson(route('resume-salary-analysis.store'), [
            'resume_id' => $resume->id,
        ])
        ->assertSuccessful()
        ->assertJsonStructure([
            'strengths',
            'weaknesses',
            'expectedSalaryMin',
            'expectedSalaryMax',
            'salaryRationale',
        ]);

    ResumeSalaryAnalysisAgent::assertPrompted(function (AgentPrompt $prompt): bool {
        return $prompt->contains('Analyze this resume');
    });
});

test('resume salary analysis endpoint forbids analyzing another users resume', function () {
    $owner = User::factory()->create();
    $resume = $owner->resumes()->create(['title' => 'My Resume']);
    $intruder = User::factory()->create();

    $this->actingAs($intruder)
        ->postJson(route('resume-salary-analysis.store'), [
            'resume_id' => $resume->id,
        ])
        ->assertForbidden();
});
