<?php

namespace HighsideLabs\LaravelSpApi;

use HaydenPierce\ClassFinder\ClassFinder;
use Illuminate\Contracts\Support\DeferrableProvider;
use Illuminate\Support\Arr;
use Illuminate\Support\ServiceProvider;
use SellingPartnerApi\Configuration;

class SellingPartnerApiServiceProvider extends ServiceProvider implements DeferrableProvider
{
    public function __construct($app)
    {
        parent::__construct($app);
        $this->apiClasses = ClassFinder::getClassesInNamespace('SellingPartnerApi\Api');
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
        foreach ($this->apiClasses as $cls) {
            $alias = 'HighsideLabs\LaravelSpApi\Api\\' . Arr::last(explode('\\', $cls));
            class_alias($cls, $alias);

            $config = new Configuration([
                'lwaClientId' => config('spapi.singleuser.lwa.client_id'),
                'lwaClientSecret' => config('spapi.singleuser.lwa.client_secret'),
                'lwaRefreshToken' => config('spapi.singleuser.lwa.refresh_token'),
                'awsAccessKeyId' => config('spapi.aws.access_key_id'),
                'awsSecretAccessKey' => config('spapi.aws.secret_access_key'),
                'endpoint' => constant('SellingPartnerApi\Endpoint::' . config('spapi.singleuser.endpoint')),
            ]);
            $instance = new $cls($config);
            $this->app->singleton($alias, fn () => $instance);
            $this->app->singleton($cls, fn () => $instance);
        }
    }

    /**
     * Register SP API classes for multiple sets of credentials.
     *
     * @return  void
     */
    private function registerMultiUser(): void
    {}
}
