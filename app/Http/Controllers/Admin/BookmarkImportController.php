<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\BookmarkParserService;
use App\Traits\ClearsDashboardCache;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class BookmarkImportController extends Controller
{
    use ClearsDashboardCache;

    public function __construct(
        private BookmarkParserService $parser
    ) {}

    /**
     * 预览书签文件内容
     */
    public function preview(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'bookmark_file' => 'required|file|mimes:html,htm|max:10240',
        ], [
            'bookmark_file.required' => '请选择书签文件',
            'bookmark_file.file' => '请上传有效的文件',
            'bookmark_file.mimes' => '只支持 .html 或 .htm 格式的书签文件',
            'bookmark_file.max' => '文件大小不能超过 10MB',
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors()->first()], 422);
        }

        try {
            $html = $request->file('bookmark_file')->getContents();
            $result = $this->parser->preview($html);

            // Cache parsed result using a unique key (avoid bloating session)
            $cacheKey = 'bookmark_preview:' . $request->session()->getId();
            Cache::put($cacheKey, $html, 1800); // 30 min TTL

            return response()->json($result);
        } catch (\Exception $e) {
            return response()->json(['error' => '书签文件解析失败，请检查文件格式'], 500);
        }
    }

    /**
     * 执行导入
     */
    public function import(Request $request)
    {
        $cacheKey = 'bookmark_preview:' . $request->session()->getId();
        $html = Cache::get($cacheKey);

        if (!$html) {
            return response()->json(['error' => '请先上传并预览书签文件'], 422);
        }

        $request->validate([
            'skip_duplicate' => 'boolean',
            'parent_category_id' => 'nullable|exists:categories,id',
        ]);

        try {
            $result = DB::transaction(function () use ($html, $request) {
                return $this->parser->import($html, [
                    'skip_duplicate' => $request->boolean('skip_duplicate', true),
                    'parent_category_id' => $request->input('parent_category_id'),
                ]);
            });

            // Clear cache after successful import
            Cache::forget($cacheKey);
            $this->clearDashboardCache();

            return response()->json([
                'success' => true,
                'message' => sprintf(
                    '导入完成！新增 %d 个分类，%d 个站点，跳过 %d 个重复',
                    $result['categories'],
                    $result['sites'],
                    $result['skipped']
                ),
                'result' => $result,
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => '导入失败，请重试'], 500);
        }
    }
}
