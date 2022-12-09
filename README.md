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

## Installation

```bash
$ composer require highsidelabs/laravel-spapi
```

## Setup

1. Publish the config file:

```bash
$ php artisan vendor:publish --provider="HighsideLabs\LaravelSpApi\SellingPartnerApiServiceProvider" --tag="config"
```

2. Register the `LaravelSpApi` service provider in `config/app.php` by adding it to the `providers` key:

```php
[
    // ...

    'providers' => [
        // ...
        HighsideLabs\LaravelSpApi\SellingPartnerApiServiceProvider::class
    ]
]
```

3. Add these environment variables to your `.env`:

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

If in Seller Central, you configured your SP API app with an IAM role ARN rather than an IAM user ARN, you'll need to put that ARN in the `SPAPI_AWS_ROLE_ARN` environment variable. Otherwise, you can leave it blank. Similarly, if you're using the North American SP API endpoint (`https://sellingpartnerapi-na.amazon.com`), you can leave out the `SPAPI_ENDPOINT_REGION` variable. Otherwise, set it to the region code for the endpoint you want to use (EU for Europe, FE for Far East, or NA for North America).

You're ready to go!


## Usage

All of the API classes supported by [jlevers/selling-partner-api](https://github.com/jlevers/selling-partner-api#supported-api-segments) can be type-hinted. This example assumes you have access to the `Selling Partner Insights` role in your SP API app configuration (so that you can call `SellersV1Api::getMarketplaceParticipations()`), but the same principle applies to type-hinting any other Selling Partner API class.

```php
use Illuminate\Http\JsonResponse;
use SellingPartnerApi\Api\SellersV1Api as SellersApi;
use SellingPartnerApi\ApiException;

class SpApiController extends Controller
{
    public function __construct(SellersApi $api)
    {
        $this->api = $api;
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
