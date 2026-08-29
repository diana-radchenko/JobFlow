<?php

namespace App\Http\Controllers;

use App\Models\AdditionalInformation;
use App\Models\Resume;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class ResumeController extends Controller
{
    public function index(): Response
    {
        $resumes = auth()->user()->resumes()
            ->with('additionalInformation')
            ->withCount(['skills', 'projects', 'educations', 'workExperiences', 'volunteerExperiences', 'leadershipActivities', 'publications', 'awardHonors', 'languages'])
            ->orderByDesc('updated_at')
            ->get()
            ->each(function (Resume $resume) {
                $profile = auth()->user()->profile;
                $hasContactInformation = $profile && collect(['first_name', 'last_name', 'email', 'phone', 'city', 'country'])
                    ->contains(fn (string $field) => filled($profile->{$field}));
                $items = [
                    ['label' => 'Contact information', 'complete' => (bool) $hasContactInformation, 'weight' => 20],
                    ['label' => 'Professional summary', 'complete' => filled($resume->additionalInformation?->notes), 'weight' => 15],
                    ['label' => 'Work experience', 'complete' => $resume->work_experiences_count > 0, 'weight' => 20],
                    ['label' => 'Education', 'complete' => $resume->educations_count > 0, 'weight' => 15],
                    ['label' => $resume->skills_count.' skills', 'complete' => $resume->skills_count > 0, 'weight' => 15],
                    ['label' => $resume->projects_count.' projects', 'complete' => $resume->projects_count > 0, 'weight' => 5],
                    ['label' => 'Certifications', 'complete' => filled($resume->additionalInformation?->certifications), 'weight' => 5],
                    ['label' => $resume->languages_count.' languages', 'complete' => $resume->languages_count > 0, 'weight' => 3],
                    ['label' => 'Additional achievements', 'complete' => $resume->award_honors_count + $resume->publications_count + $resume->leadership_activities_count + $resume->volunteer_experiences_count > 0, 'weight' => 2],
                ];

                $completeness = collect($items)->where('complete', true)->sum('weight');
                $resume->setAttribute('completeness', $completeness);
                $resume->setAttribute('completeness_items', $items);
            });

        return Inertia::render('Resumes/Index', [
            'resumes' => $resumes,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
        ]);

        $user = auth()->user();

        $validated['is_primary'] = ! $user->resumes()->exists();

        $resume = $user->resumes()->create($validated);

        $resume->skills()->sync($this->orderedIds($user->skills));
        $resume->projects()->sync($this->orderedIds($user->projects));
        $resume->educations()->sync($this->orderedIds($user->educations));
        $resume->workExperiences()->sync($this->orderedIds($user->workExperiences));
        $resume->volunteerExperiences()->sync($this->orderedIds($user->volunteerExperiences));
        $resume->leadershipActivities()->sync($this->orderedIds($user->leadershipActivities));
        $resume->publications()->sync($this->orderedIds($user->publications));
        $resume->awardHonors()->sync($this->orderedIds($user->awardHonors));
        $resume->languages()->sync($this->orderedIds($user->languages));

        $this->copyLatestAdditionalInfo($user->id, $resume);

        return redirect()->route('resume-editor.show', $resume)->with('success', 'Resume created successfully.');
    }

    public function update(Request $request, Resume $resume): RedirectResponse
    {
        $this->authorize('update', $resume);

        $validated = $request->validate([
            'title' => 'required|string|max:255',
        ]);

        $resume->update($validated);

        return back()->with('success', 'Resume renamed successfully.');
    }

    public function destroy(Resume $resume): RedirectResponse
    {
        $this->authorize('delete', $resume);

        $userId = $resume->user_id;
        $wasPrimary = (bool) $resume->is_primary;

        DB::transaction(function () use ($resume, $userId, $wasPrimary) {
            $resume->delete();

            if ($wasPrimary) {
                Resume::where('user_id', $userId)
                    ->orderByDesc('updated_at')
                    ->first()
                    ?->update(['is_primary' => true]);
            }
        });

        return redirect()->route('resumes.index')->with('success', 'Resume deleted successfully.');
    }

    public function setPrimary(Resume $resume): RedirectResponse
    {
        $this->authorize('update', $resume);

        DB::transaction(function () use ($resume) {
            Resume::where('user_id', $resume->user_id)
                ->where('id', '!=', $resume->id)
                ->update(['is_primary' => false]);

            $resume->update(['is_primary' => true]);
        });

        return back()->with('success', 'Primary resume updated successfully.');
    }

    public function duplicate(Resume $resume): RedirectResponse
    {
        $this->authorize('view', $resume);

        $copy = $resume->user->resumes()->create([
            'title' => $resume->title.' (copy)',
        ]);

        $copy->skills()->sync($this->pivotOrderIds($resume->skills));
        $copy->projects()->sync($this->pivotOrderIds($resume->projects));
        $copy->educations()->sync($this->pivotOrderIds($resume->educations));
        $copy->workExperiences()->sync($this->pivotOrderIds($resume->workExperiences));
        $copy->volunteerExperiences()->sync($this->pivotOrderIds($resume->volunteerExperiences));
        $copy->leadershipActivities()->sync($this->pivotOrderIds($resume->leadershipActivities));
        $copy->publications()->sync($this->pivotOrderIds($resume->publications));
        $copy->awardHonors()->sync($this->pivotOrderIds($resume->awardHonors));
        $copy->languages()->sync($this->pivotOrderIds($resume->languages));

        if ($resume->additionalInformation) {
            $copy->additionalInformation()->create([
                'user_id' => $copy->user_id,
                'languages' => $resume->additionalInformation->languages,
                'certifications' => $resume->additionalInformation->certifications,
                'interests' => $resume->additionalInformation->interests,
                'notes' => $resume->additionalInformation->notes,
            ]);
        }

        return redirect()->route('resume-editor.show', $copy)->with('success', 'Resume duplicated successfully.');
    }

    private function orderedIds(Collection $items): array
    {
        return $items->values()
            ->mapWithKeys(fn ($item, int $index) => [$item->id => ['order' => $index]])
            ->all();
    }

    private function pivotOrderIds(Collection $items): array
    {
        return $items->mapWithKeys(fn ($item) => [$item->id => ['order' => $item->pivot->order]])->all();
    }

    private function copyLatestAdditionalInfo(int $userId, Resume $resume): void
    {
        $latest = AdditionalInformation::whereHas('resume', fn ($query) => $query->where('user_id', $userId))
            ->where('resume_id', '!=', $resume->id)
            ->latest('updated_at')
            ->first();

        if (! $latest) {
            return;
        }

        $resume->additionalInformation()->create([
            'user_id' => $userId,
            'languages' => $latest->languages,
            'certifications' => $latest->certifications,
            'interests' => $latest->interests,
            'notes' => $latest->notes,
        ]);
    }
}


