<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class JobConversation extends Model
{
    protected $fillable = ['work_job_id', 'application_id', 'employer_user_id', 'candidate_user_id'];

    public function workJob(): BelongsTo { return $this->belongsTo(WorkJob::class); }
    public function application(): BelongsTo { return $this->belongsTo(UserWorkJobApplication::class); }
    public function employer(): BelongsTo { return $this->belongsTo(User::class, 'employer_user_id'); }
    public function candidate(): BelongsTo { return $this->belongsTo(User::class, 'candidate_user_id'); }
    public function messages(): HasMany { return $this->hasMany(JobMessage::class); }
}
