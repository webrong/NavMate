<?php

namespace Tests\Feature;

use App\Models\AdminUser;
use App\Models\UpdateLog;
use App\Services\UpdateService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class UpdateTest extends TestCase
{
    use RefreshDatabase;

    private AdminUser $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = AdminUser::factory()->create();
    }

    /**
     * Rebind a fresh UpdateService so it picks up the current config
     * (the constructor caches config('services.update.github_repo') at build time).
     */
    private function refreshUpdater(string $repo = 'webrong/NavMate'): void
    {
        config(['services.update.github_repo' => $repo]);
        $this->app->forgetInstance(UpdateService::class);
    }

    public function test_default_update_source_is_official_repo(): void
    {
        // Sanity check: out of the box, every user gets the official repo
        // without touching .env — this is the regression we want to guard.
        $this->assertSame('webrong/NavMate', config('services.update.github_repo'));
    }

    public function test_check_update_endpoint_requires_admin_auth(): void
    {
        $this->getJson('/admin/api/system/check-update')->assertStatus(401);
    }

    public function test_check_update_detects_new_version(): void
    {
        $this->refreshUpdater();

        Http::fake([
            'api.github.com/repos/webrong/NavMate/releases/latest' => Http::response([
                'tag_name' => 'v2.0.0',
                'body' => '## Breaking changes'."\n".'- Rewrite frontend',
                'html_url' => 'https://github.com/webrong/NavMate/releases/tag/v2.0.0',
                'published_at' => '2026-06-01T00:00:00Z',
                'assets' => [
                    ['name' => 'navmate-v2.0.0.zip', 'browser_download_url' => 'https://github.com/webrong/NavMate/releases/download/v2.0.0/navmate-v2.0.0.zip'],
                ],
            ], 200, ['Accept' => 'application/vnd.github+json']),
        ]);

        $response = $this->actingAs($this->admin, 'admin')
            ->getJson('/admin/api/system/check-update');

        $response->assertOk();
        $response->assertJsonPath('has_update', true);
        $response->assertJsonPath('latest_version', '2.0.0');
        // current_version comes from config('app.version'); assert against the
        // live value rather than hard-coding, so this test survives releases.
        $response->assertJsonPath('current_version', config('app.version'));
        $response->assertJsonPath('download_url', 'https://github.com/webrong/NavMate/releases/download/v2.0.0/navmate-v2.0.0.zip');
        $response->assertJsonPath('changelog', "## Breaking changes\n- Rewrite frontend");
    }

    public function test_check_update_reports_already_latest(): void
    {
        $this->refreshUpdater();

        // Current installed version is 1.0.0 (from setUp). Latest release is also 1.0.0.
        Http::fake([
            'api.github.com/repos/webrong/NavMate/releases/latest' => Http::response([
                'tag_name' => 'v1.0.0',
                'body' => 'Initial release',
                'html_url' => 'https://github.com/webrong/NavMate/releases/tag/v1.0.0',
                'published_at' => '2026-04-01T00:00:00Z',
                'assets' => [],
            ], 200),
        ]);

        $response = $this->actingAs($this->admin, 'admin')
            ->getJson('/admin/api/system/check-update');

        $response->assertOk();
        $response->assertJsonPath('has_update', false);
        $response->assertJsonPath('latest_version', '1.0.0');
        // No download_url when there are no assets
        $response->assertJsonPath('download_url', null);
    }

    public function test_check_update_handles_github_rate_limit(): void
    {
        $this->refreshUpdater();

        // GitHub returns 403 with rate-limit headers when the IP exhausts its quota.
        Http::fake([
            'api.github.com/repos/webrong/NavMate/releases/latest' => Http::response(
                ['message' => 'API rate limit exceeded'],
                403,
                [
                    'X-RateLimit-Remaining' => '0',
                    'X-RateLimit-Reset' => (string) (time() + 3600),
                ]
            ),
        ]);

        $response = $this->actingAs($this->admin, 'admin')
            ->getJson('/admin/api/system/check-update');

        $response->assertOk();
        $response->assertJsonPath('has_update', false);
        // The error message should mention the rate limit, not a generic failure.
        $this->assertStringContainsString('请求次数已用完', $response->json('error'));
    }

    public function test_check_update_handles_network_failure_gracefully(): void
    {
        $this->refreshUpdater();

        Http::fake([
            'api.github.com/repos/webrong/NavMate/releases/latest' => Http::response('', 500),
        ]);

        $response = $this->actingAs($this->admin, 'admin')
            ->getJson('/admin/api/system/check-update');

        $response->assertOk();
        $response->assertJsonPath('has_update', false);
        // 5xx responses are retried then surfaced as a transient-availability
        // message (friendlier than the raw "GitHub API 请求失败: 500").
        $this->assertStringContainsString('GitHub 服务暂时不可用', $response->json('error'));
    }

    public function test_check_update_returns_error_when_repo_not_configured(): void
    {
        // Edge case: a user explicitly blanks out the repo in .env.
        $this->refreshUpdater('');

        $response = $this->actingAs($this->admin, 'admin')
            ->getJson('/admin/api/system/check-update');

        $response->assertOk();
        $response->assertJsonPath('has_update', false);
        $this->assertStringContainsString('未配置更新源', $response->json('error'));

        // No HTTP call should be made in this branch.
        Http::assertNothingSent();
    }

    public function test_update_logs_are_listed_for_admin(): void
    {
        // Seed a couple of log rows so the endpoint has something to return.
        UpdateLog::create([
            'from_version' => '1.0.0',
            'to_version' => '1.1.0',
            'status' => 'success',
            'log' => '[1/9] 检查更新...',
        ]);
        UpdateLog::create([
            'from_version' => '1.1.0',
            'to_version' => '1.2.0',
            'status' => 'failed',
            'log' => '下载失败',
        ]);

        $response = $this->actingAs($this->admin, 'admin')
            ->getJson('/admin/api/system/update-logs');

        $response->assertOk();
        // Newest first
        $response->assertJsonCount(2);
        $this->assertSame('1.2.0', $response->json('0.to_version'));
        $this->assertSame('failed', $response->json('0.status'));
    }

    public function test_update_logs_require_admin_auth(): void
    {
        $this->getJson('/admin/api/system/update-logs')->assertStatus(401);
    }
}
