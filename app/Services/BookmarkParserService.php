<?php

namespace App\Services;

use App\Models\Category;
use App\Models\Site;
use Illuminate\Database\QueryException;
use Illuminate\Support\Str;

/**
 * 解析浏览器导出的 Netscape Bookmark HTML 文件
 */
class BookmarkParserService
{
    /**
     * 解析书签HTML文件内容
     */
    public function parse(string $html): array
    {
        if (! mb_check_encoding($html, 'UTF-8')) {
            $html = mb_convert_encoding($html, 'UTF-8', 'auto');
        }

        // 提取最外层DL内容
        if (! preg_match('/<DL>(.*)<\/DL>/si', $html, $match)) {
            return [];
        }

        return $this->parseItems($match[1]);
    }

    /**
     * 解析一组DT项（DL块的内容）
     */
    private function parseItems(string $content, int $depth = 0): array
    {
        // Prevent excessive recursion with deeply nested bookmarks
        if ($depth > 20) {
            return [];
        }

        $result = [];
        $offset = 0;
        $len = strlen($content);
        $maxIterations = 10000;
        $iteration = 0;

        while ($offset < $len) {
            if (++$iteration > $maxIterations) {
                break;
            }

            // 找下一个 <DT>
            $dtPos = stripos($content, '<DT>', $offset);
            if ($dtPos === false) {
                break;
            }

            $offset = $dtPos + 4;

            // 情况1: 文件夹 <H3>
            if (preg_match('/<H3[^>]*>(.*?)<\/H3>/si', $content, $folderMatch, 0, $offset)) {
                $folderName = $this->cleanText($folderMatch[1]);
                if ($folderName) {
                    // Use PREG_OFFSET_CAPTURE to get accurate position
                    $afterH3 = $offset + strlen($folderMatch[0]);
                    $dlStart = stripos($content, '<DL>', $afterH3);

                    if ($dlStart !== false) {
                        $inner = $this->extractDLContent($content, $dlStart);
                        if ($inner !== null) {
                            $children = $this->parseItems($inner, $depth + 1);
                            $result[] = [
                                'type' => 'folder',
                                'name' => $folderName,
                                'children' => $children,
                            ];
                            // Skip past the closing </DL> tag - use stripos for case-insensitive match
                            $closePos = stripos($content, '</DL>', $dlStart + 4 + strlen($inner));
                            $offset = $closePos !== false ? $closePos + 5 : $dlStart + 4 + strlen($inner) + 5;

                            continue;
                        }
                    }

                    // 没有DL子块，空文件夹
                    $result[] = [
                        'type' => 'folder',
                        'name' => $folderName,
                        'children' => [],
                    ];
                }

                continue;
            }

            // 情况2: 书签 <A HREF="">
            if (preg_match('/<A\s+([^>]*)>(.*?)<\/A>/si', $content, $linkMatch, 0, $offset)) {
                $attrs = $linkMatch[1];
                $title = $this->cleanText($linkMatch[2]);
                $url = $this->extractUrl($attrs);

                if ($url && ! Str::startsWith($url, ['javascript:', 'place:', 'data:'])) {
                    $addDate = null;
                    if (preg_match('/ADD_DATE="(\d+)"/si', $attrs, $m)) {
                        $addDate = date('Y-m-d H:i:s', (int) $m[1]);
                    }

                    $result[] = [
                        'type' => 'bookmark',
                        'title' => $title ?: $this->extractTitleFromUrl($url),
                        'url' => $url,
                        'add_date' => $addDate,
                    ];
                }
                $offset += strlen($linkMatch[0]);
            } else {
                // No match found, advance to avoid infinite loop
                $offset += 1;
            }
        }

        return $result;
    }

    /**
     * 提取<DL>...</DL>之间的内容，正确处理嵌套
     */
    private function extractDLContent(string $content, int $dlStart): ?string
    {
        $depth = 0;
        $pos = $dlStart;
        $len = strlen($content);

        while ($pos < $len) {
            $openTag = stripos($content, '<DL>', $pos);
            $closeTag = stripos($content, '</DL>', $pos);

            if ($closeTag === false) {
                break;
            }

            if ($openTag !== false && $openTag < $closeTag) {
                $depth++;
                $pos = $openTag + 4;
            } else {
                $depth--;
                if ($depth === 0) {
                    // 找到匹配的闭合标签
                    $innerStart = stripos($content, '>', $dlStart) + 1;

                    return substr($content, $innerStart, $closeTag - $innerStart);
                }
                $pos = $closeTag + 5;
            }
        }

        return null;
    }

    /**
     * 从属性字符串中提取URL
     */
    private function extractUrl(string $attrs): string
    {
        if (preg_match('/HREF="([^"]*)"/si', $attrs, $m)) {
            return $m[1];
        }
        if (preg_match("/HREF='([^']*)'/si", $attrs, $m)) {
            return $m[1];
        }

        return '';
    }

    /**
     * 生成导入预览数据
     */
    public function preview(string $html): array
    {
        $tree = $this->parse($html);
        $flat = $this->flatten($tree);

        $stats = ['total_bookmarks' => 0, 'total_folders' => 0];
        $preview = [];

        foreach ($flat as $group) {
            $count = count($group['bookmarks']);
            $stats['total_bookmarks'] += $count;

            if ($group['folder']) {
                $stats['total_folders']++;
                $preview[] = [
                    'folder' => $group['folder'],
                    'parent_folder' => $group['parent_folder'],
                    'count' => $count,
                    'samples' => array_slice($group['bookmarks'], 0, 3),
                ];
            } else {
                $existing = collect($preview)->first(fn ($p) => $p['folder'] === '未分类书签');
                if ($existing) {
                    $existing['count'] += $count;
                    $existing['samples'] = array_slice(
                        array_merge($existing['samples'], $group['bookmarks']), 0, 3
                    );
                } else {
                    $preview[] = [
                        'folder' => '未分类书签',
                        'parent_folder' => null,
                        'count' => $count,
                        'samples' => array_slice($group['bookmarks'], 0, 3),
                    ];
                }
            }
        }

        return ['stats' => $stats, 'preview' => $preview, 'tree' => $tree];
    }

