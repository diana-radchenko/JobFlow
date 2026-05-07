<?php

namespace App\Ai\Agents;

use Laravel\Ai\Concerns\RemembersConversations;
use Laravel\Ai\Contracts\Agent;
use Laravel\Ai\Contracts\Conversational;
use Laravel\Ai\Promptable;

class InterviewAgent implements Agent, Conversational
{
    use Promptable, RemembersConversations;

    public function __construct(
        public string $interviewType,
        public string $complexity,
        public string $resumeContext = '',
        public string $jobContext = ''
    ) {}

    /**
     * Get the instructions that the agent should follow.
     */
    public function instructions(): string
    {
        return "You are an expert HR and Technical Interviewer.
You are conducting a {$this->complexity} level {$this->interviewType} interview.
The candidate's resume context: {$this->resumeContext}
The job context: {$this->jobContext}

Follow these rules:
1. Ask ONE relevant interview question at a time.
2. Wait for the candidate's response.
3. Evaluate their response briefly, providing constructive feedback if necessary.
4. Ask the next question.
5. Maintain a professional, encouraging tone.
6. When the interview is complete (e.g. after 6-8 questions or if the candidate wants to stop), provide a final evaluation.";
    }
}
