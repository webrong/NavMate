<?php

namespace Tests\Unit;

use App\Models\Category;
use App\Models\Site;
use App\Services\CategoryTreeService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CategoryTreeServiceTest extends TestCase
{
    use RefreshDatabase;

    private CategoryTreeService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(CategoryTreeService::class);
    }

    public function test_empty_database_returns_empty_collection(): void
    {
        $tree = $this->service->getPublicTree();

        $this->assertTrue($tree->isEmpty());
    }

    public function test_parent_without_sites_or_children_is_filtered_out(): void
    {
        // An active parent with nothing attached should be excluded.
        Category::factory()->create();

        $tree = $this->service->getPublicTree();

        $this->assertTrue($tree->isEmpty());
    }

    public function test_parent_with_sites_is_included(): void
    {
        $category = Category::factory()->create();
        Site::factory()->forCategory($category->id)->create();

        $tree = $this->service->getPublicTree();

        $this->assertCount(1, $tree);
        $this->assertSame($category->id, $tree->first()->id);
        $this->assertCount(1, $tree->first()->sites);
    }

    public function test_parent_with_only_children_is_included(): void
    {
        $parent = Category::factory()->create();
        // Child itself needs a site so it appears in the active categories list with sites
        $child = Category::factory()->childOf($parent->id)->create();
        Site::factory()->forCategory($child->id)->create();

        $tree = $this->service->getPublicTree();

        $this->assertCount(1, $tree);
        $this->assertSame($parent->id, $tree->first()->id);
        $this->assertCount(1, $tree->first()->children);
        $this->assertSame($child->id, $tree->first()->children->first()->id);
    }

    public function test_inactive_category_is_excluded(): void
    {
        $category = Category::factory()->inactive()->create();
        Site::factory()->forCategory($category->id)->create();

        $tree = $this->service->getPublicTree();

        $this->assertTrue($tree->isEmpty());
    }

    public function test_inactive_site_is_excluded_from_sites_list(): void
    {
        $category = Category::factory()->create();
        Site::factory()->forCategory($category->id)->create();
        Site::factory()->inactive()->forCategory($category->id)->create();

        $tree = $this->service->getPublicTree();

        $this->assertCount(1, $tree);
        // Only the active site should be attached
        $this->assertCount(1, $tree->first()->sites);
    }

    public function test_private_site_is_excluded_from_sites_list(): void
    {
        $category = Category::factory()->create();
        Site::factory()->forCategory($category->id)->create();
        Site::factory()->private()->forCategory($category->id)->create();

        $tree = $this->service->getPublicTree();

        $this->assertCount(1, $tree->first()->sites);
    }

    public function test_children_are_correctly_nested_under_their_parent(): void
    {
        $parentA = Category::factory()->create();
        $parentB = Category::factory()->create();

        $childA1 = Category::factory()->childOf($parentA->id)->create();
        $childA2 = Category::factory()->childOf($parentA->id)->create();
        $childB1 = Category::factory()->childOf($parentB->id)->create();

        // Each child needs a site so the parent is not filtered out
        Site::factory()->forCategory($childA1->id)->create();
        Site::factory()->forCategory($childA2->id)->create();
        Site::factory()->forCategory($childB1->id)->create();

        $tree = $this->service->getPublicTree();

        $this->assertCount(2, $tree);

        $foundA = $tree->firstWhere('id', $parentA->id);
        $this->assertCount(2, $foundA->children);

        $foundB = $tree->firstWhere('id', $parentB->id);
        $this->assertCount(1, $foundB->children);
    }
}
