<?php

namespace App\Enums;

/**
 * Roles handled by spatie/laravel-permission. We only use roles, no permissions:
 * routes are gated with the `role:...` middleware.
 */
enum UserRole: string
{
    case Candidate = 'candidate';
    case Employer = 'employer';

    /**
     * Where a user of this role lands after logging in or registering.
     */
    public function home(): string
    {
        return match ($this) {
            self::Employer => route('employer.jobs.index'),
            self::Candidate => config('fortify.home'),
        };
    }
}
