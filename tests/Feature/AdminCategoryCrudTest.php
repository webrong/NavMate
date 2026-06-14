<?php

namespace Tests\Feature;

use App\Models\AdminUser;
use App\Models\Category;
use App\Models\Site;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminCategoryCrudTest extends TestCase
{
    use RefreshDatabase;

    private AdminUser $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = AdminUser::factory()->create();
    }

    public function test_guest_cannot_access_categories(): void
    {
        $this->getJson('/admin/api/categories')->assertStatus(401);
        $this->postJson('/admin/api/categories', [])->assertStatus(401);
    }

    public function test_data_returns_tree_structure(): void
    {
        $parent = Category::factory()->create(['name' => 'Tools']);
        $child = Category::factory()->childOf($parent->id)->create(['name' => 'DevTools']);

        $response = $this->actingAs($this->admin, 'admin')->getJson('/admin/api/categories');

        $response->assertOk();
        $response->assertJsonPath('code', 0);
        $response->assertJsonStructure(['data']);

        $data = $response->json('data');
        $this->assertCount(1, $data);
        $this->assertSame('Tools', $data[0]['name']);
        $this->assertCount(1, $data[0]['children']);
        $this->assertSame('DevTools', $data[0]['children'][0]['name']);
    }

    public function test_data_keyword_search_escapes_like_wildcards(): void
    {
        // Two categories with no literal underscore in their names.
        Category::factory()->create(['name' => 'Alpha']);
        Category::factory()->create(['name' => 'Beta']);

        // A bare "%" wildcard should NOT match everything.
        $response = $this->actingAs($this->admin, 'admin')
            ->getJson('/admin/api/categories?keyword=%');

        $data = $response->json('data');
        $this->assertEmpty($data);
    }

    public function test_store_creates_category(): void
    {
        $response = $this->actingAs($this->admin, 'admin')->postJson('/admin/api/categories', [
            'name' => 'New Category',
            'slug' => 'new-category',
            'description' => 'A test category',
            'is_active' => true,
        ]);

        $response->assertOk();
        $response->assertJsonPath('code', 0);
        $this->assertDatabaseHas('categories', ['slug' => 'new-category']);
    }

    public function test_store_creates_child_with_valid_parent(): void
    {
        $parent = Category::factory()->create();

        $response = $this->actingAs($this->admin, 'admin')->postJson('/admin/api/categories', [
            'name' => 'Child',
            'slug' => 'child',
            'parent_id' => $parent->id,
        ]);

        $response->assertOk();
        $this->assertDatabaseHas('categories', [
            'slug' => 'child',
            'parent_id' => $parent->id,
        ]);
    }

    public function test_store_rejects_duplicate_slug(): void
    {
        Category::factory()->create(['slug' => 'duplicate']);

        $response = $this->actingAs($this->admin, 'admin')->postJson('/admin/api/categories', [
            'name' => 'Another',
            'slug' => 'duplicate',
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['slug']);
    }

    public function test_store_validates_required_fields(): void
    {
        $response = $this->actingAs($this->admin, 'admin')->postJson('/admin/api/categories', []);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['name', 'slug']);
    }

    public function test_store_rejects_nonexistent_parent(): void
    {
        $response = $this->actingAs($this->admin, 'admin')->postJson('/admin/api/categories', [
            'name' => 'Orphan',
            'slug' => 'orphan',
            'parent_id' => 999999,
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['parent_id']);
    }

    public function test_update_cannot_set_self_as_parent(): void
    {
        $category = Category::factory()->create();

        $response = $this->actingAs($this->admin, 'admin')
            ->putJson("/admin/api/categories/{$category->id}", [
                'name' => $category->name,
                'slug' => $category->slug,
                'parent_id' => $category->id,
            ]);

        $response->assertStatus(422);
        $response->assertJson(['message' => '不能将自己设为父级分类']);
    }

    public function test_update_cannot_create_circular_reference(): void
    {
        // Build a chain: A -> B -> C (B's parent is A, C's parent is B)
        $a = Category::factory()->create();
        $b = Category::factory()->childOf($a->id)->create();
        $c = Category::factory()->childOf($b->id)->create();

        // Trying to make A's parent be C would create the cycle A -> C -> B -> A.
        $response = $this->actingAs($this->admin, 'admin')
            ->putJson("/admin/api/categories/{$a->id}", [
                'name' => $a->name,
                'slug' => $a->slug,
                'parent_id' => $c->id,
            ]);

        $response->assertStatus(422);
        $response->assertJson(['message' => '不能将子级分类设为父级，会产生循环引用']);
    }

    public function test_update_succeeds_for_normal_changes(): void
    {
        $category = Category::factory()->create(['name' => 'Old']);

        $response = $this->actingAs($this->admin, 'admin')
            ->putJson("/admin/api/categories/{$category->id}", [
                'name' => 'New',
                'slug' => $category->slug,
            ]);

        $response->assertOk();
        $this->assertDatabaseHas('categories', ['id' => $category->id, 'name' => 'New']);
    }

    public function test_destroy_refuses_category_with_sites(): void
    {
        $category = Category::factory()->create();
        Site::factory()->forCategory($category->id)->create();

        $response = $this->actingAs($this->admin, 'admin')
            ->deleteJson("/admin/api/categories/{$category->id}");

        $response->assertStatus(422);
        $response->assertJsonPath('message', fn ($m) => str_contains((string) $m, '1 个站点'));
        $this->assertDatabaseHas('categories', ['id' => $category->id]);
    }

    public function test_destroy_refuses_category_with_children(): void
    {
        $parent = Category::factory()->create();
        Category::factory()->childOf($parent->id)->create();

        $response = $this->actingAs($this->admin, 'admin')
            ->deleteJson("/admin/api/categories/{$parent->id}");

        $response->assertStatus(422);
        $response->assertJsonPath('message', fn ($m) => str_contains((string) $m, '1 个子分类'));
    }

    public function test_destroy_deletes_empty_category(): void
    {
        $category = Category::factory()->create();

        $response = $this->actingAs($this->admin, 'admin')
            ->deleteJson("/admin/api/categories/{$category->id}");

        $response->assertOk();
        $response->assertJsonPath('code', 0);
        $this->assertDatabaseMissing('categories', ['id' => $category->id]);
    }

    public function test_tree_returns_active_categories(): void
    {
        Category::factory()->create(['name' => 'Active', 'is_active' => true]);
        Category::factory()->inactive()->create(['name' => 'Inactive']);

        $response = $this->actingAs($this->admin, 'admin')
            ->getJson('/admin/api/categories/tree');

        $response->assertOk();
        $names = collect($response->json('data'))->pluck('name');
        $this->assertContains('Active', $names);
        $this->assertNotContains('Inactive', $names);
    }
}
