<?php

namespace HighsideLabs\LaravelSpApi;

use HaydenPierce\ClassFinder\ClassFinder;
use HighsideLabs\LaravelSpApi\Models\Credentials;
use InvalidArgumentException;
use SellingPartnerApi\Api;

final class SellingPartnerApi
{
    public const REGIONS = ['NA', 'EU', 'FE'];

    public const API_CLASSES = [
        Api\AplusContentV20201101Api::class,
        Api\AuthorizationV1Api::class,
        Api\CatalogItemsV0Api::class,
        Api\CatalogItemsV20201201Api::class,
        Api\CatalogItemsV20220401Api::class,
        Api\EasyShipV20220323Api::class,
        Api\FbaInboundEligibilityV1Api::class,
        Api\FbaInboundV0Api::class,
        Api\FbaInventoryV1Api::class,
        Api\FbaOutboundV20200701Api::class,
        Api\FeedsV20210630Api::class,
        Api\FeesV0Api::class,
        Api\FinancesV0Api::class,
        Api\ListingsRestrictionsV20210801Api::class,
        Api\ListingsV20200901Api::class,
        Api\ListingsV20210801Api::class,
        Api\MerchantFulfillmentV0Api::class,
        Api\MessagingV1Api::class,
        Api\NotificationsV1Api::class,
        Api\OrdersV0Api::class,
        Api\ProductPricingV0Api::class,
        Api\ProductTypeDefinitionsV20200901Api::class,
        Api\ReportsV20210630Api::class,
        Api\SalesV1Api::class,
        Api\SellersV1Api::class,
        Api\ServiceV1Api::class,
        Api\ShipmentInvoicingV0Api::class,
        Api\ShippingV1Api::class,
        Api\ShippingV2Api::class,
        Api\SmallAndLightV1Api::class,
        Api\SolicitationsV1Api::class,
        Api\TokensV20210301Api::class,
        Api\UploadsV20201101Api::class,
        Api\VendorDirectFulfillmentInventoryV1Api::class,
        Api\VendorDirectFulfillmentOrdersV1Api::class,
        Api\VendorDirectFulfillmentOrdersV20211228Api::class,
        Api\VendorDirectFulfillmentPaymentsV1Api::class,
        Api\VendorDirectFulfillmentSandboxV20211028Api::class,
        Api\VendorDirectFulfillmentShippingV1Api::class,
        Api\VendorDirectFulfillmentShippingV20211228Api::class,
        Api\VendorDirectFulfillmentTransactionsV1Api::class,
        Api\VendorDirectFulfillmentTransactionsV20211228Api::class,
        Api\VendorInvoicesV1Api::class,
        Api\VendorOrdersV1Api::class,
        Api\VendorShippingV1Api::class,
        Api\VendorTransactionStatusV1Api::class,
    ];

    /**
     * @param string $apiCls  The SP API class to instantiate.
     * @param \HighsideLabs\LaravelSpApi\Models\Credentials|int $credentials
     *  The Credentials or id of the credentials to use for an SP API class.
     */
    public static function makeApi(string $apiCls, Credentials|int $credentials)
    {
        if (!in_array($apiCls, static::API_CLASSES)) {
            throw new InvalidArgumentException("Invalid SP API class: $apiCls");
        }

        $creds = $credentials;
        if (is_int($credentials)) {
            $creds = Credentials::findOrFail($credentials);
        }

        $config = $creds->toSpApiConfiguration();
        return new $apiCls($config);
    }

    /**
     * Convert a region code (NA, EU, FE) to a SellingPartnerApi\Endpoint constant.
     *
     * @param string $region
     * @return string
     */
    public static function regionToEndpoint(string $region): array
    {
        return constant('SellingPartnerApi\Endpoint::' . $region);
    }
}
