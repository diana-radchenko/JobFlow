<?php

namespace App\Providers;

use App\Models\AdditionalInformation;
use App\Models\Education;
use App\Models\LeadershipActivity;
use App\Models\AwardHonor;
use App\Models\Language;
use App\Models\Publication;
use App\Models\Project;
use App\Models\Resume;
use App\Models\Skill;
use App\Models\VolunteerExperience;
use App\Models\WorkExperience;
use App\Models\WorkJob;
use App\Policies\OwnedResourcePolicy;
use App\Policies\ResumePolicy;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * The model to policy mappings for the application.
     *
     * @var array<class-string, class-string>
     */
    protected $policies = [
        WorkExperience::class => OwnedResourcePolicy::class,
        Education::class => OwnedResourcePolicy::class,
        Skill::class => OwnedResourcePolicy::class,
        Project::class => OwnedResourcePolicy::class,
        VolunteerExperience::class => OwnedResourcePolicy::class,
        LeadershipActivity::class => OwnedResourcePolicy::class,
        Publication::class => OwnedResourcePolicy::class,
        AwardHonor::class => OwnedResourcePolicy::class,
        Language::class => OwnedResourcePolicy::class,
        AdditionalInformation::class => OwnedResourcePolicy::class,
        WorkJob::class => OwnedResourcePolicy::class,
        Resume::class => ResumePolicy::class,
    ];

    /**
     * Register any authentication / authorization services.
     */
    public function boot(): void
    {
        //
    }
}

