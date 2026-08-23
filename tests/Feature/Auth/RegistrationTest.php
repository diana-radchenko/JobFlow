<?php

use App\Enums\UserRole;
use App\Models\User;
use Laravel\Fortify\Features;

beforeEach(function () {
    $this->skipUnlessFortifyHas(Features::registration());
});

test('registration screen can be rendered', function () {
    $response = $this->get(route('register'));

    $response->assertOk();
});

test('the register screen preselects the employer profile from the HRFlow link', function () {
    $this->get(route('register', ['type' => 'employer']))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('auth/Register')
            ->where('accountType', 'employer')
        );
});

test('the register screen defaults to the candidate profile', function () {
    $this->get(route('register'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page->where('accountType', 'candidate'));
});

test('new users can register', function () {
    $response = $this->post(route('register.store'), [
        'email' => 'test@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
        'account_type' => UserRole::Candidate->value,
    ]);

    $this->assertAuthenticated();
    $response->assertRedirect(route('jobflow', absolute: false));

    $this->assertDatabaseHas('users', [
        'email' => 'test@example.com',
        'name' => null,
    ]);

    expect(User::firstWhere('email', 'test@example.com')->hasRole(UserRole::Candidate->value))->toBeTrue();
    expect(User::firstWhere('email', 'test@example.com')->resumes()->exists())->toBeFalse();
    expect(User::firstWhere('email', 'test@example.com')->employerProfile()->exists())->toBeFalse();
});

test('employers register with the employer role and land on their job list', function () {
    $response = $this->post(route('register.store'), [
        'email' => 'employer@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
        'account_type' => UserRole::Employer->value,
    ]);

    $this->assertAuthenticated();
    $response->assertRedirect(route('hrflow', absolute: false));

    expect(User::firstWhere('email', 'employer@example.com')->hasRole(UserRole::Employer->value))->toBeTrue();
    expect(User::firstWhere('email', 'employer@example.com')->resumes()->exists())->toBeFalse();
    expect(User::firstWhere('email', 'employer@example.com')->employerProfile()->exists())->toBeTrue();
});

test('an account type query cannot change an authenticated users stored role', function () {
    $candidate = User::factory()->create();

    $this->actingAs($candidate)
        ->get(route('register', ['type' => UserRole::Employer->value]))
        ->assertRedirect(route('hrflow'));

    expect($candidate->fresh()->hasRole(UserRole::Candidate->value))->toBeTrue()
        ->and($candidate->fresh()->hasRole(UserRole::Employer->value))->toBeFalse();
});

test('registration requires a known account type', function (?string $accountType) {
    $this->post(route('register.store'), [
        'email' => 'test@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
        'account_type' => $accountType,
    ])->assertSessionHasErrors('account_type');

    $this->assertGuest();
    $this->assertDatabaseMissing('users', ['email' => 'test@example.com']);
})->with([
    'missing' => null,
    'unknown role' => 'admin',
]);