    /**
     * 将解析树展平为分组列表
     */
    public function flatten(array $tree, ?string $parentFolder = null): array
    {
        $result = [];

        foreach ($tree as $item) {
            if ($item['type'] === 'folder') {
                $folderPath = $parentFolder ? $parentFolder.' / '.$item['name'] : $item['name'];

                // 先递归处理子文件夹
                $childResults = $this->flatten($item['children'], $folderPath);
                $result = array_merge($result, $childResults);

                // 收集当前文件夹的直接书签
                $bookmarks = array_filter($item['children'], fn ($c) => $c['type'] === 'bookmark');
                if (! empty($bookmarks)) {
                    $result[] = [
                        'folder' => $item['name'],
                        'parent_folder' => $parentFolder,
                        'bookmarks' => array_values($bookmarks),
                    ];
                }
            } elseif ($item['type'] === 'bookmark') {
                $result[] = [
                    'folder' => null,
                    'parent_folder' => null,
                    'bookmarks' => [$item],
                ];
            }
        }

        return $result;
    }

    /**
     * 执行导入
     */
    public function import(string $html, array $options = []): array
    {
        $tree = $this->parse($html);
        $flat = $this->flatten($tree);

        $imported = ['categories' => 0, 'sites' => 0, 'skipped' => 0];

        if (empty($flat)) {
            return $imported;
        }

        $skipDuplicate = $options['skip_duplicate'] ?? true;
        $parentCategoryId = $options['parent_category_id'] ?? null;
        $existingUrls = Site::pluck('url')->flip()->toArray();

        // Pre-calculate next sort_order to avoid repeated MAX() queries
        $nextSortOrder = (Category::max('sort_order') ?? 0) + 1;

        foreach ($flat as $group) {
            if (empty($group['bookmarks'])) {
                continue;
            }

            $categoryName = $group['folder'] ?: '未分类书签';

            // Resolve parent category
            $catParentId = $parentCategoryId;
            if ($group['parent_folder']) {
                $parentCat = Category::where('name', $group['parent_folder'])->first();
                if ($parentCat) {
                    $catParentId = $parentCat->id;
                }
            }

            // Look for existing category matching name AND parent
            $categoryQuery = Category::where('name', $categoryName);
            if ($catParentId) {
                $categoryQuery->where('parent_id', $catParentId);
            } else {
                $categoryQuery->whereNull('parent_id');
            }
            $category = $categoryQuery->first();

            if (! $category) {
                // Slug generation is non-atomic (check-then-insert), and the
                // categories.slug column has a UNIQUE index. Under concurrent
                // imports two transactions can pick the same slug; the second
                // insert throws QueryException. Retry once with a fresh slug
                // before giving up so a collision doesn't roll back the whole
                // import transaction.
                for ($attempt = 1; $attempt <= 2; $attempt++) {
                    try {
                        $category = Category::create([
                            'name' => $categoryName,
                            'slug' => $this->generateSlug($categoryName),
                            'is_active' => true,
                            'sort_order' => $nextSortOrder++,
                            'parent_id' => $catParentId,
                        ]);
                        $imported['categories']++;
                        break;
                    } catch (QueryException $e) {
                        // MySQL duplicate key: 1062. Only retry on this error;
                        // anything else rethrows to abort the transaction.
                        if ($attempt === 2 || ($e->errorInfo[1] ?? 0) !== 1062) {
                            throw $e;
                        }
                    }
                }
            }

            foreach ($group['bookmarks'] as $bookmark) {
                $url = $bookmark['url'];

                if ($skipDuplicate && isset($existingUrls[$url])) {
                    $imported['skipped']++;

                    continue;
                }

                Site::create([
                    'category_id' => $category->id,
                    'title' => $bookmark['title'],
                    'url' => $url,
                    'description' => '',
                    'is_public' => true,
                    'is_active' => true,
                    'sort_order' => 0,
                ]);

                $existingUrls[$url] = true;
                $imported['sites']++;
            }
        }

        return $imported;
    }

    private function cleanText(string $text): string
    {
        $text = html_entity_decode($text, ENT_QUOTES | ENT_HTML5, 'UTF-8');

        return trim(preg_replace('/\s+/', ' ', $text));
    }

    private function generateSlug(string $name): string
    {
        // 中文友好的slug: 使用拼音首字母
        $slug = preg_replace('/[^\p{Han}\p{L}\p{N}\p{P}]+/u', '-', $name);
        if (empty($slug) || ! preg_match('/[a-zA-Z]/', $slug)) {
            $slug = 'cat-'.Str::random(6);
        }

        // 确保唯一
        $count = Category::where('slug', $slug)->count();
        if ($count > 0) {
            $slug .= '-'.Str::random(4);
        }

        return $slug;
    }

    private function extractTitleFromUrl(string $url): string
    {
        $parsed = parse_url($url);

        return str_replace('www.', '', $parsed['host'] ?? $url);
    }
}
