<?php

namespace App\Actions\Fortify;

use App\Concerns\PasswordValidationRules;
use App\Concerns\ProfileValidationRules;
use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Laravel\Fortify\Contracts\CreatesNewUsers;

class CreateNewUser implements CreatesNewUsers
{
    use PasswordValidationRules, ProfileValidationRules;

    /**
     * Validate and create a newly registered user.
     *
     * @param  array<string, string>  $input
     */
    public function create(array $input): User
    {
        Validator::make($input, [
            ...$this->profileRules(),
            'password' => $this->passwordRules(),
            'account_type' => ['required', Rule::enum(UserRole::class)],
        ])->validate();

        $user = User::create([
            'name' => $input['name'] ?? null,
            'email' => $input['email'],
            'password' => $input['password'],
        ]);

        $user->assignRole($input['account_type']);

        if ($input['account_type'] === UserRole::Employer->value) {
            $user->employerProfile()->create();
        }

        return $user;
    }
}
