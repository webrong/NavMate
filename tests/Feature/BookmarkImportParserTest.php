<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Site;
use App\Services\BookmarkParserService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BookmarkImportParserTest extends TestCase
{
    use RefreshDatabase;

    private BookmarkParserService $service;

    private string $nestedHtml = <<<'HTML'
        <!DOCTYPE NETSCAPE-Bookmark-file-1>
        <META HTTP-EQUIV="Content-Type" CONTENT="text/html; charset=UTF-8">
        <TITLE>Bookmarks</TITLE>
        <DL><p>
            <DT><H3>开发工具</H3>
            <DL><p>
                <DT><A HREF="https://github.example.com">GitHub</A>
                <DT><H3>前端</H3>
                <DL><p>
                    <DT><A HREF="https://vuejs.example.com">Vue 官网</A>
                </DL><p>
            </DL><p>
            <DT><A HREF="https://loose.example.com">Loose Bookmark</A>
        </DL><p>
        HTML;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(BookmarkParserService::class);
    }

    public function test_nested_folders_create_the_full_parent_chain(): void
    {
        $result = $this->service->import($this->nestedHtml);

        // 开发工具 + 前端 + 未分类书签
        $this->assertSame(3, $result['categories']);
        $this->assertSame(3, $result['sites']);

        $root = Category::where('name', '开发工具')->first();
        $this->assertNotNull($root);
        $this->assertNull($root->parent_id);

        $child = Category::where('name', '前端')->first();
        $this->assertNotNull($child);
        $this->assertSame($root->id, $child->parent_id);

        // The nested site belongs to the nested category, not the import root
        $vue = Site::where('title', 'Vue 官网')->first();
        $this->assertNotNull($vue);
        $this->assertSame($child->id, $vue->category_id);

        // Top-level loose bookmark goes to the catch-all root category
        $loose = Site::where('title', 'Loose Bookmark')->first();
        $looseCategory = Category::find($loose->category_id);
        $this->assertSame('未分类书签', $looseCategory->name);
        $this->assertNull($looseCategory->parent_id);
    }

    public function test_preview_merges_ungrouped_bookmark_counts(): void
    {
        $html = <<<'HTML'
            <DL><p>
                <DT><A HREF="https://one.example.com">One</A>
                <DT><A HREF="https://two.example.com">Two</A>
                <DT><A HREF="https://three.example.com">Three</A>
            </DL><p>
            HTML;

        $preview = $this->service->preview($html);

        $this->assertSame(3, $preview['stats']['total_bookmarks']);

        $ungrouped = array_values(array_filter(
            $preview['preview'],
            fn ($p) => $p['folder'] === '未分类书签',
        ));

        // Previously the count-merge mutated an array copy: three separate
        // entries of count 1 instead of one entry of count 3
        $this->assertCount(1, $ungrouped);
        $this->assertSame(3, $ungrouped[0]['count']);
    }

    public function test_duplicates_are_skipped_by_default(): void
    {
        Site::factory()->create(['url' => 'https://loose.example.com']);

        $result = $this->service->import($this->nestedHtml);

        $this->assertSame(1, $result['skipped']);
        $this->assertSame(2, $result['sites']);
    }

    public function test_duplicates_are_counted_as_skipped_when_skip_disabled(): void
    {
        // skip_duplicate=false previously let the sites.url unique index throw
        // and roll back the whole import transaction
        Site::factory()->create(['url' => 'https://loose.example.com']);

        $result = $this->service->import($this->nestedHtml, ['skip_duplicate' => false]);

        $this->assertSame(1, $result['skipped']);
        $this->assertSame(2, $result['sites']);
        $this->assertSame(1, Site::where('url', 'https://loose.example.com')->count());
    }
}
