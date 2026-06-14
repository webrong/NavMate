<?php

namespace Tests\Feature;

use App\Models\AdminUser;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\RateLimiter;
use Tests\TestCase;

class AdminAuthTest extends TestCase
{
    use RefreshDatabase;

    private AdminUser $admin;

    protected function setUp(): void
    {
        parent::setUp();
        // The admin_users migration auto-creates one; we use the factory for a known password.
        $this->admin = AdminUser::factory()->create([
            'email' => 'admin@example.com',
            'password' => 'correct-password',
        ]);
    }

    protected function tearDown(): void
    {
        // Throttle keys persist across tests within a process; clear them so the
        // next test class starts clean.
        RateLimiter::clear('admin_login:admin@example.com:127.0.0.1');
        parent::tearDown();
    }

    public function test_login_succeeds_with_correct_credentials(): void
    {
        $response = $this->postJson('/admin/login', [
            'email' => 'admin@example.com',
            'password' => 'correct-password',
        ]);

        $response->assertOk();
        $response->assertJsonStructure(['message', 'user' => ['id', 'name', 'email']]);
        $response->assertJsonPath('user.email', 'admin@example.com');
    }

    public function test_login_fails_with_wrong_password(): void
    {
        $response = $this->postJson('/admin/login', [
            'email' => 'admin@example.com',
            'password' => 'wrong-password',
        ]);

        $response->assertStatus(422);
        $response->assertJson(['message' => '账号或密码错误']);
    }

    public function test_login_validates_email_format(): void
    {
        $response = $this->postJson('/admin/login', [
            'email' => 'not-an-email',
            'password' => 'whatever',
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['email']);
    }

    public function test_login_throttles_after_five_failed_attempts(): void
    {
        // Five failures should be allowed; the sixth should be blocked.
        for ($i = 0; $i < 5; $i++) {
            $this->postJson('/admin/login', [
                'email' => 'admin@example.com',
                'password' => 'wrong',
            ])->assertStatus(422);
        }

        $this->postJson('/admin/login', [
            'email' => 'admin@example.com',
            'password' => 'wrong',
        ])->assertStatus(429);
    }

    public function test_successful_login_clears_throttle_counter(): void
    {
        // Use a separate admin so this test's throttle state is isolated from
        // the throttle-after-five-failures test (throttle keys are per-email+IP
        // and persist in the array cache within the test process).
        $admin = AdminUser::factory()->create([
            'email' => 'clear-test@example.com',
            'password' => 'correct-password',
        ]);

        // Burn two attempts, then succeed (which clears the counter).
        for ($i = 0; $i < 2; $i++) {
            $this->postJson('/admin/login', [
                'email' => 'clear-test@example.com',
                'password' => 'wrong',
            ])->assertStatus(422);
        }

        $this->postJson('/admin/login', [
            'email' => 'clear-test@example.com',
            'password' => 'correct-password',
        ])->assertOk();

        // After a successful login, the throttle counter is cleared. A fresh
        // wrong attempt should start from zero and be rejected with 422, not
        // throttled with 429.
        $this->postJson('/admin/login', [
            'email' => 'clear-test@example.com',
            'password' => 'wrong',
        ])->assertStatus(422);
    }

    public function test_me_returns_admin_data_when_authenticated(): void
    {
        $response = $this->actingAs($this->admin, 'admin')->getJson('/admin/api/me');

        $response->assertOk();
        $response->assertJsonPath('id', $this->admin->id);
        $response->assertJsonPath('email', 'admin@example.com');
    }

    public function test_me_is_unauthorized_for_guests(): void
    {
        // The route uses auth:admin middleware; a guest gets 401 (or redirect).
        $response = $this->getJson('/admin/api/me');

        $response->assertStatus(401);
    }

    public function test_logout_requires_admin_authentication(): void
    {
        $response = $this->postJson('/admin/logout');

        $response->assertStatus(401);
    }

    public function test_logout_succeeds_when_authenticated(): void
    {
        $response = $this->actingAs($this->admin, 'admin')->postJson('/admin/logout');

        $response->assertOk();
        $response->assertJson(['message' => '已退出登录']);
    }

    public function test_admin_routes_block_regular_users(): void
    {
        // A regular web user must not be able to access admin routes.
        $user = User::factory()->create();

        $response = $this->actingAs($user)->getJson('/admin/api/me');

        $response->assertStatus(401);
    }
}
