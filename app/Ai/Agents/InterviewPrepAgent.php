<?php

namespace App\Ai\Agents;

use Laravel\Ai\Contracts\Agent;
use Laravel\Ai\Promptable;

class InterviewPrepAgent implements Agent
{
    use Promptable;

    public function __construct(
        public string $interviewType,
        public string $complexity,
        public string $resumeContext,
        public string $jobContext,
    ) {}

    public function instructions(): string
    {
        return "You are an interview preparation coach. This is preparation before a mock interview, not the interview itself.
Use the selected resume and job context to give concise, practical coaching for a {$this->complexity} {$this->interviewType} interview.
You may suggest themes, preparation steps, and ways to strengthen a practice answer.
Do not score the candidate and do not pretend a formal interview is in progress.
Resume context: {$this->resumeContext}
Job context: {$this->jobContext}";
    }
}
