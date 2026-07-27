<?php

namespace App\Ai\Agents;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Contracts\Agent;
use Laravel\Ai\Contracts\HasStructuredOutput;
use Laravel\Ai\Promptable;
use Stringable;

class ResumeAnalysisAgent implements Agent, HasStructuredOutput
{
    use Promptable;

    public function __construct(
        public string $resumeContext,
    ) {}

    /**
     * Get the instructions that the agent should follow.
     */
    public function instructions(): Stringable|string
    {
        return "You are an expert resume reviewer and career coach.
Analyze the candidate's resume in general, without scoring it against any specific job.
The candidate's resume: {$this->resumeContext}

Consider every section, including volunteer & community involvement and leadership & extracurricular activities. Treat these as real evidence of transferable skills, initiative, and impact rather than filler, and call out when they are missing or too vague to be useful.

Provide:
- strengths: what stands out and works well in this resume.
- weaknesses: gaps, weak phrasing, or missing information that hold the resume back.
- recommendations: actionable advice to improve the resume overall.
- professionalSummary: a concise, polished 2-4 sentence professional summary the candidate could place at the top of their resume, written in first person voice and based on their actual experience. Draw on volunteer and leadership entries where they add meaningful signal.";
    }

    /**
     * Get the agent's structured output schema definition.
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'strengths' => $schema->array()->items($schema->string())->required(),
            'weaknesses' => $schema->array()->items($schema->string())->required(),
            'recommendations' => $schema->array()->items($schema->string())->required(),
            'professionalSummary' => $schema->string()->required(),
        ];
    }
}
