<?php

namespace HighsideLabs\LaravelSpApi\Models;

use GuzzleHttp\Client;
use HighsideLabs\LaravelSpApi\Cache;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use SellingPartnerApi\Enums\Endpoint;
use SellingPartnerApi\Seller\SellerConnector;
use SellingPartnerApi\SellingPartnerApi;
use SellingPartnerApi\Vendor\VendorConnector;

class Credentials extends Model
{
    protected $table = 'spapi_credentials';

    protected $fillable = [
        'selling_partner_id',
        'region',
        'client_id',
        'client_secret',
        'refresh_token',
        'seller_id',
    ];

    /**
     * Create a SellerConnector instance from these credentials.
     */
    public function sellerConnector(
        ?array $dataElements = [],
        ?string $delegatee = null,
        ?Client $authenticationClient = null
    ): SellerConnector {
        $connector = SellingPartnerApi::seller(
            clientId: $this->client_id ?? config('spapi.single.lwa.client_id'),
            clientSecret: $this->client_secret ?? config('spapi.single.lwa.client_secret'),
            refreshToken: $this->refresh_token,
            endpoint: Endpoint::byRegion($this->region),
            dataElements: $dataElements,
            delegatee: $delegatee,
            authenticationClient: $authenticationClient,
            cache: new Cache($this->id),
        );

        static::debug($connector);

        return $connector;
    }

    /**
     * Create a VendorConnector instance from these credentials.
     */
    public function vendorConnector(
        ?array $dataElements = [],
        ?string $delegatee = null,
        ?Client $authenticationClient = null
    ): VendorConnector {
        $connector = SellingPartnerApi::vendor(
            clientId: $this->client_id ?? config('spapi.single.lwa.client_id'),
            clientSecret: $this->client_secret ?? config('spapi.single.lwa.client_secret'),
            refreshToken: $this->refresh_token,
            endpoint: Endpoint::byRegion($this->region),
            dataElements: $dataElements,
            delegatee: $delegatee,
            authenticationClient: $authenticationClient,
            cache: new Cache($this->id),
        );

        static::debug($connector);

        return $connector;
    }

    /**
     * Get the Seller that owns the Credentials.
     */
    public function seller(): BelongsTo
    {
        return $this->belongsTo(Seller::class);
    }

    /**
     * Manage debug settings on API connector class.
     */
    protected static function debug(SellingPartnerApi $connector): void
    {
        if (config('spapi.debug')) {
            if (config('spapi.debug_file')) {
                $connector->debugToFile(config('spapi.debug_file'));
            } else {
                $connector->debug();
            }
        }
    }

    /**
     * Perform any actions required after the model boots.
     */
    protected static function booted(): void
    {
        // Bust the cache when the model is updated, in case the access token
        // is no longer valid for the updated credentials.
        static::updating(function (self $credentials) {
            $cache = new Cache($credentials->id);
            $cache->clearForCreds();
        });
    }
}
