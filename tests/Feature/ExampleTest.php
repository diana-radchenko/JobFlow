<?php

test('starts guests in candidate registration', function () {
    $response = $this->get(route('home'));

    $response->assertRedirect(route('register', ['type' => 'candidate']));
});
