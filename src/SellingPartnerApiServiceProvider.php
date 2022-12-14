<?php

namespace HighsideLabs\LaravelSpApi;

use HaydenPierce\ClassFinder\ClassFinder;
use HighsideLabs\LaravelSpApi\Models\Credentials;
use Illuminate\Contracts\Support\DeferrableProvider;
use Illuminate\Support\ServiceProvider;

class SellingPartnerApiServiceProvider extends ServiceProvider implements DeferrableProvider
{
    public function __construct($app)
    {
        parent::__construct($app);
        $this->apiClasses = SellingPartnerApi::getSpApiClasses();
    }

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
            __DIR__ . '/../src/SpApiCredentials.php' => app_path('Models/SpApiCredentials.php'),
        ], 'multiuser');
    }

	/**
	 * Register bindings in the container.
	 *
	 * @return void
	 */
    public function register(): void
    {
        if (config('spapi.installation_type') === 'singleuser') {
            $this->registerSingleUser();
        } else {
            $this->registerMultiUser();
        }
    }

    /**
     * Get the services provided by the provider.
     *
     * @return array
     */
    public function provides()
    {
        return $this->apiClasses;
    }

    /**
     * Register SP API classes for a single set of credentials.
     *
     * @return void
     */
    private function registerSingleUser(): void
    {
        $creds = new Credentials([
            'lwa_client_id' => config('spapi.singleuser.lwa.client_id'),
            'lwa_client_secret' => config('spapi.singleuser.lwa.client_secret'),
            'lwa_refresh_token' => config('spapi.singleuser.lwa.refresh_token'),
            'role_arn' => config('spapi.aws.role_arn'),
            'aws_access_key_id' => config('spapi.aws.access_key_id'),
            'aws_secret_access_key' => config('spapi.aws.secret_access_key'),
            'region' => config('spapi.singleuser.endpoint'),
        ]);
        $config = $creds->toSpApiConfiguration();

        foreach ($this->apiClasses as $cls) {
            $instance = new $cls($config);
            $this->app->singleton($cls, fn () => $instance);
        }
    }

    /**
     * Register SP API classes for multiple sets of credentials.
     *
     * @return  void
     */
    private function registerMultiUser(): void
    {
        foreach ($this->apiClasses as $cls) {
            $this->app->singleton($cls, fn () => new SellingPartnerApi($cls));
        }
    }
}
