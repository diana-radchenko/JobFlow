<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'interview_session_id', 'changed_by', 'action', 'scheduled_at', 'timezone',
    'duration_minutes', 'interview_format', 'meeting_link', 'location', 'employer_note',
])]
class InterviewSessionEvent extends Model
{
    protected function casts(): array
    {
        return ['scheduled_at' => 'datetime'];
    }

    public function interviewSession(): BelongsTo
    {
        return $this->belongsTo(InterviewSession::class);
    }

    public function changedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'changed_by');
    }
}
