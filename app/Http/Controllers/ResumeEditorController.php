<?php

namespace App\Http\Controllers;

use App\Models\Education;
use App\Models\Project;
use App\Models\Skill;
use App\Models\WorkExperience;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ResumeEditorController extends Controller
{
    public function show(): Response
    {
        $user = auth()->user();

        return Inertia::render('ResumeEditor', [
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
            ],
            'profile' => $user->profile?->only(['phone', 'linkedin_url', 'location']),
            'workExperiences' => $user->workExperiences()->orderBy('start_date', 'desc')->get(),
            'educations' => $user->educations()->orderBy('start_date', 'desc')->get(),
            'skills' => $user->skills()->get(),
            'projects' => $user->projects()->get(),
            'additionalInfo' => $user->additionalInformation?->only(['languages', 'certifications', 'interests', 'notes']),
        ]);
    }

    public function updatePersonalInfo(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'phone' => ['nullable', 'string', 'max:20', 'regex:/^\+?[0-9]*$/'],
            'linkedin_url' => 'nullable|url',
            'location' => 'nullable|string|max:255',
        ]);

        $user = auth()->user();

        if ($user->profile) {
            $user->profile->update($validated);
        } else {
            $user->profile()->create($validated);
        }

        return back()->with('success', 'Personal information updated successfully.');
    }

    public function storeWorkExperience(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'company_name' => 'required|string|max:255',
            'job_title' => 'required|string|max:255',
            'location' => 'nullable|string|max:255',
            'start_date' => 'required|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'is_current' => 'boolean',
            'description' => 'nullable|string',
        ]);

        auth()->user()->workExperiences()->create($validated);

        return back()->with('success', 'Work experience added successfully.');
    }

    public function updateWorkExperience(Request $request, WorkExperience $workExperience): RedirectResponse
    {
        $validated = $request->validate([
            'company_name' => 'required|string|max:255',
            'job_title' => 'required|string|max:255',
            'location' => 'nullable|string|max:255',
            'start_date' => 'required|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'is_current' => 'boolean',
            'description' => 'nullable|string',
        ]);

        $workExperience->update($validated);

        return back()->with('success', 'Work experience updated successfully.');
    }

    public function destroyWorkExperience(WorkExperience $workExperience): RedirectResponse
    {
        $workExperience->delete();

        return back()->with('success', 'Work experience deleted successfully.');
    }

    public function storeEducation(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'degree' => 'required|in:HIGHSCHOOL,CERTIFICATE,ASSOCIATE,BACHELORS,MASTERS,DOCTORATE,POSTDOC',
            'institution' => 'required|string|max:255',
            'field_of_study' => 'nullable|string|max:255',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'description' => 'nullable|string',
        ]);

        auth()->user()->educations()->create($validated);

        return back()->with('success', 'Education added successfully.');
    }

    public function updateEducation(Request $request, Education $education): RedirectResponse
    {
        $validated = $request->validate([
            'degree' => 'required|in:HIGHSCHOOL,CERTIFICATE,ASSOCIATE,BACHELORS,MASTERS,DOCTORATE,POSTDOC',
            'institution' => 'required|string|max:255',
            'field_of_study' => 'nullable|string|max:255',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'description' => 'nullable|string',
        ]);

        $education->update($validated);

        return back()->with('success', 'Education updated successfully.');
    }

    public function destroyEducation(Education $education): RedirectResponse
    {
        $education->delete();

        return back()->with('success', 'Education deleted successfully.');
    }

    public function storeSkill(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'proficiency_level' => 'required|in:Beginner,Intermediate,Advanced,Expert',
        ]);

        auth()->user()->skills()->create($validated);

        return back()->with('success', 'Skill added successfully.');
    }

    public function updateSkill(Request $request, Skill $skill): RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'proficiency_level' => 'required|in:Beginner,Intermediate,Advanced,Expert',
        ]);

        $skill->update($validated);

        return back()->with('success', 'Skill updated successfully.');
    }

    public function destroySkill(Skill $skill): RedirectResponse
    {
        $skill->delete();

        return back()->with('success', 'Skill deleted successfully.');
    }

    public function storeProject(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'type' => 'required|in:project,achievement',
            'description' => 'nullable|string',
            'url' => 'nullable|url',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
        ]);

        auth()->user()->projects()->create($validated);

        return back()->with('success', 'Project added successfully.');
    }

    public function updateProject(Request $request, Project $project): RedirectResponse
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'type' => 'required|in:project,achievement',
            'description' => 'nullable|string',
            'url' => 'nullable|url',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
        ]);

        $project->update($validated);

        return back()->with('success', 'Project updated successfully.');
    }

    public function destroyProject(Project $project): RedirectResponse
    {
        $project->delete();

        return back()->with('success', 'Project deleted successfully.');
    }

    public function updateAdditionalInfo(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'languages' => 'nullable|string',
            'certifications' => 'nullable|string',
            'interests' => 'nullable|string',
            'notes' => 'nullable|string',
        ]);

        $user = auth()->user();

        if ($user->additionalInformation) {
            $user->additionalInformation->update($validated);
        } else {
            $user->additionalInformation()->create($validated);
        }

        return back()->with('success', 'Additional information updated successfully.');
    }

    public function showSummary(): Response
    {
        $user = auth()->user();

        return Inertia::render('ResumeEditor', [
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
            ],
            'profile' => $user->profile?->only(['phone', 'linkedin_url', 'location']),
            'workExperiences' => $user->workExperiences()->orderBy('start_date', 'desc')->get(),
            'educations' => $user->educations()->orderBy('start_date', 'desc')->get(),
            'skills' => $user->skills()->get(),
            'projects' => $user->projects()->get(),
            'additionalInfo' => $user->additionalInformation?->only(['languages', 'certifications', 'interests', 'notes']),
            'showSummary' => true,
        ]);
    }
}
