<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Site;
use App\Models\User;
use App\Models\UserLink;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SiteControllerTest extends TestCase
{
    use RefreshDatabase;

    private Category $category;

    protected function setUp(): void
    {
        parent::setUp();
        $this->category = Category::create([
            'name' => 'Test Category',
            'slug' => 'test',
            'sort_order' => 0,
            'is_active' => true,
        ]);
    }

    public function test_search_returns_public_sites(): void
    {
        Site::create([
            'category_id' => $this->category->id,
            'title' => 'GitHub',
            'url' => 'https://github.com',
            'is_public' => true,
            'is_active' => true,
        ]);

        Site::create([
            'category_id' => $this->category->id,
            'title' => 'Private Site',
            'url' => 'https://private.example.com',
            'is_public' => false,
            'is_active' => true,
        ]);

        $response = $this->getJson('/api/search?q=GitHub');

        $response->assertOk();
        $response->assertJsonCount(1);
        $response->assertJsonFragment(['title' => 'GitHub']);
    }

    public function test_search_excludes_inactive_sites(): void
    {
        Site::create([
            'category_id' => $this->category->id,
            'title' => 'Inactive Site',
            'url' => 'https://inactive.example.com',
            'is_public' => true,
            'is_active' => false,
        ]);

        $response = $this->getJson('/api/search?q=Inactive');

        $response->assertOk();
        $response->assertJsonCount(0);
    }

    public function test_search_limits_query_length(): void
    {
        $longQuery = str_repeat('a', 300);
        $response = $this->getJson('/api/search?q=' . $longQuery);

        $response->assertOk();
    }

    public function test_click_increments_counter(): void
    {
        $site = Site::create([
            'category_id' => $this->category->id,
            'title' => 'Clickable',
            'url' => 'https://example.com',
            'is_public' => true,
            'is_active' => true,
            'clicks' => 0,
        ]);

        $response = $this->postJson('/api/click', ['site_id' => $site->id]);

        $response->assertOk();
        $this->assertDatabaseHas('sites', ['id' => $site->id, 'clicks' => 1]);
    }

    public function test_click_creates_log_entry(): void
    {
        $site = Site::create([
            'category_id' => $this->category->id,
            'title' => 'Logging',
            'url' => 'https://example.com',
            'is_public' => true,
            'is_active' => true,
        ]);

        $this->postJson('/api/click', ['site_id' => $site->id]);

        $this->assertDatabaseHas('click_logs', ['site_id' => $site->id]);
    }

    public function test_click_fails_for_nonexistent_site(): void
    {
        $response = $this->postJson('/api/click', ['site_id' => 9999]);

        $response->assertStatus(422); // validation fails
    }

    public function test_categories_api_returns_data(): void
    {
        $response = $this->getJson('/api/categories');

        $response->assertOk();
    }
}
