<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    use RefreshDatabase;

    /**
     * A basic smoke test: the application boots and a route responds.
     *
     * Originally this hit GET / which renders the SPA view — but that view
     * uses the @vite directive, which requires public/build/manifest.json
     * (a frontend build artifact). The CI test job runs PHP only, without the
     * frontend build, so the manifest is absent there and @vite throws.
     *
     * Hitting the public settings API instead verifies the same thing
     * (app boots, routing works, middleware chain runs) without the Vite
     * dependency. The SPA view itself is covered by the frontend-build job.
     */
    public function test_the_application_returns_a_successful_response(): void
    {
        $response = $this->getJson('/api/settings');

        $response->assertOk();
    }
}
