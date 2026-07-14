<?php

namespace App\Http\Controllers;

use App\Models\AdditionalInformation;
use App\Models\Resume;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Inertia\Inertia;
use Inertia\Response;

class ResumeController extends Controller
{
    public function index(): Response
    {
        $resumes = auth()->user()->resumes()
            ->withCount(['skills', 'projects', 'educations', 'workExperiences'])
            ->orderByDesc('updated_at')
            ->get();

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
        $resume = $user->resumes()->create($validated);

        $resume->skills()->sync($this->orderedIds($user->skills));
        $resume->projects()->sync($this->orderedIds($user->projects));
        $resume->educations()->sync($this->orderedIds($user->educations));
        $resume->workExperiences()->sync($this->orderedIds($user->workExperiences));

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

        $resume->delete();

        return redirect()->route('resumes.index')->with('success', 'Resume deleted successfully.');
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
