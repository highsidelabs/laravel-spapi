<?php

declare(strict_types=1);

namespace HighsideLabs\LaravelSpApi\Tests;

use HighsideLabs\LaravelSpApi\Models\Credentials;
use HighsideLabs\LaravelSpApi\Models\Seller;
use SellingPartnerApi\Enums\Endpoint;
use SellingPartnerApi\Seller\SellersV1;
use SellingPartnerApi\Vendor\DirectFulfillmentShippingV1;

class MultiSellerTest extends TestCase
{
    private Credentials $creds;

    public function setUp(): void
    {
        parent::setUp();

        $seller = Seller::create(['name' => 'seller-1']);
        $this->creds = Credentials::create([
            'seller_id' => $seller->id,
            'selling_partner_id' => 'spapi01',
            'client_id' => 'client-id-1',
            'client_secret' => 'client-secret-1',
            'refresh_token' => 'refresh-token-1',
            'region' => 'NA',
        ]);
    }

    public function testCanMakeSellerApis(): void
    {
        $sellerConnector = $this->creds->sellerConnector();
        $api = $sellerConnector->sellersV1();

        $this->assertInstanceOf(SellersV1\Api::class, $api);
        $this->assertEquals('client-id-1', $sellerConnector->clientId);
        $this->assertEquals(Endpoint::NA, $sellerConnector->endpoint);
    }

    public function testCanMakeVendorApis(): void
    {
        $vendorConnector = $this->creds->vendorConnector();
        $api = $vendorConnector->directFulfillmentShippingV1();

        $this->assertInstanceOf(DirectFulfillmentShippingV1\Api::class, $api);
        $this->assertEquals('client-id-1', $vendorConnector->clientId);
        $this->assertEquals(Endpoint::NA, $vendorConnector->endpoint);
    }
}
