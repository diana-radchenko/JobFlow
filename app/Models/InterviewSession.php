<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InterviewSession extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'resume_id',
        'work_job_id',
        'conversation_id',
        'type',
        'complexity',
        'mode',
        'status',
        'application_id',
        'employer_id',
        'scheduled_at',
        'timezone',
        'duration_minutes',
        'employer_note',
    ];

    protected function casts(): array
    {
        return ['scheduled_at' => 'datetime'];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function resume(): BelongsTo
    {
        return $this->belongsTo(Resume::class);
    }

    public function workJob(): BelongsTo
    {
        return $this->belongsTo(WorkJob::class);
    }

    public function application(): BelongsTo
    {
        return $this->belongsTo(UserWorkJobApplication::class, 'application_id');
    }

    public function employer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'employer_id');
    }
}
