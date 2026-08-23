<?php

use App\Enums\UserRole;
use App\Models\User;

test('anonymous visitors enter JobFlow through candidate registration', function () {
    $this->get(route('jobflow'))
        ->assertRedirect(route('register', ['type' => UserRole::Candidate->value]));
});

test('anonymous visitors enter HRFlow through employer registration', function () {
    $this->get(route('hrflow'))
        ->assertRedirect(route('register', ['type' => UserRole::Employer->value]));
});

test('candidate is sent to the candidate area from either module entry', function (string $routeName) {
    $candidate = User::factory()->create();

    $this->actingAs($candidate)
        ->get(route($routeName))
        ->assertRedirect(config('fortify.home'));
})->with(['jobflow', 'hrflow']);

test('employer is sent to the employer area from either module entry', function (string $routeName) {
    $employer = User::factory()->employer()->create();

    $this->actingAs($employer)
        ->get(route($routeName))
        ->assertRedirect(route('employer.jobs.index'));
})->with(['jobflow', 'hrflow']);
