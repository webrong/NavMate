<?php

namespace App\Http\Controllers;

use App\Services\InstallerService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class InstallController extends Controller
{
    public function __construct(
        private InstallerService $installer
    ) {}

    /**
     * Block all actions if already installed
     */
    private function guardAgainstInstalled(): void
    {
        if (InstallerService::isInstalled()) {
            abort(403, '应用已安装，如需重新安装请删除 storage/app/installed 文件');
        }
    }

    /**
     * Render the install wizard page
     */
    public function index()
    {
        $this->guardAgainstInstalled();

        return view('install');
    }

    /**
     * Check server environment
     */
    public function checkEnvironment(): JsonResponse
    {
        $this->guardAgainstInstalled();

        return response()->json($this->installer->checkEnvironment());
    }

    /**
     * Test database connection
     */
    public function testDatabase(Request $request): JsonResponse
    {
        $this->guardAgainstInstalled();
        $data = $request->validate([
            'db_host' => 'required|string',
            'db_port' => 'required|integer',
            'db_database' => 'nullable|string',
            'db_username' => 'required|string',
            'db_password' => 'nullable|string',
        ]);

        $result = $this->installer->testDatabase($data);

        return response()->json($result, $result['success'] ? 200 : 422);
    }

    /**
     * Test Redis connection
     */
    public function testRedis(Request $request): JsonResponse
    {
        $this->guardAgainstInstalled();
        $data = $request->validate([
            'redis_host' => 'required|string',
            'redis_port' => 'required|integer',
            'redis_password' => 'nullable|string',
        ]);

        $result = $this->installer->testRedis($data);

        return response()->json($result, $result['success'] ? 200 : 422);
    }

    /**
     * Execute installation
     */
    public function execute(Request $request): JsonResponse
    {
        $this->guardAgainstInstalled();
        @set_time_limit(300);

        $data = $request->validate([
            'db_host' => 'required|string',
            'db_port' => 'required|integer',
            'db_database' => 'required|string',
            'db_username' => 'required|string',
            'db_password' => 'nullable|string',
            'cache_store' => 'required|in:database,redis,file',
            'redis_host' => 'nullable|string',
            'redis_port' => 'nullable|integer',
            'redis_password' => 'nullable|string',
            'app_name' => 'required|string|max:100',
            'app_url' => 'required|url|max:500',
            'admin_name' => 'required|string|max:100',
            'admin_email' => 'required|email|max:255',
            'admin_password' => 'required|string|min:8|max:255',
            'seed_sample' => 'nullable|boolean',
            'skip_mail' => 'nullable|boolean',
            'mail_host' => 'nullable|string|max:255',
            'mail_port' => 'nullable|integer|between:1,65535',
            'mail_encryption' => 'nullable|string|in:ssl,tls,null',
            'mail_username' => 'nullable|string|max:255',
            'mail_password' => 'nullable|string|max:255',
            'mail_from_address' => 'nullable|email|max:255',
            'mail_from_name' => 'nullable|string|max:255',
        ]);

        try {
            $result = $this->installer->install($data);
        } catch (\Throwable $e) {
            Log::error('Installation failed', ['error' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);
            $result = ['success' => false, 'message' => '安装失败，请查看日志获取详情'];
        }

        return response()->json($result, $result['success'] ? 200 : 500);
    }
}
