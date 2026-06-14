<?php

namespace Tests\Feature;

use App\Models\AdminUser;
use App\Models\Category;
use App\Models\Site;
use App\Services\UrlFetcherService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class AdminSiteCrudTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    private AdminUser $admin;

    private Category $category;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = AdminUser::factory()->create();
        $this->category = Category::factory()->create();
    }

    public function test_guest_cannot_access_sites(): void
    {
        $this->getJson('/admin/api/sites')->assertStatus(401);
        $this->postJson('/admin/api/sites', [])->assertStatus(401);
    }

    public function test_data_returns_paginated_sites(): void
    {
        Site::factory()->count(20)->forCategory($this->category->id)->create();

        $response = $this->actingAs($this->admin, 'admin')->getJson('/admin/api/sites');

        $response->assertOk();
        // Default page size is 15 — confirm pagination is applied.
        $this->assertCount(15, $response->json('data'));
        $this->assertEquals(20, $response->json('total'));
    }

    public function test_data_respects_limit_parameter(): void
    {
        Site::factory()->count(10)->forCategory($this->category->id)->create();

        $response = $this->actingAs($this->admin, 'admin')
            ->getJson('/admin/api/sites?limit=5');

        $this->assertCount(5, $response->json('data'));
    }

    public function test_data_filters_by_keyword(): void
    {
        Site::factory()->forCategory($this->category->id)->create(['title' => 'GitHub']);
        Site::factory()->forCategory($this->category->id)->create(['title' => 'GitLab']);
        Site::factory()->forCategory($this->category->id)->create(['title' => 'Totally Unrelated']);

        $response = $this->actingAs($this->admin, 'admin')
            ->getJson('/admin/api/sites?keyword=Git');

        $titles = collect($response->json('data'))->pluck('title');
        $this->assertContains('GitHub', $titles);
        $this->assertContains('GitLab', $titles);
        $this->assertNotContains('Totally Unrelated', $titles);
    }

    public function test_data_filters_by_category(): void
    {
        $otherCategory = Category::factory()->create();
        Site::factory()->forCategory($this->category->id)->create(['title' => 'In Cat']);
        Site::factory()->forCategory($otherCategory->id)->create(['title' => 'Other Cat']);

        $response = $this->actingAs($this->admin, 'admin')
            ->getJson("/admin/api/sites?category_id={$this->category->id}");

        $titles = collect($response->json('data'))->pluck('title');
        $this->assertContains('In Cat', $titles);
        $this->assertNotContains('Other Cat', $titles);
    }

    public function test_data_filters_by_is_public(): void
    {
        Site::factory()->forCategory($this->category->id)->create(['title' => 'Public', 'is_public' => true]);
        Site::factory()->private()->forCategory($this->category->id)->create(['title' => 'Private']);

        $response = $this->actingAs($this->admin, 'admin')
            ->getJson('/admin/api/sites?is_public=0');

        $titles = collect($response->json('data'))->pluck('title');
        $this->assertContains('Private', $titles);
        $this->assertNotContains('Public', $titles);
    }

    public function test_store_creates_site_with_defaults(): void
    {
        $url = $this->faker->url();

        $response = $this->actingAs($this->admin, 'admin')->postJson('/admin/api/sites', [
            'category_id' => $this->category->id,
            'title' => 'New Site',
            'url' => $url,
        ]);

        $response->assertOk();
        $response->assertJsonPath('code', 0);
        $this->assertDatabaseHas('sites', [
            'url' => $url,
            'title' => 'New Site',
            'is_public' => true,
            'is_active' => true,
        ]);
    }

    public function test_store_rejects_duplicate_url(): void
    {
        $existing = Site::factory()->forCategory($this->category->id)->create();

        $response = $this->actingAs($this->admin, 'admin')->postJson('/admin/api/sites', [
            'category_id' => $this->category->id,
            'title' => 'Dup',
            'url' => $existing->url,
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['url']);
    }

    public function test_store_rejects_invalid_category(): void
    {
        $response = $this->actingAs($this->admin, 'admin')->postJson('/admin/api/sites', [
            'category_id' => 999999,
            'title' => 'Site',
            'url' => $this->faker->url(),
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['category_id']);
    }

    public function test_store_rejects_invalid_url(): void
    {
        $response = $this->actingAs($this->admin, 'admin')->postJson('/admin/api/sites', [
            'category_id' => $this->category->id,
            'title' => 'Site',
            'url' => 'not-a-url',
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['url']);
    }

    public function test_update_modifies_site(): void
    {
        $site = Site::factory()->forCategory($this->category->id)->create(['title' => 'Old']);

        $response = $this->actingAs($this->admin, 'admin')
            ->putJson("/admin/api/sites/{$site->id}", [
                'category_id' => $this->category->id,
                'title' => 'New Title',
                'url' => $site->url,
            ]);

        $response->assertOk();
        $this->assertDatabaseHas('sites', ['id' => $site->id, 'title' => 'New Title']);
    }

    public function test_update_returns_404_for_missing_site(): void
    {
        $response = $this->actingAs($this->admin, 'admin')
            ->putJson('/admin/api/sites/999999', [
                'category_id' => $this->category->id,
                'title' => 'X',
                'url' => $this->faker->url(),
            ]);

        $response->assertStatus(404);
    }

    public function test_destroy_deletes_site(): void
    {
        $site = Site::factory()->forCategory($this->category->id)->create();

        $response = $this->actingAs($this->admin, 'admin')
            ->deleteJson("/admin/api/sites/{$site->id}");

        $response->assertOk();
        $this->assertDatabaseMissing('sites', ['id' => $site->id]);
    }

    public function test_fetch_url_delegates_to_service(): void
    {
        $this->mock(UrlFetcherService::class, function ($mock) {
            $mock->shouldReceive('fetch')
                ->once()
                ->with('https://example.com')
                ->andReturn(['title' => 'Example Domain', 'favicon_url' => 'https://example.com/favicon.ico']);
        });

        $response = $this->actingAs($this->admin, 'admin')
            ->postJson('/admin/api/sites/fetch-url', [
                'url' => 'https://example.com',
            ]);

        $response->assertOk();
        $response->assertJson([
            'title' => 'Example Domain',
            'favicon_url' => 'https://example.com/favicon.ico',
        ]);
    }

    public function test_categories_dropdown_returns_active_only(): void
    {
        Category::factory()->create(['name' => 'Active', 'is_active' => true]);
        Category::factory()->inactive()->create(['name' => 'Inactive']);

        $response = $this->actingAs($this->admin, 'admin')
            ->getJson('/admin/api/sites/categories');

        $response->assertOk();
        $names = collect($response->json('data'))->pluck('name');
        $this->assertContains('Active', $names);
        $this->assertNotContains('Inactive', $names);
    }
}
