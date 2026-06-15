<?php

use App\Models\User;

test('guests are redirected to the login page from salary', function () {
    $response = $this->get(route('salary'));
    $response->assertRedirect(route('login'));
});

test('authenticated users can visit the salary page', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $response = $this->get(route('salary'));
    $response->assertOk();
});
