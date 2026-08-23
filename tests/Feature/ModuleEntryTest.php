<?php

use App\Enums\UserRole;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

test('anonymous visitors enter JobFlow through candidate registration', function () {
    $this->get(route('jobflow'))
        ->assertRedirect(route('register', ['type' => UserRole::Candidate->value]));
});

test('anonymous visitors enter HRFlow through employer registration', function () {
    $this->get(route('hrflow'))
        ->assertRedirect(route('register', ['type' => UserRole::Employer->value]));
});

test('candidate enters JobFlow directly', function () {
    $candidate = User::factory()->create();

    $this->actingAs($candidate)
        ->get(route('jobflow'))
        ->assertRedirect(config('fortify.home'));
});

test('employer enters HRFlow directly', function () {
    $employer = User::factory()->employer()->create();

    $this->actingAs($employer)
        ->get(route('hrflow'))
        ->assertRedirect(route('employer.jobs.index'));
});

test('candidate sees the HRFlow entry instead of being redirected to resumes', function () {
    $candidate = User::factory()->create();

    $this->actingAs($candidate)
        ->get(route('hrflow'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('auth/ModuleEntry')
            ->where('moduleName', 'HRFlow')
            ->where('targetRole', UserRole::Employer->value)
            ->where('currentRole', UserRole::Candidate->value));
});

test('employer sees the JobFlow entry instead of entering the candidate area', function () {
    $employer = User::factory()->employer()->create();

    $this->actingAs($employer)
        ->get(route('jobflow'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('auth/ModuleEntry')
            ->where('moduleName', 'JobFlow')
            ->where('targetRole', UserRole::Candidate->value)
            ->where('currentRole', UserRole::Employer->value));
});

test('candidate can leave the current session for employer login', function () {
    $candidate = User::factory()->create();

    $this->actingAs($candidate)
        ->post(route('module-entry.switch', ['module' => 'hrflow', 'action' => 'login']))
        ->assertRedirect(route('login', ['type' => UserRole::Employer->value]))
        ->assertSessionHas('module_entry', 'hrflow');

    $this->assertGuest();
});

test('wrong account login returns to the requested module entry', function () {
    $candidate = User::factory()->create();

    $this->withSession(['module_entry' => 'hrflow'])
        ->post(route('login'), [
            'email' => $candidate->email,
            'password' => 'password',
        ])
        ->assertRedirect(route('hrflow'))
        ->assertSessionHas('module_error');
});
