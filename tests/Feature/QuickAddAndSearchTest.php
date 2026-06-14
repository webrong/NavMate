<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Site;
use App\Models\User;
use App\Services\UrlFetcherService;
use Illuminate\Cache\CacheManager;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class QuickAddAndSearchTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    private Category $category;

    protected function setUp(): void
    {
        parent::setUp();
        $this->category = Category::factory()->create();
    }

    public function test_quick_add_requires_authentication(): void
    {
        $this->postJson('/api/quick-add', [
            'url' => 'https://example.com',
            'category_id' => $this->category->id,
        ])->assertStatus(401);
    }

    public function test_quick_add_creates_private_site_and_sets_visitor_cookie(): void
    {
        $user = User::factory()->create();
        $url = $this->faker->url();

        $response = $this->actingAs($user)->postJson('/api/quick-add', [
            'url' => $url,
            'title' => 'My Quick Site',
            'category_id' => $this->category->id,
        ]);

        $response->assertOk();
        $response->assertJsonPath('success', true);
        $response->assertCookie('visitor_token');

        // quickAdd always creates PRIVATE sites (is_public=false) — only the
        // visitor (identified by the cookie token) should see them in search.
        $this->assertDatabaseHas('sites', [
            'url' => $url,
            'title' => 'My Quick Site',
            'is_public' => false,
        ]);
    }

    public function test_quick_add_validates_required_fields(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->postJson('/api/quick-add', []);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['url', 'category_id']);
    }

    public function test_quick_add_rejects_nonexistent_category(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->postJson('/api/quick-add', [
            'url' => $this->faker->url(),
            'category_id' => 999999,
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['category_id']);
    }

    public function test_click_increments_counter_and_logs(): void
    {
        $site = Site::factory()->forCategory($this->category->id)->create(['clicks' => 5]);

        $response = $this->postJson('/api/click', ['site_id' => $site->id]);

        $response->assertOk();
        $response->assertJsonPath('success', true);
        $this->assertSame(6, (int) $site->fresh()->clicks);
        $this->assertDatabaseHas('click_logs', ['site_id' => $site->id]);
    }

    public function test_click_deduplicates_within_one_hour(): void
    {
        $site = Site::factory()->forCategory($this->category->id)->create(['clicks' => 0]);

        // First click increments.
        $this->postJson('/api/click', ['site_id' => $site->id])->assertOk();
        // Second click from the same IP within the hour is ignored.
        $this->postJson('/api/click', ['site_id' => $site->id])->assertOk();

        $this->assertSame(1, (int) $site->fresh()->clicks);
        // Only one ClickLog row should exist.
        $this->assertSame(1, \DB::table('click_logs')->where('site_id', $site->id)->count());
    }

    public function test_click_rejects_nonexistent_site(): void
    {
        $this->postJson('/api/click', ['site_id' => 999999])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['site_id']);
    }

    public function test_click_does_not_block_after_cache_window_expires(): void
    {
        // Simulate the dedup window elapsing by clearing the cache between clicks.
        $site = Site::factory()->forCategory($this->category->id)->create(['clicks' => 0]);

        $this->postJson('/api/click', ['site_id' => $site->id])->assertOk();
        app(CacheManager::class)->flush();
        $this->postJson('/api/click', ['site_id' => $site->id])->assertOk();

        $this->assertSame(2, (int) $site->fresh()->clicks);
    }

    public function test_search_returns_only_public_sites(): void
    {
        $public = Site::factory()->forCategory($this->category->id)->create([
            'title' => 'Public GitHub', 'is_public' => true,
        ]);
        Site::factory()->private()->forCategory($this->category->id)->create([
            'title' => 'Private GitHub', 'is_public' => false,
        ]);

        $response = $this->getJson('/api/search?q=GitHub');

        $response->assertOk();
        $titles = collect($response->json())->pluck('title');
        $this->assertContains('Public GitHub', $titles);
        $this->assertNotContains('Private GitHub', $titles);
    }

    public function test_search_escapes_like_wildcards(): void
    {
        // Two public sites whose titles contain no literal "%".
        Site::factory()->forCategory($this->category->id)->create(['title' => 'Alpha']);
        Site::factory()->forCategory($this->category->id)->create(['title' => 'Beta']);

        // A bare "%" is a LIKE wildcard — without escaping it would match everything.
        $response = $this->getJson('/api/search?q=%');

        $response->assertOk();
        $this->assertEmpty($response->json());
    }

    public function test_search_truncates_overly_long_query(): void
    {
        // A 300-char query is truncated to 200 — it must not error or match unrelated rows.
        $longQuery = str_repeat('a', 300);
        Site::factory()->forCategory($this->category->id)->create(['title' => 'Alpha']);

        $response = $this->getJson('/api/search?q='.$longQuery);

        $response->assertOk();
        $this->assertEmpty($response->json());
    }

    public function test_fetch_url_delegates_to_service(): void
    {
        $this->mock(UrlFetcherService::class, function ($mock) {
            $mock->shouldReceive('fetch')
                ->once()
                ->andReturn(['title' => 'Mocked Title', 'favicon_url' => null]);
        });

        $response = $this->postJson('/api/fetch-url', ['url' => 'https://example.com']);

        $response->assertOk();
        $response->assertJson(['title' => 'Mocked Title', 'favicon_url' => null]);
    }

    public function test_fetch_url_validates_url_format(): void
    {
        $this->postJson('/api/fetch-url', ['url' => 'not-a-url'])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['url']);
    }
}
