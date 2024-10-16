<?php

declare(strict_types=1);

namespace HighsideLabs\LaravelSpApi\Tests;

use HighsideLabs\LaravelSpApi\SellingPartnerApiServiceProvider;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Orchestra\Testbench\Attributes\WithEnv;
use Orchestra\Testbench\TestCase as OrchestraTestCase;

#[WithEnv('SPAPI_LWA_CLIENT_ID', 'client-id')]
#[WithEnv('SPAPI_LWA_CLIENT_SECRET', 'client-secret')]
#[WithEnv('SPAPI_LWA_REFRESH_TOKEN', 'refresh-token')]
#[WithEnv('SPAPI_ENDPOINT_REGION', 'EU')]
class TestCase extends OrchestraTestCase
{
    use RefreshDatabase;

    protected function resolveApplicationConfiguration($app): void
    {
        parent::resolveApplicationConfiguration($app);

        $spapiConfig = require __DIR__.'/../config/spapi.php';
        $app['config']->set('spapi', $spapiConfig);
    }

    protected function defineDatabaseMigrations(): void
    {
        // Migrations cannot be loaded via artisan($this, 'vendor:publish', ['--tag' => 'spapi-multi-seller']),
        // because Laravel rewrites their timestamps every time they're published, which means that Testbench
        // duplicates them for every test that's run
        $this->loadMigrationsFrom([
            __DIR__.'/../database/migrations/2024_08_05_154100_create_spapi_sellers_table.php',
            __DIR__.'/../database/migrations/2024_08_05_154200_create_spapi_credentials_table.php',
        ]);
    }

    /**
     * Get package providers.
     */
    protected function getPackageProviders($app): array
    {
        return [SellingPartnerApiServiceProvider::class];
    }
}
