<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

#[Fillable(['user_id', 'title'])]
class Resume extends Model
{
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
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
}
