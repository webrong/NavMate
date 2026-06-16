<?php

namespace App\Console\Commands;

use App\Models\Setting;
use Illuminate\Console\Command;

class MigrateSettingsCommand extends Command
{
    protected $signature = 'settings:migrate-json';

    public function handle()
    {
        $path = storage_path('app/settings.json');
        if (! file_exists($path)) {
            $this->error('settings.json not found');

            return;
        }

        $json = json_decode(file_get_contents($path), true);
        $count = 0;

        foreach ($json as $key => $value) {
            // Normalise to the string/'1'/'0'/null representation the settings
            // table expects (matching how SettingsController stores values).
            if (is_bool($value)) {
                $value = $value ? '1' : '0';
            } elseif ($value !== null) {
                $value = (string) $value;
            }

            Setting::updateOrCreate(
                ['key' => $key],
                ['value' => $value]
            );
            $count++;
        }

        $this->info("Migrated {$count} settings to database.");
    }
}
