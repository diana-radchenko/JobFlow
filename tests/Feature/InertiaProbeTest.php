<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InertiaProbeTest extends TestCase
{
    public function test_probe(): void
    {
        $user = User::where('email', 'ryhuqaja@mailinator.com')->first();
        $this->assertNotNull($user, 'user not found');

        $response = $this->actingAs($user)->get('/resumes', [
            'X-Inertia' => 'true',
            'Accept' => 'application/json',
        ]);

        fwrite(STDERR, "STATUS: " . $response->getStatusCode() . "\n");
        fwrite(STDERR, "BODY: " . $response->getContent() . "\n");
    }
}
