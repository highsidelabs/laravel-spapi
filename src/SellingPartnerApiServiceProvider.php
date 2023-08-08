<?php

namespace HighsideLabs\LaravelSpApi;

use HighsideLabs\LaravelSpApi\Configuration;
use HighsideLabs\LaravelSpApi\Models\Credentials;
use Illuminate\Contracts\Support\DeferrableProvider;
use Illuminate\Support\ServiceProvider;
use SellingPartnerApi\Endpoint;

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
        if (!config('spapi.registration_enabled', true)) {
            return;
        }

        if (config('spapi.installation_type') === 'single') {
            $this->registerSingleSeller();
        } else {
            $this->registerMultiSeller();
        }
    }

    /**
     * Get the services provided by the provider.
     *
     * @return array
     */
    public function provides()
    {
        return SellingPartnerApi::API_CLASSES;
    }

    /**
     * Register SP API classes for a single set of credentials.
     *
     * @return void
     */
    private function registerSingleSeller(): void
    {
        $creds = new Credentials([
            'access_key_id' => config('spapi.aws.access_key_id'),
            'secret_access_key' => config('spapi.aws.secret_access_key'),
            'client_id' => config('spapi.single.lwa.client_id'),
            'client_secret' => config('spapi.single.lwa.client_secret'),
            'refresh_token' => config('spapi.single.lwa.refresh_token'),
            'role_arn' => config('spapi.aws.role_arn'),
            'region' => config('spapi.single.endpoint'),
        ]);

        foreach (SellingPartnerApi::API_CLASSES as $cls) {
            $this->app->bind(
                $cls,
                // Converting creds inside the closure prevents errors on
                // application boot due to missing env vars
                fn () => new $cls($creds->toSpApiConfiguration())
            );
        }
    }

    /**
     * Register SP API classes for multiple sets of credentials.
     *
     * @return  void
     */
    private function registerMultiSeller(): void
    {
        foreach (SellingPartnerApi::API_CLASSES as $cls) {
            $placeholderConfig = new Configuration(true, [
                'lwaClientId' => 'PLACEHOLDER',
                'lwaClientSecret' => 'PLACEHOLDER',
                'lwaRefreshToken' => 'PLACEHOLDER',
                'roleArn' => 'PLACEHOLDER',
                'awsAccessKeyId' => 'PLACEHOLDER',
                'awsSecretAccessKey' => 'PLACEHOLDER',
                'endpoint' => Endpoint::NA,
            ]);
            $this->app->bind($cls, fn () => new $cls($placeholderConfig));
        }
    }
}
