<?php

declare(strict_types=1);

namespace HighsideLabs\LaravelSpApi\Tests;

use Orchestra\Testbench\Attributes\WithConfig;
use SellingPartnerApi\Enums\Endpoint;
use SellingPartnerApi\Seller\SellerConnector;
use SellingPartnerApi\Vendor\VendorConnector;

#[WithConfig('spapi.debug', true)]
class SingleSellerTest extends TestCase
{
    private SellerConnector $sellerConnector;

    private VendorConnector $vendorConnector;

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
