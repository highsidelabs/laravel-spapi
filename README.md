Selling Partner API wrapper for Laravel
===

[![Total Downloads](https://img.shields.io/packagist/dt/highsidelabs/laravel-spapi.svg?style=flat-square)](https://packagist.org/packages/highsidelabs/laravel-spapi)
[![Latest Stable Version](https://img.shields.io/packagist/v/highsidelabs/laravel-spapi.svg?style=flat-square)](https://packagist.org/packages/highsidelabs/laravel-spapi)
[![License](https://img.shields.io/github/license/highsidelabs/laravel-spapi.svg?style=flat-square)](https://packagist.org/packages/highsidelabs/laravel-spapi)

Easily access the Selling Partner API with Laravel.

| | |
| ------ | ------ |
| [![Highside Labs Logo](https://highsidelabs.co/static/favicons/favicon.png)](https://highsidelabs.co) | **This package is developed and maintained as part of [Highside Labs](https://highsidelabs.co). If you need support integrating with Amazon's (or any other e-commerce platform's) APIs, we're happy to help! Shoot us an email at [hi@highsidelabs.co](mailto:hi@highsidelabs.co). We'd love to hear from you :)** |
| | We are the team behind the [Selling Partner API library](https://github.com/jlevers/selling-partner-api). If you need to access the Selling Partner API outside the context of Laravel, we recommending integrating with that library directly.

If you've found this library useful, please consider [becoming a Sponsor](https://github.com/sponsors/jlevers), or making a one-time donation via the button below. We appreciate any and all support you can provide!

[![paypal](https://www.paypalobjects.com/en_US/i/btn/btn_donateCC_LG.gif)](https://www.paypal.com/donate?business=EL4PRLAEMGXNQ&currency_code=USD)

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

------

## Overview

This library has two modes:
1. **Single-seller mode**, which you should use if you only plan to make requests to the Selling Partner API with a single set of credentials (most people fall into this category, so if you're not sure, this is probably you).
2. **Multi-seller mode**, which makes it easy to make requests to the Selling Partner API from within Laravel when you have multiple sets of SP API credentials (for instance, if you operate multiple seller accounts, or operate one seller account in multiple regions).

## Single-seller mode

### Setup

1. Publish the config file:

```bash
$ php artisan vendor:publish --provider="HighsideLabs\LaravelSpApi\SellingPartnerApiServiceProvider" --tag="config"
```

2. Add these environment variables to your `.env`:

```env
SPAPI_AWS_ACCESS_KEY_ID=
SPAPI_AWS_SECRET_ACCESS_KEY=
SPAPI_LWA_CLIENT_ID=
SPAPI_LWA_CLIENT_SECRET=
SPAPI_LWA_REFRESH_TOKEN=

# Optional
# SPAPI_AWS_ROLE_ARN=
# SPAPI_ENDPOINT_REGION=
```

If in Seller Central, you configured your SP API app with an IAM role ARN rather than an IAM user ARN, you'll need to put that ARN in the `SPAPI_AWS_ROLE_ARN` environment variable. Otherwise, you can leave it blank. Set `SPAPI_ENDPOINT_REGION` to the region code for the endpoint you want to use (EU for Europe, FE for Far East, or NA for North America).

You're ready to go!

### Usage

All of the API classes supported by [jlevers/selling-partner-api](https://github.com/jlevers/selling-partner-api#supported-api-segments) can be type-hinted. This example assumes you have access to the `Selling Partner Insights` role in your SP API app configuration (so that you can call `SellersV1Api::getMarketplaceParticipations()`), but the same principle applies to type-hinting any other Selling Partner API class.

```php
use Illuminate\Http\JsonResponse;
use SellingPartnerApi\Api\SellersV1Api as SellersApi;
use SellingPartnerApi\ApiException;

class SpApiController extends Controller
{
    public function index(SellersApi $api): JsonResponse
    {
        try {
            $result = $api->getMarketplaceParticipations();
            return response()->json($result);
        } catch (ApiException $e) {
            $jsonBody = json_decode($e->getResponseBody());
            return response()->json($jsonBody, $e->getCode());
        }
    }
}
```


## Multi-seller mode

### Setup

1. Publish the config file:

```bash
# Publish config/spapi.php file
$ php artisan vendor:publish --provider="HighsideLabs\LaravelSpApi\SellingPartnerApiServiceProvider" --tag="config"
```

2. Update the configuration to support multi-seller usage.
    * Change the `installation_type` in `config/spapi.php` to `multi`.
    * If the different sets of seller credentials you plan to use aren't all associated with the same set of AWS credentials (access key ID, secret access key, and optionally role ARN), make sure to change the `aws.dynamic` key to true. If you don't make that change before running migrations (the next step), the fields for AWS credentials won't be added to the database. (If you're not sure if this change applies to you, it probably doesn't.)

3. Publish the multi-seller migrations:

```bash
# Publish migrations to database/migrations/
$ php artisan vendor:publish --provider="HighsideLabs\LaravelSpApi\SellingPartnerApiServiceProvider" --tag="multi"
```


4. Run the database migrations to set up the `spapi_sellers` and `spapi_credentials` tables (corresponding to the `HighsideLabs\LaravelSpApi\Models\Seller` and `HighsideLabs\LaravelSpApi\Models\Credentials` models, respectively):

```bash
$ php artisan migrate
```

5. Add these environment variables to your `.env` (unless you changed the `aws.dynamic` configuration flag to `true` in step 2):

```env
SPAPI_AWS_ACCESS_KEY_ID=
SPAPI_AWS_SECRET_ACCESS_KEY=
```

### Usage

First you'll need to create a `Seller`, and some `Credentials` for that seller. The `Seller` and `Credentials` models work just like any other Laravel model.

```php
use HighsideLabs\LaravelSpApi\Models;

$seller = Models\Seller::create(['name' => 'MySeller']);
$credentials = Models\Credentials::create([
    'seller_id' => $seller->id,
    // You can find your selling partner ID/merchant ID by going to
    // https://<regional-seller-central-domain>/sw/AccountInfo/MerchantToken/step/MerchantToken
    'selling_partner_id' => '<AMAZON SELLER ID>',
    // Can be NA, EU, or FE
    'region' => 'NA'
    // The LWA client ID and client secret for the SP API application these credentials were created with
    'client_id' => 'amzn....',
    'client_secret' => 'fec9/aw....',
    // The LWA refresh token for this seller
    'refresh_token' => 'IWeB|....',

    // If you have the `aws.dynamic` config flag set to true, you'll also need these attributes:
    // 'access_key_id' => 'AKIA....',
    // 'secret_access_key' => '23pasdf....',
    // // Only necessary if you configured your SP API setup with an IAM role ARN, otherwise can be omitted
    // // 'role_arn' => 'arn:aws:iam::....',  
]);
```

Once you have credentials in the database, you can use them like this:

```php
use HighsideLabs\LaravelSpApi\Models\Credentials;
use Illuminate\Http\JsonResponse;
use SellingPartnerApi\Api\SellersV1Api as SellersApi;
use SellingPartnerApi\ApiException;

class SpApiController extends Controller
{
    public function __construct(SellersApi $api)
    {
        // Retrieve the credentials we just created
        $creds = Credentials::first();
        $this->api = $creds->useOn($api);
        // You can now make calls to the SP API with $creds using $this->api!
    }

    public function index(): JsonResponse
    {
        try {
            $result = $this->api->getMarketplaceParticipations();
            return response()->json($result);
        } catch (ApiException $e) {
            $jsonBody = json_decode($e->getResponseBody());
            return response()->json($jsonBody, $e->getCode());
        }
    }
}
```

Or, if you want to use a Selling Partner API class without auto-injecting it, you can quickly create one like this:

```php
use HighsideLabs\LaravelSpApi\SellingPartnerApi;
use SellingPartnerApi\Api\SellersV1Api as SellersApi;

$creds = Credentials::first();
$api = SellingPartnerApi::makeApi(SellersApi::class, $creds);
```
