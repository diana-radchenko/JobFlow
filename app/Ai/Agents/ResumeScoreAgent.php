<?php

namespace App\Ai\Agents;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Contracts\Agent;
use Laravel\Ai\Contracts\HasStructuredOutput;
use Laravel\Ai\Promptable;
use Stringable;

class ResumeScoreAgent implements Agent, HasStructuredOutput
{
    use Promptable;

    public function __construct(
        public string $resumeContext,
        public string $jobContext,
    ) {}

    /**
     * Get the instructions that the agent should follow.
     */
    public function instructions(): Stringable|string
    {
        return "You are an expert resume reviewer and ATS (Applicant Tracking System) analyst.
Score how well the candidate's resume fits the given job, from 0 to 100.
The candidate's resume: {$this->resumeContext}
The job: {$this->jobContext}

Base the score on relevant skills, work experience, education, and projects that match the job.
Then provide actionable recommendations to improve the resume's fit for this specific job:
- highlights: existing resume items that are strong fits and should be emphasized.
- additions: skills, experience, or details missing from the resume that should be added.
- removals: resume items that are less relevant to this job and could be trimmed.";
    }

    /**
     * Get the agent's structured output schema definition.
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'score' => $schema->integer()->min(0)->max(100)->required(),
            'summary' => $schema->string()->required(),
            'highlights' => $schema->array()->items($schema->string())->required(),
            'additions' => $schema->array()->items($schema->string())->required(),
            'removals' => $schema->array()->items($schema->string())->required(),
        ];
    }
}
