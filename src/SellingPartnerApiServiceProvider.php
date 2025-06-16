<?php

namespace HighsideLabs\LaravelSpApi;

use HighsideLabs\LaravelSpApi\Models\Credentials;
use Illuminate\Support\ServiceProvider;
use SellingPartnerApi\Seller\SellerConnector;
use SellingPartnerApi\Vendor\VendorConnector;

class SellingPartnerApiServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap the application events.
     */
    public function boot(): void
    {
        // Publish config file
        $this->publishes([__DIR__.'/../config/spapi.php' => config_path('spapi.php')], 'spapi-config');

        // Publish spapi_sellers and spapi_credentials migrations
        $migrationsDir = __DIR__.'/../database/migrations';
        $sellersMigrationFile = '2024_08_05_154100_create_spapi_sellers_table.php';
        $credentialsMigrationFile = '2024_08_05_154200_create_spapi_credentials_table.php';
        $this->publishesMigrations([
            "$migrationsDir/$sellersMigrationFile" => database_path("migrations/$sellersMigrationFile"),
            "$migrationsDir/$credentialsMigrationFile" => database_path("migrations/$credentialsMigrationFile"),
        ], 'spapi-multi-seller');

        $dbCacheMigrationFile = '2024_09_11_135400_increase_cache_key_and_value_size.php';
        $this->publishesMigrations([
            "$migrationsDir/$dbCacheMigrationFile" => database_path("migrations/$dbCacheMigrationFile"),
        ], 'spapi-database-cache');

        // Don't offer the option to publish the package version upgrade migration unless this is a multi-seller
        // installation that was using dynamic AWS credentials (a feature that is now deprecated/irrelevant)
        if (config('spapi.installation_type') === 'multi' && config('spapi.aws.dynamic')) {
            $v2MigrationFile = '2024_08_05_154300_upgrade_to_laravel_spapi_v2.php';
            $this->publishesMigrations([
                "$migrationsDir/$v2MigrationFile" => database_path("migrations/$v2MigrationFile"),
            ], 'spapi-v2-upgrade');
        }
    }

    /**
     * Register bindings in the container.
     */
    public function register(): void
    {
        if (config('spapi.installation_type') === 'single') {
            $creds = new Credentials([
                'client_id' => config('spapi.single.lwa.client_id'),
                'client_secret' => config('spapi.single.lwa.client_secret'),
                'refresh_token' => config('spapi.single.lwa.refresh_token'),
                'region' => config('spapi.single.endpoint'),
                'sandbox' => config('spapi.single.sandbox'),
            ]);
            // To give the cache an ID to work with
            $creds->id = 1;

            $this->app->bind(SellerConnector::class, fn () => $creds->sellerConnector());
            $this->app->bind(VendorConnector::class, fn () => $creds->vendorConnector());
        }
    }
}
