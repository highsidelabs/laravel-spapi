<?php

namespace HighsideLabs\LaravelSpApi;

use HaydenPierce\ClassFinder\ClassFinder;
use HighsideLabs\LaravelSpApi\Models\Credentials;
use InvalidArgumentException;

final class SellingPartnerApi
{
    public const REGIONS = ['NA', 'EU', 'FE'];

    /**
     * @param string $apiCls  The SP API class to instantiate.
     * @param \HighsideLabs\LaravelSpApi\Models\Credentials|int $credentials
     *  The Credentials or id of the credentials to use for an SP API class.
     */
    public static function makeApi(string $apiCls, Credentials|int $credentials)
    {
        if (!in_array($apiCls, self::getSpApiClasses())) {
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
     * Get all the SP API classes (from the SellingPartnerApi\Api namespace).
     *
     * @return array<string>
     */
    public static function getSpApiClasses(): array
    {
        $classes = ClassFinder::getClassesInNamespace('SellingPartnerApi\Api');
        // Don't return the BaseApi class, since it is abstract (and thus not instantiable)
        return array_filter($classes, fn ($cls) => is_subclass_of($cls, 'SellingPartnerApi\Api\BaseApi'));
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
