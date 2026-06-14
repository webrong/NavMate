<?php

namespace Tests;

use Illuminate\Contracts\Console\Kernel;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    /**
     * Disable console output mocking so artisan calls invoked during setup
     * (e.g. RefreshDatabase's `migrate:fresh`) don't trip Mockery when a
     * command attempts an interactive prompt.
     *
     * Declared public to match the parent class visibility.
     */
    public $mockConsoleOutput = false;

    /**
     * Create the application, then pin the testing config.
     *
     * We override createApplication (instead of setUp) because RefreshDatabase
     * runs `migrate:fresh` during setUp's trait initialization — which itself
     * happens after refreshApplication(). By that point the app has already
     * booted from the (possibly production) .env, so forcing config in setUp is
     * too late. Overriding here lets us swap the config right after bootstrap,
     * before any trait setUp code runs.
     *
     * Two environment-specific traps are handled:
     *
     * 1. Missing .env file: In CI (and some local setups) there is no .env at
     *    test time. Dotenv's Reader uses @file_get_contents which on PHP 8.4 +
     *    PHPUnit 12 no longer fully suppresses the warning, and PHPUnit turns
     *    every warning into a test failure. We seed a minimal .env before
     *    bootstrap to avoid this.
     *
     * 2. Wrong DB connection: phpunit.xml's <env> overrides don't reliably
     *    reach Laravel's bootstrap (notably on Windows), so we pin the sqlite
     *    memory config explicitly.
     */
    public function createApplication()
    {
        $basePath = Application::inferBasePath();

        // Ensure a .env file exists so Dotenv doesn't emit a warning during
        // bootstrap. CI's "Prepare application" step normally creates one, but
        // this makes tests self-contained regardless of the host setup.
        $envPath = $basePath.'/.env';
        if (! file_exists($envPath)) {
            @file_put_contents($envPath, "APP_KEY=base64:7bvAlPlM1/PJ1xe0PzRc96sAszsrGq0wnt6URmnHyB4=\n");
        }

        $app = require $basePath.'/bootstrap/app.php';

        $this->traitsUsedByTest = array_flip(class_uses_recursive(static::class));

        $app->make(Kernel::class)->bootstrap();

        // Force the testing config regardless of what .env says. This must
        // run before RefreshDatabase's migrate:fresh, which happens in setUp.
        config([
            'app.env' => 'testing',
            'database.default' => 'sqlite',
            'database.connections.sqlite' => [
                'driver' => 'sqlite',
                'url' => null,
                'database' => ':memory:',
                'prefix' => '',
                'foreign_key_constraints' => true,
            ],
            'cache.default' => 'array',
            'session.driver' => 'array',
            'queue.default' => 'sync',
        ]);

        // Application::environment() reads $app['env'], which was bound to the
        // (possibly production) value during bootstrap. Rebind it so that
        // migrate:fresh's confirmToProceed() sees testing and skips the prompt.
        $app['env'] = 'testing';

        return $app;
    }
}
