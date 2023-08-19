<?php

namespace HighsideLabs\LaravelSpApi\Models;

use Carbon\Carbon;
use HighsideLabs\LaravelSpApi\SellingPartnerApi;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Cache;
use SellingPartnerApi\Authentication;
use HighsideLabs\LaravelSpApi\Configuration;

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
        'access_key_id',
        'secret_access_key',
        'role_arn',
    ];

    protected $casts = [
        'expires_at' => 'datetime',
    ];

    protected $access_token = null;
    protected $expires_at = null;

    /**
     * Use these credentials on the given SP API class, and return the usable instance.
     *
     * @param mixed $apiInstance
     * @return mixed
     */
    public function useOn($apiInstance)
    {
        $config = $this->toSpApiConfiguration();
        $apiInstance->setConfig($config);
        return $apiInstance;
    }

    /**
     * Convert this Credentials instance to a HighsideLabs\LaravelSpApi\Configuration instance.
     *
     * @param bool $placeholder  If true, the returned Configuration will throw an exception if any API
     *  methods are called. This is useful for preventing unauthorized errors as result of auto-injected
     *  API class being used before it has been passed through Credentials::useOn().
     * @return HighsideLabs\LaravelSpApi\Configuration
     */
    public function toSpApiConfiguration(bool $placeholder = false): Configuration
    {
        $dynamicAws = config('spapi.aws.dynamic');

        $configuration = new Configuration($placeholder, [
            'lwaClientId' => $this->client_id,
            'lwaClientSecret' => $this->client_secret,
            'lwaRefreshToken' => $this->refresh_token,
            'awsAccessKeyId' => $dynamicAws ? $this->access_key_id : config('spapi.aws.access_key_id'),
            'awsSecretAccessKey' => $dynamicAws ? $this->secret_access_key : config('spapi.aws.secret_access_key'),
            'roleArn' => $dynamicAws ? $this->role_arn : config('spapi.aws.role_arn'),
            'endpoint' => SellingPartnerApi::regionToEndpoint($this->handleRegion()),
            'accessToken' => $this->_getAccessToken(),
            'accessTokenExpiration' => $this->_getExpiresAt(),
        ]);

        if (config('spapi.debug', false)) {
            $configuration->setDebug(true);
            $configuration->setDebugFile(config('spapi.debug_file'));
        }

        return $configuration;
    }

    /**
     * Get the cache key for the access token.
     *
     * @return string
     */
    public function getAccessTokenCacheKey(): string
    {
        if (config('spapi.installation_type') === 'single') {
            return 'spapi:access_token';
        }
        return "spapi:access_token:{$this->id}";
    }

    /**
     * Get the cache key for the access token expiration timestamp.
     *
     * @return string
     */
    public function getExpiresAtCacheKey(): string
    {
        if (config('spapi.installation_type') === 'single') {
            return 'spapi:access_token_expiration';
        }
        return "spapi:access_token_expiration:{$this->id}";
    }

    /**
     * Remove any cached access token info.
     *
     * @return static
     */
    public function bustCache(): static
    {
        Cache::forget($this->getAccessTokenCacheKey());
        Cache::forget($this->getExpiresAtCacheKey());
        $this->access_token = null;

        return $this;
    }

    /**
     * Get the Seller that owns the Credentials.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function seller(): BelongsTo
    {
        return $this->belongsTo(Seller::class);
    }

    /**
     * Converts whatever the value of $this->region is to a region code string,
     * used when determining which Selling Partner API endpoint to use.
     *
     * @return string
     */
    protected function handleRegion(): string
    {
        return $this->region;
    }

    /**
     * Retrieve the access token.
     *
     * @return \Illuminate\Database\Eloquent\Casts\Attribute
     */
    protected function accessToken(): Attribute
    {
        return Attribute::make(
            fn () => $this->_getAccessToken(),
        );
    }

    /**
     * Retrieve the access token expiration.
     *
     * @return \Illuminate\Database\Eloquent\Casts\Attribute
     */
    protected function expiresAt(): Attribute
    {
        return Attribute::make(
            fn () => $this->_getExpiresAt(),
        );
    }

    /**
     * Retrieve the access token from the cache if it's there. Otherwise,
     * request a new one from Amazon and cache it.
     *
     * @return string
     */
    protected function _getAccessToken(): string
    {
        if (!is_null($this->access_token)) {
            return $this->access_token;
        }

        $tokenCacheKey = $this->getAccessTokenCacheKey();
        $expirationCacheKey = $this->getExpiresAtCacheKey();

        $cachedToken = Cache::get($tokenCacheKey);
        if (!is_null($cachedToken)) {
            $this->access_token = $cachedToken;
        } else {
            $dynamicAws = config('spapi.aws.dynamic');
            $auth = new Authentication([
                'lwaClientId' => $this->client_id,
                'lwaClientSecret' => $this->client_secret,
                'lwaRefreshToken' => $this->refresh_token,
                'awsAccessKeyId' => $dynamicAws ? $this->access_key_id : config('spapi.aws.access_key_id'),
                'awsSecretAccessKey' => $dynamicAws ? $this->secret_access_key : config('spapi.aws.secret_access_key'),
                'roleArn' => $dynamicAws ? $this->role_arn : config('spapi.aws.role_arn'),
                'endpoint' => SellingPartnerApi::regionToEndpoint($this->handleRegion()),
            ]);

            [$newAccessToken, $expiresTimestamp] = $auth->requestLWAToken();
            $cacheExpiration = Carbon::createFromTimestamp($expiresTimestamp);

            Cache::put($tokenCacheKey, $newAccessToken, $cacheExpiration);
            Cache::put($expirationCacheKey, $expiresTimestamp, $cacheExpiration);
            $this->access_token = $newAccessToken;
            $this->expires_at = $expiresTimestamp;
        }

        return $this->access_token;
    }

    /**
     * Retrieve the access token expiration, if there is an access token in the cache.
     * Otherwise, request a new access token from Amazon and save that token's expiration time.
     *
     * @return int
     */
    protected function _getExpiresAt(): int
    {
        if (!is_null($this->expires_at)) {
            return $this->expires_at;
        }

        $cacheKey = $this->getExpiresAtCacheKey();
        $cachedExpiration = Cache::get($cacheKey);

        if (is_null($cachedExpiration)) {
            // We retrieve, cache, and set $this->expires_at in _getAccessToken()
            $this->_getAccessToken();
        } else {
            $this->expires_at = $cachedExpiration;
        }

        return $this->expires_at;
    }

    /**
     * Perform any actions required after the model boots.
     *
     * @return void
     */
    protected static function booted(): void
    {
        // Bust the cache when the model is updated, in case the access token
        // is no longer valid for the updated credentials.
        static::updating(function (self $credentials) {
            $credentials->bustCache();
            $credentials->access_token = null;
            $credentials->expires_at = null;
        });
    }
}
