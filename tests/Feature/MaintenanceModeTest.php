<?php

namespace Tests\Feature;

use App\Models\AdminUser;
use App\Models\Setting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MaintenanceModeTest extends TestCase
{
    use RefreshDatabase;

    private string $marker;

    protected function setUp(): void
    {
        parent::setUp();

        // CheckMaintenanceMode only runs when the app is "installed"
        $this->marker = storage_path('app/installed');
        file_put_contents($this->marker, json_encode([
            'installed_at' => now()->toIso8601String(),
            'version' => config('app.version'),
        ]));

        Setting::set('maintenance_mode', '1');
    }

    protected function tearDown(): void
    {
        @unlink($this->marker);
        Setting::set('maintenance_mode', '0');

        parent::tearDown();
    }

    public function test_maintenance_returns_503_for_regular_visitors(): void
    {
        $response = $this->get('/');

        $response->assertStatus(503);
        $response->assertSee('站点维护中');
    }

    public function test_maintenance_bots_receive_503_not_site_content(): void
    {
        // Regression: the bot prerender middleware used to run BEFORE the
        // maintenance check, letting search engines index the full site
        // while maintenance mode was active.
        $response = $this->get('/', ['User-Agent' => 'Mozilla/5.0 (compatible; Googlebot/2.1; +http://www.google.com/bot.html)']);

        $response->assertStatus(503);
    }

    public function test_security_headers_present_on_maintenance_response(): void
    {
        // SecurityHeaders must wrap the whole stack so the 503 also carries them
        $response = $this->get('/');

        $response->assertStatus(503);
        $response->assertHeader('X-Frame-Options', 'DENY');
        $response->assertHeader('X-Content-Type-Options', 'nosniff');
    }

    public function test_authenticated_admin_can_use_admin_during_maintenance(): void
    {
        $admin = AdminUser::factory()->create();

        $response = $this->actingAs($admin, 'admin')->get('/admin/dashboard');

        $response->assertOk();
    }

    public function test_admin_login_page_accessible_during_maintenance(): void
    {
        $response = $this->get('/admin/login');

        $response->assertOk();
    }
}
