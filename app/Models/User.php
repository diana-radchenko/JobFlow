<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Enums\UserRole;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Fortify\TwoFactorAuthenticatable;
use Spatie\Permission\Traits\HasRoles;

#[Fillable(['name', 'email', 'password'])]
#[Hidden(['password', 'two_factor_secret', 'two_factor_recovery_codes', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, HasRoles, Notifiable, TwoFactorAuthenticatable;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'two_factor_confirmed_at' => 'datetime',
        ];
    }

    /**
     * The single role this user was registered with, if any.
     */
    public function role(): ?UserRole
    {
        return UserRole::tryFrom((string) $this->getRoleNames()->first());
    }

    public function profile(): HasOne
    {
        return $this->hasOne(UserProfile::class);
    }

    public function educations(): HasMany
    {
        return $this->hasMany(Education::class);
    }

    public function workExperiences(): HasMany
    {
        return $this->hasMany(WorkExperience::class);
    }

    public function skills(): HasMany
    {
        return $this->hasMany(Skill::class);
    }

    public function projects(): HasMany
    {
        return $this->hasMany(Project::class);
    }

    public function volunteerExperiences(): HasMany
    {
        return $this->hasMany(VolunteerExperience::class);
    }

    public function leadershipActivities(): HasMany
    {
        return $this->hasMany(LeadershipActivity::class);
    }

    public function publications(): HasMany
    {
        return $this->hasMany(Publication::class);
    }

    public function awardHonors(): HasMany
    {
        return $this->hasMany(AwardHonor::class);
    }

    public function languages(): HasMany
    {
        return $this->hasMany(Language::class);
    }

    public function additionalInformation(): HasOne
    {
        return $this->hasOne(AdditionalInformation::class);
    }

    public function resumes(): HasMany
    {
        return $this->hasMany(Resume::class);
    }

    public function applications(): HasMany
    {
        return $this->hasMany(UserWorkJobApplication::class);
    }

    /**
     * Job postings this user owns as an employer.
     */
    public function workJobs(): HasMany
    {
        return $this->hasMany(WorkJob::class);
    }

    public function appliedJobs(): BelongsToMany
    {
        return $this->belongsToMany(WorkJob::class, 'user_work_job_applications', 'user_id', 'work_job_id')
            ->withPivot('status')
            ->withTimestamps();
    }
}

