<?php

namespace App\Ai\Agents;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Contracts\Agent;
use Laravel\Ai\Contracts\HasStructuredOutput;
use Laravel\Ai\Promptable;
use Stringable;

class ResumeSalaryAnalysisAgent implements Agent, HasStructuredOutput
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
        return "You are an expert compensation analyst and resume reviewer.
Analyze the candidate's resume and judge how it positions them for salary negotiation, without scoring it against any specific job posting.
The candidate's resume: {$this->resumeContext}

Consider every section, including volunteer & community involvement and leadership & extracurricular activities, as real evidence of transferable skills, initiative, and impact that can affect earning potential.

Provide:
- strengths: resume items (skills, experience, achievements) that increase this candidate's market value and negotiating power.
- weaknesses: gaps, weak phrasing, or missing information that hold back their earning potential.
- expectedSalaryMin: a realistic annual salary estimate (in whole US dollars) at the low end of what this candidate could expect based on their resume, general US market.
- expectedSalaryMax: a realistic annual salary estimate (in whole US dollars) at the high end of what this candidate could expect based on their resume, general US market.
- salaryRationale: a concise 1-3 sentence explanation of how the strengths and weaknesses above shape that salary range.";
    }

    /**
     * Get the agent's structured output schema definition.
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'strengths' => $schema->array()->items($schema->string())->required(),
            'weaknesses' => $schema->array()->items($schema->string())->required(),
            'expectedSalaryMin' => $schema->integer()->min(0)->required(),
            'expectedSalaryMax' => $schema->integer()->min(0)->required(),
            'salaryRationale' => $schema->string()->required(),
        ];
    }
}
