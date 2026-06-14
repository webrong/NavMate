<?php

namespace Tests\Feature;

use App\Models\AdminUser;
use App\Models\Setting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Mail;
use Symfony\Component\Mailer\SentMessage;
use Tests\TestCase;

class SettingsTest extends TestCase
{
    use RefreshDatabase;

    private AdminUser $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = AdminUser::factory()->create();
        // The Setting model keeps an in-process cache; make sure tests start clean.
        Setting::flushCache();
    }

    public function test_public_settings_returns_only_safe_keys(): void
    {
        // Seed both a public and a private key.
        Setting::set('site_name', 'My Nav');
        Setting::set('mail_password', Crypt::encryptString('super-secret'));
        Setting::set('mail_host', 'smtp.private.internal');
        Setting::set('mail_username', 'smtp-user');
        Setting::flushCache();

        $response = $this->getJson('/api/settings');

        $response->assertOk();
        $response->assertJsonPath('site_name', 'My Nav');

        // The most sensitive keys must NEVER leak to the public endpoint.
        $body = $response->json();
        $this->assertArrayNotHasKey('mail_password', $body);
        $this->assertArrayNotHasKey('mail_host', $body);
        $this->assertArrayNotHasKey('mail_username', $body);
        $this->assertArrayNotHasKey('site_keywords', $body);
    }

    public function test_public_settings_coerces_booleans_to_real_bool(): void
    {
        Setting::set('enable_register', '1');
        Setting::set('maintenance_mode', '0');
        Setting::flushCache();

        $response = $this->getJson('/api/settings');

        $response->assertOk();
        // assertJsonPath checks strict equality for scalars — string '1' would fail.
        $response->assertJsonPath('enable_register', true);
        $response->assertJsonPath('maintenance_mode', false);
    }

    public function test_public_settings_falls_back_to_app_name(): void
    {
        // No site_name in the database → should fall back to config('app.name').
        $response = $this->getJson('/api/settings');

        $response->assertOk();
        $this->assertNotEmpty($response->json('site_name'));
    }

    public function test_admin_settings_requires_authentication(): void
    {
        $this->getJson('/admin/api/settings')->assertStatus(401);
    }

    public function test_admin_settings_returns_full_settings_with_decrypted_mail_password(): void
    {
        Setting::set('mail_password', Crypt::encryptString('decrypted-secret'));
        Setting::flushCache();

        $response = $this->actingAs($this->admin, 'admin')->getJson('/admin/api/settings');

        $response->assertOk();
        $response->assertJsonPath('code', 0);
        // The password should be decrypted back to plaintext for the admin UI.
        $this->assertSame('decrypted-secret', $response->json('data.mail_password'));
    }

    public function test_admin_settings_handles_corrupt_mail_password_gracefully(): void
    {
        Setting::set('mail_password', 'not-valid-ciphertext');
        Setting::flushCache();

        $response = $this->actingAs($this->admin, 'admin')->getJson('/admin/api/settings');

        $response->assertOk();
        // Corrupt ciphertext should yield an empty string, not a 500.
        $this->assertSame('', $response->json('data.mail_password'));
    }

    public function test_admin_update_persists_settings(): void
    {
        $response = $this->actingAs($this->admin, 'admin')->putJson('/admin/api/settings', [
            'site_name' => 'Updated Name',
            'site_description' => 'A description',
        ]);

        $response->assertOk();
        $response->assertJsonPath('code', 0);
        $this->assertDatabaseHas('settings', ['key' => 'site_name', 'value' => 'Updated Name']);
    }

    public function test_admin_update_encrypts_mail_password(): void
    {
        $this->actingAs($this->admin, 'admin')->putJson('/admin/api/settings', [
            'mail_password' => 'plain-password',
        ])->assertOk();

        // The stored value must not be the plaintext.
        $stored = Setting::where('key', 'mail_password')->value('value');
        $this->assertNotSame('plain-password', $stored);
        $this->assertSame('plain-password', Crypt::decryptString($stored));
    }

    public function test_admin_update_normalizes_booleans(): void
    {
        $this->actingAs($this->admin, 'admin')->putJson('/admin/api/settings', [
            'enable_register' => true,
            'maintenance_mode' => false,
        ])->assertOk();

        $this->assertDatabaseHas('settings', ['key' => 'enable_register', 'value' => '1']);
        $this->assertDatabaseHas('settings', ['key' => 'maintenance_mode', 'value' => '0']);
    }

    public function test_admin_update_validates_email_encryption_value(): void
    {
        $response = $this->actingAs($this->admin, 'admin')->putJson('/admin/api/settings', [
            'mail_encryption' => 'invalid',
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['mail_encryption']);
    }

    public function test_test_email_requires_authentication(): void
    {
        $this->postJson('/admin/api/settings/test-email', [
            'to' => 'someone@example.com',
        ])->assertStatus(401);
    }

    public function test_test_email_validates_required_fields(): void
    {
        $response = $this->actingAs($this->admin, 'admin')
            ->postJson('/admin/api/settings/test-email', []);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors([
            'to', 'mail_host', 'mail_port', 'mail_encryption',
            'mail_username', 'mail_password', 'mail_from_address',
        ]);
    }

    public function test_test_email_rejects_internal_smtp_host(): void
    {
        // 'localhost' resolves to 127.0.0.1 on every host — a textbook internal IP.
        $response = $this->actingAs($this->admin, 'admin')
            ->postJson('/admin/api/settings/test-email', [
                'to' => 'someone@example.com',
                'mail_host' => 'localhost',
                'mail_port' => 25,
                'mail_encryption' => 'null',
                'mail_username' => 'user',
                'mail_password' => 'pass',
                'mail_from_address' => 'from@example.com',
            ]);

        $response->assertStatus(422);
        $response->assertJsonPath('code', 1);
    }

    public function test_test_email_sends_on_valid_external_smtp(): void
    {
        // Use Mail::shouldReceive instead of Mail::fake(): the controller calls
        // Mail::raw(...), and MailFake's assertSentCount only counts Mailable
        // objects (not raw mail), whereas shouldReceive verifies the raw call
        // was actually dispatched before the fake short-circuits the SMTP send.
        Mail::shouldReceive('raw')
            ->once()
            ->andReturn(SentMessage::class);

        // Use a real public hostname so gethostbyname returns a public IP and
        // the SSRF guard passes. The actual SMTP connection never happens —
        // the mail mock intercepts Mail::raw() before any network call.
        $response = $this->withoutExceptionHandling()->actingAs($this->admin, 'admin')
            ->postJson('/admin/api/settings/test-email', [
                'to' => 'someone@example.com',
                'mail_host' => 'smtp.gmail.com',
                'mail_port' => 465,
                'mail_encryption' => 'ssl',
                'mail_username' => 'user@gmail.com',
                'mail_password' => 'pass',
                'mail_from_address' => 'from@example.com',
            ]);

        $response->assertOk();
        $response->assertJsonPath('code', 0);
    }
}
