<p align="center">
    <a href="https://highsidelabs.co" target="_blank">
        <img src="https://github.com/highsidelabs/.github/blob/main/images/logo.png?raw=true" width="125" alt="Highside Labs logo">
    </a>
</p>

<p align="center">
    <a href="https://packagist.org/packages/highsidelabs/laravel-spapi"><img alt="Total downloads" src="https://img.shields.io/packagist/dt/highsidelabs/laravel-spapi.svg?style=flat-square"></a>
    <a href="https://packagist.org/packages/highsidelabs/laravel-spapi"><img alt="Latest stable version" src="https://img.shields.io/packagist/v/highsidelabs/laravel-spapi.svg?style=flat-square"></a>
    <a href="https://packagist.org/packages/highsidelabs/laravel-spapi"><img alt="License" src="https://img.shields.io/packagist/l/highsidelabs/laravel-spapi.svg?style=flat-square"></a>
</p>

## Selling Partner API wrapper for Laravel

Simplify connecting to the Selling Partner API with Laravel. Uses [jlevers/selling-partner-api](https://github.com/jlevers/selling-partner-api) under the hood.

### Related packages

* [`jlevers/selling-partner-api`](https://github.com/jlevers/selling-partner-api): A PHP library for Amazon's [Selling Partner API](https://developer-docs.amazon.com/sp-api/docs). `highsidelabs/laravel-spapi` is a Laravel wrapper around `jlevers/selling-partner-api`.
* [`highsidelabs/walmart-api`](https://github.com/highsidelabs/walmart-api-php): A PHP library for [Walmart's seller and supplier APIs](https://developer.walmart.com), including the Marketplace, Drop Ship Vendor, Content Provider, and Warehouse Supplier APIs.
* [`highsidelabs/amazon-business-api`](https://github.com/highsidelabs/amazon-business-api): A PHP library for Amazon's [Business API](https://developer-docs.amazon.com/amazon-business/docs), with a near-identical interface to `jlevers/selling-partner-api`.

---

**This package is developed and maintained by [Highside Labs](https://highsidelabs.co). If you need support integrating with Amazon's (or any other e-commerce platform's) APIs, we're happy to help! Shoot us an email at [hi@highsidelabs.co](mailto:hi@highsidelabs.co). We'd love to hear from you :)**

If you've found any of our packages useful, please consider [becoming a Sponsor](https://github.com/sponsors/highsidelabs), or making a donation via the button below. We appreciate any and all support you can provide!

<p align="center">
    <a href="https://www.paypal.com/donate/?hosted_button_id=FG8Q6MNB4HJCC"><img alt="Donate to Highside Labs" src="https://www.paypalobjects.com/en_US/i/btn/btn_donateCC_LG.gif"></a>
</p>

---

_There is a more in-depth guide to using this package [on our blog](https://highsidelabs.co/blog/laravel-selling-partner-api)._

## Installation

```bash
$ composer require highsidelabs/laravel-spapi
```

## Table of Contents 

* [Overview](#overview)
* [Single-seller mode](#single-seller-mode)
    * [Setup](#setup)
    * [Usage](#usage)
* [Multi-seller mode](#multi-seller-mode)
    * [Setup](#setup-1)
    * [Usage](#usage-1)
* [Troubleshooting](#troubleshooting)

------

## Overview

This library has two modes:
1. **Single-seller mode**, which you should use if you only plan to make requests to the Selling Partner API with a single set of credentials (most people fall into this category, so if you're not sure, this is probably you).
2. **Multi-seller mode**, which makes it easy to make requests to the Selling Partner API from within Laravel when you have multiple sets of SP API credentials (for instance, if you operate multiple seller accounts, or operate one seller account in multiple regions).

## Single-seller mode

### Setup

1. Publish the config file:

```bash
$ php artisan vendor:publish --tag="spapi-config"
```

2. Add these environment variables to your `.env`:

```env
SPAPI_LWA_CLIENT_ID=
SPAPI_LWA_CLIENT_SECRET=
SPAPI_LWA_REFRESH_TOKEN=

# Optional
# SPAPI_ENDPOINT_REGION=
```

Set `SPAPI_ENDPOINT_REGION` to the region code for the endpoint you want to use (EU for Europe, FE for Far East, or NA for North America). The default is North America.

### Usage

`SellerConnector` and `VendorConnector` can be type-hinted, and the connector classes can be used to create instances of all APIs supported by [jlevers/selling-partner-api](https://github.com/jlevers/selling-partner-api#supported-api-segments). This example assumes you have access to the `Selling Partner Insights` role in your SP API app configuration (so that you can call `SellingPartnerApi\Seller\SellersV1\Api::getMarketplaceParticipations()`), _but the same principle applies to calling any other Selling Partner API endpoint._

```php
use Illuminate\Http\JsonResponse;
use Saloon\Exceptions\Request\RequestException;
use SellingPartnerApi\Seller\SellerConnector;

class SpApiController extends Controller
{
    public function index(SellerConnector $connector): JsonResponse
    {
        try {
            $api = $connector->sellersV1();
            $result = $api->getMarketplaceParticipations();
            return response()->json($result->json());
        } catch (RequestException $e) {
            $response = $e->getResponse();
            return response()->json($response->json(), $e->getStatus());
        }
    }
}
```


## Multi-seller mode

### Setup

1. Publish the config file:

```bash
# Publish config/spapi.php file
$ php artisan vendor:publish --provider="HighsideLabs\LaravelSpApi\SellingPartnerApiServiceProvider"
```

2. Change the `installation_type` in `config/spapi.php` to `multi`.

3. Publish the multi-seller migrations:

```bash
# Publish migrations to database/migrations/
$ php artisan vendor:publish --tag="spapi-multi-seller"
```


4. Run the database migrations to set up the `spapi_sellers` and `spapi_credentials` tables (corresponding to the `HighsideLabs\LaravelSpApi\Models\Seller` and `HighsideLabs\LaravelSpApi\Models\Credentials` models, respectively):

```bash
$ php artisan migrate
```

### Usage

First you'll need to create a `Seller`, and some `Credentials` for that seller. The `Seller` and `Credentials` models work just like any other Laravel model.

```php
use HighsideLabs\LaravelSpApi\Models\Credentials;
use HighsideLabs\LaravelSpApi\Models\Seller;

$seller = Seller::create(['name' => 'My Seller']);
$credentials = Credentials::create([
    'seller_id' => $seller->id,
    // You can find your selling partner ID/merchant ID by going to
    // https://<regional-seller-central-domain>/sw/AccountInfo/MerchantToken/step/MerchantToken
    'selling_partner_id' => '<AMAZON SELLER ID>',
    // Can be NA, EU, or FE
    'region' => 'NA',
    // The LWA client ID and client secret for the SP API application these credentials were created with
    'client_id' => 'amzn....',
    'client_secret' => 'fec9/aw....',
    // The LWA refresh token for this seller
    'refresh_token' => 'IWeB|....',
]);
```

Once you have credentials in the database, you can use them to retrieve a `SellerConnector`  instance, from which you can get an instance of any seller API:

```php
use HighsideLabs\LaravelSpApi\Models\Credentials;
use Illuminate\Http\JsonResponse;
use Saloon\Exceptions\Request\RequestException;

$creds = Credentials::first();
/** @var SellingPartnerApi\Seller\SellersV1\Api $api */
$api = $creds->sellerConnector()->sellersV1();

try {
    $result = $api->getMarketplaceParticipations();
    $dto = $result->dto();
} catch (RequestException $e) {
    $responseBody = $e->getResponse()->json();
}
```

The same goes for a `VendorConnector` instance:

```php
use HighsideLabs\LaravelSpApi\Models\Credentials;
use Illuminate\Http\JsonResponse;
use Saloon\Exceptions\Request\RequestException;

$creds = Credentials::first();
/** @var SellingPartnerApi\Vendor\DirectFulfillmentShippingV1\Api $api */
$api = $creds->vendorConnector()->directFulfillmentShippingV1();
```

## Troubleshooting

If you encounter an error like `String data, right truncated: 7 ERROR:  value too long for type character varying(255)`, it's probably because you're using Laravel's database cache, which by default has a 255-character limit on cache keys and values. This library has a migration available to fix this:

```bash
$ php artisan vendor:publish --tag="spapi-database-cache"
$ php artisan migrate
```