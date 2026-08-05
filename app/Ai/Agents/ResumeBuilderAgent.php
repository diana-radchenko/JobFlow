<?php

namespace App\Ai\Agents;

use App\Ai\Tools\SaveAdditionalInfo;
use App\Ai\Tools\SaveEducation;
use App\Ai\Tools\SaveLeadershipActivity;
use App\Ai\Tools\SavePersonalInfo;
use App\Ai\Tools\SaveProject;
use App\Ai\Tools\SaveSkill;
use App\Ai\Tools\SaveVolunteerExperience;
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
        $personalInfo = json_encode($this->personalInfo(), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) ?: '{}';

        $hasExistingData = $this->personalInfo() !== []
            || array_filter(json_decode($this->resumeContext, true) ?: []) !== [];

        $startRule = $hasExistingData
            ? 'Your FIRST message must briefly list the sections below and ask which one the candidate wants to work on. Wait for their choice before asking anything else, and never re-ask for data already shown above.'
            : "This resume is empty, so build it from scratch: greet the candidate briefly, explain you'll ask a few questions, and begin with personal information.";

        return "You are a friendly resume-building assistant. Your goal is to help the candidate build a complete resume by asking questions and saving their answers.

The candidate's personal information on file: {$personalInfo}

The resume already contains the following data: {$this->resumeContext}

Follow these rules:
1. {$startRule}
2. The sections are: personal information, work experience, education, skills, projects, volunteer & community involvement, leadership & extracurricular activities, and additional information (languages, certifications, interests). When the candidate has no preference, work through them in that order, skipping anything already filled in above.
3. Ask ONE focused question at a time and wait for the answer. Ask brief follow-ups if a required detail is missing.
4. As soon as you have enough detail for an item, immediately call the matching tool to save it. Save each work experience, education entry, skill, project, volunteer experience, and leadership activity as a separate tool call.
5. After saving, confirm what you saved in one short sentence and move on to the next question.
6. Dates must be passed to tools in YYYY-MM-DD format; ask the candidate to clarify if they give a partial date.
7. When all sections are covered, summarize what was added and let them know they can review or edit everything in the resume editor.
8. Keep a warm, encouraging, and concise tone.";
    }

    /**
     * Get the personal information already stored for the candidate.
     *
     * @return array<string, mixed>
     */
    private function personalInfo(): array
    {
        $profile = $this->resume->user->profile?->only([
            'first_name',
            'last_name',
            'middle_name',
            'date_of_birth',
            'phone',
            'linkedin_url',
            'city',
            'country',
        ]) ?? [];

        return array_filter($profile, fn ($value) => $value !== null && $value !== '');
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
            new SaveVolunteerExperience($this->resume),
            new SaveLeadershipActivity($this->resume),
            new SaveAdditionalInfo($this->resume),
        ];
    }
}
