<?php

namespace App\Ai\Agents;

use App\Ai\Tools\SaveAdditionalInfo;
use App\Ai\Tools\SaveEducation;
use App\Ai\Tools\SavePersonalInfo;
use App\Ai\Tools\SaveProject;
use App\Ai\Tools\SaveSkill;
use App\Ai\Tools\SaveWorkExperience;
use App\Models\Resume;
use Laravel\Ai\Attributes\MaxSteps;
use Laravel\Ai\Concerns\RemembersConversations;
use Laravel\Ai\Contracts\Agent;
use Laravel\Ai\Contracts\Conversational;
use Laravel\Ai\Contracts\HasTools;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Promptable;

#[MaxSteps(12)]
class ResumeBuilderAgent implements Agent, Conversational, HasTools
{
    use Promptable, RemembersConversations;

    public function __construct(
        public Resume $resume,
        public string $resumeContext = '',
    ) {}

    /**
     * Get the instructions that the agent should follow.
     */
    public function instructions(): string
    {
        return "You are a friendly resume-building assistant. Your goal is to help the candidate build a complete resume by asking questions and saving their answers.

The resume already contains the following data: {$this->resumeContext}

Follow these rules:
1. Greet the candidate briefly and explain you'll ask a few questions to build their resume.
2. Work through the sections in this order, but skip anything already filled in above and don't re-ask for it: personal information, work experience, education, skills, projects, and additional information (languages, certifications, interests).
3. Ask ONE focused question at a time and wait for the answer. Ask brief follow-ups if a required detail is missing.
4. As soon as you have enough detail for an item, immediately call the matching tool to save it. Save each work experience, education entry, skill, and project as a separate tool call.
5. After saving, confirm what you saved in one short sentence and move on to the next question.
6. Dates must be passed to tools in YYYY-MM-DD format; ask the candidate to clarify if they give a partial date.
7. When all sections are covered, summarize what was added and let them know they can review or edit everything in the resume editor.
8. Keep a warm, encouraging, and concise tone.";
    }

    /**
     * Get the tools available to the agent.
     *
     * @return iterable<Tool>
     */
    public function tools(): iterable
    {
        return [
            new SavePersonalInfo($this->resume),
            new SaveWorkExperience($this->resume),
            new SaveEducation($this->resume),
            new SaveSkill($this->resume),
            new SaveProject($this->resume),
            new SaveAdditionalInfo($this->resume),
        ];
    }
}
