<?php

declare(strict_types=1);

namespace HighsideLabs\LaravelSpApi\Tests;

use Orchestra\Testbench\Attributes\WithConfig;
use Orchestra\Testbench\Attributes\WithEnv;
use SellingPartnerApi\Enums\Endpoint;
use SellingPartnerApi\Seller\SellerConnector;
use SellingPartnerApi\Vendor\VendorConnector;

#[WithEnv('SPAPI_LWA_CLIENT_ID', 'client-id')]
#[WithEnv('SPAPI_LWA_CLIENT_SECRET', 'client-secret')]
#[WithEnv('SPAPI_LWA_REFRESH_TOKEN', 'refresh-token')]
#[WithEnv('SPAPI_ENDPOINT_REGION', 'EU')]
#[WithConfig('spapi.debug', true)]
class SingleSellerTest extends TestCase
{
    private SellerConnector $sellerConnector;

    private VendorConnector $vendorConnector;

    protected function resolveApplicationConfiguration($app): void
    {
        parent::resolveApplicationConfiguration($app);

        $spapiConfig = require_once __DIR__.'/../config/spapi.php';
        $app['config']->set('spapi', $spapiConfig);
    }

    public function setUp(): void
    {
        parent::setUp();

        $this->sellerConnector = $this->app->make(SellerConnector::class);
        $this->vendorConnector = $this->app->make(VendorConnector::class);
    }

    public function testUsesCorrectCredentials(): void
    {
        $this->assertEquals('client-id', $this->sellerConnector->clientId);
        $this->assertEquals(Endpoint::EU, $this->sellerConnector->endpoint);

        $this->assertEquals('client-id', $this->vendorConnector->clientId);
        $this->assertEquals(Endpoint::EU, $this->vendorConnector->endpoint);
    }
}
