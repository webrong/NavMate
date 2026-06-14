<?php

namespace Tests\Feature;

use App\Services\InstallerService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\RateLimiter;
use Tests\TestCase;

class InstallControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        // Tests start from a clean slate — the install marker must not exist,
        // otherwise the controller's guardAgainstInstalled() aborts with 403.
        $this->withoutInstalledMarker();
        $this->mockInstallerService();
    }

    protected function tearDown(): void
    {
        // Install execute is throttled at 3,1 — clear between test classes so the
        // next class starts with a fresh counter.
        RateLimiter::clear('install.execute:127.0.0.1');
        // Make sure we never leave the marker behind for other test classes.
        $this->withoutInstalledMarker();
        parent::tearDown();
    }

    /**
     * Bind a fake InstallerService so no real file writes / migrations happen.
     *
     * Note: InstallerService::isInstalled() is a static method that checks a real
     * file — it cannot be mocked. We control it by creating/deleting the marker
     * file directly (see withInstalledMarker / withoutInstalledMarker).
     */
    private function mockInstallerService(array $stubs = []): void
    {
        $defaults = array_merge([
            'checkEnvironment' => ['checks' => [], 'all_pass' => true],
            'testDatabase' => ['success' => true, 'message' => '数据库连接成功'],
            'testRedis' => ['success' => true, 'message' => 'Redis 连接成功'],
            'install' => ['success' => true, 'message' => '安装成功！'],
        ], $stubs);

        $this->mock(InstallerService::class, function ($mock) use ($defaults) {
            foreach ($defaults as $method => $return) {
                $mock->shouldReceive($method)->andReturn($return);
            }
        });
    }

    protected function withInstalledMarker(): void
    {
        file_put_contents(storage_path('app/installed'), '{"installed_at":"now"}');
    }

    protected function withoutInstalledMarker(): void
    {
        @unlink(storage_path('app/installed'));
    }

    public function test_install_page_renders_when_not_installed(): void
    {
        $response = $this->get('/install');

        $response->assertOk();
        $response->assertViewIs('install');
    }

    public function test_install_page_is_blocked_when_already_installed(): void
    {
        $this->withInstalledMarker();

        $response = $this->get('/install');

        $response->assertStatus(403);
    }

    public function test_check_environment_returns_check_results(): void
    {
        $response = $this->postJson('/install/check-environment');

        $response->assertOk();
        $response->assertJsonStructure(['checks', 'all_pass']);
    }

    public function test_test_database_returns_200_on_success(): void
    {
        $response = $this->postJson('/install/test-database', [
            'db_host' => '127.0.0.1',
            'db_port' => 3306,
            'db_database' => 'navmate',
            'db_username' => 'root',
            'db_password' => 'secret',
        ]);

        $response->assertOk();
        $response->assertJson(['success' => true]);
    }

    public function test_test_database_returns_422_on_failure(): void
    {
        $this->mockInstallerService([
            'testDatabase' => ['success' => false, 'message' => '连接失败'],
        ]);

        $response = $this->postJson('/install/test-database', [
            'db_host' => '127.0.0.1',
            'db_port' => 3306,
            'db_username' => 'root',
        ]);

        $response->assertStatus(422);
        $response->assertJson(['success' => false]);
    }

    public function test_test_database_validates_required_fields(): void
    {
        $response = $this->postJson('/install/test-database', [
            'db_host' => '',
            'db_port' => 'not-an-integer',
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['db_host', 'db_port']);
    }

    public function test_test_redis_returns_422_on_failure(): void
    {
        $this->mockInstallerService([
            'testRedis' => ['success' => false, 'message' => 'Redis 扩展未安装'],
        ]);

        $response = $this->postJson('/install/test-redis', [
            'redis_host' => '127.0.0.1',
            'redis_port' => 6379,
        ]);

        $response->assertStatus(422);
        $response->assertJson(['success' => false]);
    }

    public function test_execute_returns_200_on_successful_install(): void
    {
        $response = $this->postJson('/install/execute', $this->validInstallPayload());

        $response->assertOk();
        $response->assertJson(['success' => true]);
    }

    public function test_execute_returns_500_on_install_failure(): void
    {
        $this->mockInstallerService([
            'install' => ['success' => false, 'message' => '安装失败: something went wrong'],
        ]);

        $response = $this->postJson('/install/execute', $this->validInstallPayload());

        $response->assertStatus(500);
        $response->assertJson(['success' => false]);
    }

    public function test_execute_validates_required_fields(): void
    {
        $response = $this->postJson('/install/execute', []);

        $response->assertStatus(422);
        // Spot-check the most important required fields.
        $response->assertJsonValidationErrors([
            'db_host', 'db_database', 'app_name', 'app_url',
            'admin_name', 'admin_email', 'admin_password', 'cache_store',
        ]);
    }

    public function test_execute_rejects_invalid_cache_store(): void
    {
        $payload = $this->validInstallPayload();
        $payload['cache_store'] = 'memcached';

        $response = $this->postJson('/install/execute', $payload);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['cache_store']);
    }

    public function test_execute_rejects_short_admin_password(): void
    {
        $payload = $this->validInstallPayload();
        $payload['admin_password'] = 'short';

        $response = $this->postJson('/install/execute', $payload);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['admin_password']);
    }

    public function test_execute_is_throttled(): void
    {
        // The route is throttle:3,1 — three requests are allowed, the fourth is blocked.
        for ($i = 0; $i < 3; $i++) {
            $this->postJson('/install/execute', $this->validInstallPayload())
                ->assertOk();
        }

        $this->postJson('/install/execute', $this->validInstallPayload())
            ->assertStatus(429);
    }

    private function validInstallPayload(): array
    {
        return [
            'db_host' => '127.0.0.1',
            'db_port' => 3306,
            'db_database' => 'navmate',
            'db_username' => 'root',
            'db_password' => 'secret',
            'cache_store' => 'database',
            'app_name' => 'NavMate',
            'app_url' => 'https://nav.example.com',
            'admin_name' => 'Admin',
            'admin_email' => 'admin@example.com',
            'admin_password' => 'secure-password',
            'skip_mail' => true,
        ];
    }
}
