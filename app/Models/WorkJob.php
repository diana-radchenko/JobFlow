<?php

namespace App\Models;

use Database\Factories\WorkJobFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Builder;

/**
 *  Table to store jobs. Jobs name were used already by Laravel jobs table, so why we used work_jobs table name.
 */
#[Fillable(['title', 'salary_start', 'salary_end', 'salary_currency', 'salary_period', 'company', 'description', 'responsibilities', 'requirements', 'benefits', 'contacts', 'location', 'industry', 'position_level', 'employment_type', 'workplace_type', 'technologies', 'status', 'published_at'])]
class WorkJob extends Model
{
    /** @use HasFactory<WorkJobFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'salary_start' => 'decimal:2',
            'salary_end' => 'decimal:2',
            'technologies' => 'array',
            'published_at' => 'datetime',
        ];
    }

    /**
     * Employer who posted the job. Null for the platform's own seeded listings.
     */
    public function employer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function applications(): HasMany
    {
        return $this->hasMany(UserWorkJobApplication::class);
    }

    public function interviewSessions(): HasMany
    {
        return $this->hasMany(InterviewSession::class);
    }

    public function conversations(): HasMany
    {
        return $this->hasMany(JobConversation::class);
    }

    public function scopePublished(Builder $query): Builder
    {
        return $query->whereNotNull('user_id')->where('status', 'published');
    }

    public function applicants(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'user_work_job_applications', 'work_job_id', 'user_id')
            ->withPivot('status')
            ->withTimestamps();
    }

    public function savedBy(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'saved_work_jobs')->withTimestamps();
    }
}
