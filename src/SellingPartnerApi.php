<?php

namespace HighsideLabs\LaravelSpApi;

use HaydenPierce\ClassFinder\ClassFinder;
use HighsideLabs\LaravelSpApi\Models\Credentials;
use InvalidArgumentException;

class SellingPartnerApi {
    public const REGIONS = ['NA', 'EU', 'FE'];

    public function __construct(string $apiCls)
    {
        if (!in_array($apiCls, self::getSpApiClasses())) {
            throw new InvalidArgumentException("Invalid SP API class: $apiCls");
        }

        $this->apiCls = $apiCls;
    }

    /**
     * @param \HighsideLabs\LaravelSpApi\Models\Credentials|int $credentials
     *  The Credentials or id of the credentials to use for an SP API class.
     */
    public function withCredentials(Credentials|int $credentials) {
        $creds = $credentials;
        if (is_int($credentials)) {
            $creds = Credentials::findOrFails($credentials);
        }

        $config = $creds->toSpApiConfiguration();
        return new $this->apiCls($config);
    }

    /**
     * Get all the SP API classes (from the SellingPartnerApi\Api namespace).
     *
     * @return array<string>
     */
    public static function getSpApiClasses(): array
    {
        return ClassFinder::getClassesInNamespace('SellingPartnerApi\Api');
    }

    /**
     * Convert a region code (NA, EU, FE) to a SellingPartnerApi\Endpoint constant.
     *
     * @param string $region
     * @return string
     */
    public static function regionToEndpoint(string $region): string
    {
        if (!in_array($region, static::REGIONS)) {
            throw new InvalidArgumentException("Invalid SP API region: $region");
        }
        return constant('SellingPartnerApi\Endpoint::' . $region);
    }
}