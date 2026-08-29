<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

#[Fillable(['user_id', 'title', 'ai_conversation_id', 'is_primary'])]
class Resume extends Model
{
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function additionalInformation(): HasOne
    {
        return $this->hasOne(AdditionalInformation::class);
    }

    public function skills(): BelongsToMany
    {
        return $this->belongsToMany(Skill::class, 'resume_skill')
            ->withPivot('order')
            ->withTimestamps()
            ->orderByPivot('order');
    }

    public function projects(): BelongsToMany
    {
        return $this->belongsToMany(Project::class, 'resume_project')
            ->withPivot('order')
            ->withTimestamps()
            ->orderByPivot('order');
    }

    public function educations(): BelongsToMany
    {
        return $this->belongsToMany(Education::class, 'resume_education')
            ->withPivot('order')
            ->withTimestamps()
            ->orderByPivot('order');
    }

    public function workExperiences(): BelongsToMany
    {
        return $this->belongsToMany(WorkExperience::class, 'resume_work_experience')
            ->withPivot('order')
            ->withTimestamps()
            ->orderByPivot('order');
    }

    public function volunteerExperiences(): BelongsToMany
    {
        return $this->belongsToMany(VolunteerExperience::class, 'resume_volunteer_experience')
            ->withPivot('order')
            ->withTimestamps()
            ->orderByPivot('order');
    }

    public function leadershipActivities(): BelongsToMany
    {
        return $this->belongsToMany(LeadershipActivity::class, 'resume_leadership_activity')
            ->withPivot('order')
            ->withTimestamps()
            ->orderByPivot('order');
    }

    public function publications(): BelongsToMany
    {
        return $this->belongsToMany(Publication::class, 'resume_publication')
            ->withPivot('order')->withTimestamps()->orderByPivot('order');
    }

    public function awardHonors(): BelongsToMany
    {
        return $this->belongsToMany(AwardHonor::class, 'resume_award_honor')
            ->withPivot('order')->withTimestamps()->orderByPivot('order');
    }

    public function languages(): BelongsToMany
    {
        return $this->belongsToMany(Language::class, 'resume_language')
            ->withPivot('order')->withTimestamps()->orderByPivot('order');
    }
}

