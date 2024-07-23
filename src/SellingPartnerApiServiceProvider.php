<?php

namespace HighsideLabs\LaravelSpApi;

use HighsideLabs\LaravelSpApi\Models\Credentials;
use Illuminate\Contracts\Support\DeferrableProvider;
use Illuminate\Support\ServiceProvider;
use SellingPartnerApi\Seller\SellerConnector;
use SellingPartnerApi\Vendor\VendorConnector;

class SellingPartnerApiServiceProvider extends ServiceProvider implements DeferrableProvider
{
    /**
     * Bootstrap the application events.
     *
     * @return void
     */
    public function boot(): void
    {
        // Publish config file
        $this->publishes([
            __DIR__ . '/../config/spapi.php' => config_path('spapi.php'),
        ], 'config');

        // Publish sellers and spapi_credentials migrations
        $time = time();
        $sellersMigrationFile = date('Y_m_d_His', $time) . '_create_spapi_sellers_table.php';
        $credentialsMigrationFile = date('Y_m_d_His', $time + 1) . '_create_spapi_credentials_table.php';
        $this->publishes([
            __DIR__ . '/../database/migrations/create_spapi_sellers_table.php.stub' => database_path('migrations/' . $sellersMigrationFile),
            __DIR__ . '/../database/migrations/create_spapi_credentials_table.php.stub' => database_path('migrations/' . $credentialsMigrationFile),
        ], 'multi');

        // Don't offer the option to publish the AWS migration unless this is a multi-seller installation with dynamic AWS
        // credentials
        if (config('spapi.installation_type') === 'multi' && config('spapi.aws.dynamic')) {
            $awsMigrationFile = date('Y_m_d_His', $time + 2) . '_add_aws_fields_to_spapi_credentials_table.php';
            $this->publishes([
                __DIR__ . '/../database/migrations/add_aws_fields_to_spapi_credentials_table.php.stub' => database_path('migrations/' . $awsMigrationFile),
            ], 'add-aws');
        }
    }

    /**
     * Register bindings in the container.
     *
     * @return void
     */
    public function register(): void
    {
        if (config('spapi.installation_type') === 'single') {
            $creds = new Credentials([
                'client_id' => config('spapi.single.lwa.client_id'),
                'client_secret' => config('spapi.single.lwa.client_secret'),
                'refresh_token' => config('spapi.single.lwa.refresh_token'),
                'region' => config('spapi.single.endpoint'),
            ]);

            $this->app->bind(SellerConnector::class, fn () => $creds->sellerConnector());
            $this->app->bind(VendorConnector::class, fn () => $creds->vendorConnector());
        }
    }

    /**
     * Get the services provided by the provider.
     *
     * @return array
     */
    public function provides(): array
    {
        if (config('spapi.installation_type') === 'single') {
            return [SellerConnector::class, VendorConnector::class];
        }

        return [];
    }
}
