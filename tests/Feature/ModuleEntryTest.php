<?php

use App\Enums\UserRole;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

test('anonymous visitors enter the platform through candidate registration', function () {
    $this->get(route('home'))
        ->assertRedirect(route('register', ['type' => UserRole::Candidate->value]));
});

test('candidate opening the platform root enters JobFlow', function () {
    $candidate = User::factory()->create();

    $this->actingAs($candidate)
        ->get(route('home'))
        ->assertRedirect(route('jobflow'));
});

test('employer opening the platform root enters HRFlow', function () {
    $employer = User::factory()->employer()->create();

    $this->actingAs($employer)
        ->get(route('home'))
        ->assertRedirect(route('hrflow'));
});

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
            ->where('currentRole', UserRole::Candidate->value)
            ->where('returnUrl', route('jobflow')));
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
            ->where('currentRole', UserRole::Employer->value)
            ->where('returnUrl', route('hrflow')));
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

test('logging out and back in preserves the candidates data', function () {
    $candidate = User::factory()->create();
    $resume = $candidate->resumes()->create(['title' => 'Preserved resume']);

    $this->actingAs($candidate)->post(route('logout'))->assertRedirect(route('home'));

    $this->post(route('login.store'), [
        'email' => $candidate->email,
        'password' => 'password',
    ])->assertRedirect(route('jobflow'));

    $this->assertDatabaseHas('resumes', [
        'id' => $resume->id,
        'user_id' => $candidate->id,
        'title' => 'Preserved resume',
    ]);
});
