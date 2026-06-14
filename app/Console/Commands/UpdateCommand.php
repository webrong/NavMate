<?php

namespace App\Console\Commands;

use App\Services\UpdateService;
use Illuminate\Console\Command;

class UpdateCommand extends Command
{
    protected $signature = 'app:update
        {--check : Only check for updates, do not execute}
        {--force : Force update even if already on latest version}';

    protected $description = 'Update the application to the latest version';

    public function handle(UpdateService $updater): int
    {
        $currentVersion = $updater->getCurrentVersion();
        $this->info("Current version: {$currentVersion}");

        // Check for update
        $this->info('Checking for updates...');
        $result = $updater->checkForUpdate();

        if (! empty($result['error'])) {
            $this->error($result['error']);

            return 1;
        }

        if (empty($result['has_update']) && ! $this->option('force')) {
            $this->info('Already on the latest version.');

            return 0;
        }

        $latestVersion = $result['latest_version'] ?? 'unknown';
        $this->info("Latest version: {$latestVersion}");

        if ($this->option('check')) {
            $hasUpdate = $result['has_update'] ? 'Yes' : 'No';
            $this->info("Update available: {$hasUpdate}");
            if (! empty($result['changelog'])) {
                $this->newLine();
                $this->info('Changelog:');
                $this->line($result['changelog']);
            }

            return 0;
        }

        if (! $this->option('force') && ! $this->confirm("Update from {$currentVersion} to {$latestVersion}?")) {
            $this->info('Update cancelled.');

            return 0;
        }

        $this->info('Starting update...');

        $result = $updater->update();

        if ($result['success']) {
            $this->info('');
            $this->info($result['message']);

            return 0;
        }

        $this->error($result['message']);

        return 1;
    }
}
