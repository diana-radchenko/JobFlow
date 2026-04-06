<?php

namespace App\Models;

use App\Enums\ApplicationStatus;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['user_id', 'work_job_id', 'status'])]
class UserWorkJobApplication extends Model
{
    protected function casts(): array
    {
        return [
            'status' => ApplicationStatus::class,
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function workJob(): BelongsTo
    {
        return $this->belongsTo(WorkJob::class);
    }
}
