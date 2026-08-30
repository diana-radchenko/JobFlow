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
        public string $jobContext = '',
        public int $currentQuestion = 0,
        public int $totalQuestions = 6,
        public bool $finalEvaluation = false,
    ) {}

    /**
     * Get the instructions that the agent should follow.
     */
    public function instructions(): string
    {
        if ($this->finalEvaluation) {
            return "You are evaluating a completed mock interview.
Use the conversation, the candidate's resume, and the selected job context.
Return concise semantic Markdown, without a code fence or raw HTML, with exactly these level-two headings in this order:
## Overall Assessment
A short assessment paragraph.

## Strengths
- Use a separate Markdown bullet for each evidence-based strength.

## Areas to Improve
- Use a separate Markdown bullet for each actionable improvement.

## Recommendation
A short recommendation paragraph.

Leave a blank line after each heading and between sections. Never return the section labels as plain body text.
Do not ask another question and do not invent facts.
Resume context: {$this->resumeContext}
Job context: {$this->jobContext}";
        }

        $nextQuestion = min($this->currentQuestion + 1, $this->totalQuestions);

        return "You are an expert HR and Technical Interviewer.
You are conducting a {$this->complexity} level {$this->interviewType} mock interview.
The candidate's resume context: {$this->resumeContext}
The job context: {$this->jobContext}

Follow these rules:
1. Ask exactly ONE relevant interview question: question {$nextQuestion} of {$this->totalQuestions}.
2. Return only the interview question. Do not include feedback, coaching, hints, an answer outline, or an evaluation.
3. Do not repeat a question already asked in this conversation.
4. Keep a professional interviewer tone.
5. The application controls interview completion. Never end or score the interview during the question stage.";
    }
}
