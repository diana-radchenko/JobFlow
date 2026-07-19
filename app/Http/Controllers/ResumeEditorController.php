<?php

namespace App\Http\Controllers;

use App\Enums\SkillsLevel;
use App\Http\Requests\StoreEducationRequest;
use App\Http\Requests\StoreProjectRequest;
use App\Http\Requests\StoreWorkExperienceRequest;
use App\Http\Requests\UpdatePersonalInfoRequest;
use App\Models\Education;
use App\Models\Project;
use App\Models\Resume;
use App\Models\Skill;
use App\Models\WorkExperience;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class ResumeEditorController extends Controller
{
    private const ITEM_TYPES = [
        'skill' => Skill::class,
        'project' => Project::class,
        'education' => Education::class,
        'work-experience' => WorkExperience::class,
    ];

    public function show(Resume $resume): Response
    {
        $this->authorize('view', $resume);

        return Inertia::render('ResumeEditor', $this->buildPayload($resume));
    }

    public function showSummary(Resume $resume): Response
    {
        $this->authorize('view', $resume);

        return Inertia::render('ResumeEditor', $this->buildPayload($resume, showSummary: true));
    }

    public function updatePersonalInfo(UpdatePersonalInfoRequest $request): RedirectResponse
    {
        $validated = $request->validated();
        $user = $request->user();

        if ($user->profile) {
            $user->profile->update($validated);
        } else {
            $user->profile()->create($validated);
        }

        return back()->with('success', 'Personal information updated successfully.');
    }

    public function storeWorkExperience(StoreWorkExperienceRequest $request, Resume $resume): RedirectResponse
    {
        $this->authorize('update', $resume);

        $workExperience = auth()->user()->workExperiences()->create($request->validated());
        $resume->workExperiences()->attach($workExperience->id, ['order' => $resume->workExperiences()->count()]);

        return back()->with('success', 'Work experience added successfully.');
    }

    public function updateWorkExperience(StoreWorkExperienceRequest $request, Resume $resume, WorkExperience $workExperience): RedirectResponse
    {
        $this->authorize('update', $workExperience);

        $workExperience->update($request->validated());

        return back()->with('success', 'Work experience updated successfully.');
    }

    public function destroyWorkExperience(Resume $resume, WorkExperience $workExperience): RedirectResponse
    {
        $this->authorize('delete', $workExperience);

        $workExperience->delete();

        return back()->with('success', 'Work experience deleted successfully.');
    }

    public function storeEducation(StoreEducationRequest $request, Resume $resume): RedirectResponse
    {
        $this->authorize('update', $resume);

        $education = auth()->user()->educations()->create($request->validated());
        $resume->educations()->attach($education->id, ['order' => $resume->educations()->count()]);

        return back()->with('success', 'Education added successfully.');
    }

    public function updateEducation(StoreEducationRequest $request, Resume $resume, Education $education): RedirectResponse
    {
        $this->authorize('update', $education);

        $education->update($request->validated());

        return back()->with('success', 'Education updated successfully.');
    }

    public function destroyEducation(Resume $resume, Education $education): RedirectResponse
    {
        $this->authorize('delete', $education);

        $education->delete();

        return back()->with('success', 'Education deleted successfully.');
    }

    public function storeSkill(Request $request, Resume $resume): RedirectResponse
    {
        $this->authorize('update', $resume);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'proficiency_level' => ['required', Rule::enum(SkillsLevel::class)],
        ]);

        $skill = auth()->user()->skills()->create($validated);
        $resume->skills()->attach($skill->id, ['order' => $resume->skills()->count()]);

        return back()->with('success', 'Skill added successfully.');
    }

    public function updateSkill(Request $request, Resume $resume, Skill $skill): RedirectResponse
    {
        $this->authorize('update', $skill);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'proficiency_level' => ['required', Rule::enum(SkillsLevel::class)],
        ]);

        $skill->update($validated);

        return back()->with('success', 'Skill updated successfully.');
    }

    public function destroySkill(Resume $resume, Skill $skill): RedirectResponse
    {
        $this->authorize('delete', $skill);

        $skill->delete();

        return back()->with('success', 'Skill deleted successfully.');
    }

    public function storeProject(StoreProjectRequest $request, Resume $resume): RedirectResponse
    {
        $this->authorize('update', $resume);

        $project = auth()->user()->projects()->create($request->validated());
        $resume->projects()->attach($project->id, ['order' => $resume->projects()->count()]);

        return back()->with('success', 'Project added successfully.');
    }

    public function updateProject(StoreProjectRequest $request, Resume $resume, Project $project): RedirectResponse
    {
        $this->authorize('update', $project);

        $project->update($request->validated());

        return back()->with('success', 'Project updated successfully.');
    }

    public function destroyProject(Resume $resume, Project $project): RedirectResponse
    {
        $this->authorize('delete', $project);

        $project->delete();

        return back()->with('success', 'Project deleted successfully.');
    }

    public function updateAdditionalInfo(Request $request, Resume $resume): RedirectResponse
    {
        $this->authorize('update', $resume);

        $validated = $request->validate([
            'languages' => 'nullable|string',
            'certifications' => 'nullable|string',
            'interests' => 'nullable|string',
            'notes' => 'nullable|string',
        ]);

        if ($resume->additionalInformation) {
            $resume->additionalInformation->update($validated);
        } else {
            $resume->additionalInformation()->create(array_merge($validated, ['user_id' => $resume->user_id]));
        }

        return back()->with('success', 'Additional information updated successfully.');
    }

    public function toggleItem(Resume $resume, string $type, int $item): RedirectResponse
    {
        $this->authorize('update', $resume);

        $modelClass = self::ITEM_TYPES[$type] ?? abort(404);

        $item = $modelClass::where('user_id', $resume->user_id)->findOrFail($item);

        $relation = $this->relationFor($resume, $type);
        $relation->toggle([$item->id => ['order' => $relation->count()]]);

        return back()->with('success', 'Resume updated successfully.');
    }

    public function reorderItems(Request $request, Resume $resume, string $type): RedirectResponse
    {
        $this->authorize('update', $resume);

        $modelClass = self::ITEM_TYPES[$type] ?? abort(404);

        $validated = $request->validate([
            'ids' => 'required|array',
            'ids.*' => 'integer',
        ]);

        $ownedIds = $modelClass::where('user_id', $resume->user_id)
            ->whereIn('id', $validated['ids'])
            ->pluck('id')
            ->all();

        $orderedIds = array_values(array_intersect($validated['ids'], $ownedIds));

        $syncData = collect($orderedIds)
            ->mapWithKeys(fn ($id, int $index) => [$id => ['order' => $index]])
            ->all();

        $this->relationFor($resume, $type)->sync($syncData);

        return back();
    }

    private function relationFor(Resume $resume, string $type): BelongsToMany
    {
        return match ($type) {
            'skill' => $resume->skills(),
            'project' => $resume->projects(),
            'education' => $resume->educations(),
            'work-experience' => $resume->workExperiences(),
            default => abort(404),
        };
    }

    private function buildPayload(Resume $resume, bool $showSummary = false): array
    {
        $user = auth()->user();

        return [
            'resume' => $resume->only(['id', 'title']),
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
            ],
            'profile' => $user->profile?->only([
                'first_name',
                'last_name',
                'middle_name',
                'date_of_birth',
                'phone',
                'linkedin_url',
                'city',
                'country',
            ]),
            'workExperiences' => $this->itemsWithInclusion(
                $user->workExperiences()->orderBy('start_date', 'desc')->get(),
                $resume->workExperiences,
            ),
            'educations' => $this->itemsWithInclusion(
                $user->educations()->orderBy('start_date', 'desc')->get(),
                $resume->educations,
            ),
            'skills' => $this->itemsWithInclusion($user->skills()->get(), $resume->skills),
            'projects' => $this->itemsWithInclusion($user->projects()->get(), $resume->projects),
            'additionalInfo' => $resume->additionalInformation?->only(['languages', 'certifications', 'interests', 'notes']),
            'aiMessages' => $this->aiMessages($resume),
            'showSummary' => $showSummary,
        ];
    }

    /**
     * Get the AI assistant chat history for a resume.
     *
     * @return array<int, array{role: string, content: string}>
     */
    private function aiMessages(Resume $resume): array
    {
        if (! $resume->ai_conversation_id) {
            return [];
        }

        return DB::table('agent_conversation_messages')
            ->where('conversation_id', $resume->ai_conversation_id)
            ->whereIn('role', ['user', 'assistant'])
            ->whereNotNull('content')
            ->where('content', '!=', '')
            ->orderBy('created_at')
            ->get()
            ->map(fn ($m) => ['role' => $m->role, 'content' => $m->content])
            ->all();
    }

    private function itemsWithInclusion(Collection $allItems, Collection $includedItems): array
    {
        return $allItems->map(function ($item) use ($includedItems) {
            $included = $includedItems->firstWhere('id', $item->id);

            return array_merge($item->toArray(), [
                'included' => $included !== null,
                'order' => $included?->pivot->order,
            ]);
        })->values()->all();
    }
}
