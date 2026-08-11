<?php

namespace App\Models;

use Database\Factories\WorkJobFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 *  Table to store jobs. Jobs name were used already by Laravel jobs table, so why we used work_jobs table name.
 */
#[Fillable(['title', 'salary_start', 'salary_end', 'company', 'description', 'contacts', 'location', 'technologies'])]
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

    public function applicants(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'user_work_job_applications', 'work_job_id', 'user_id')
            ->withPivot('status')
            ->withTimestamps();
    }
}
