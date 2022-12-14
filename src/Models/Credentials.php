<?php
namespace HighsideLabs\LaravelSpApi\Models;

use Carbon\Carbon;
use HighsideLabs\LaravelSpApi\SellingPartnerApi;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Cache;
use SellingPartnerApi\Authentication;
use SellingPartnerApi\Configuration;

class Credentials extends Model
{
    protected $table = 'spapi_credentials';

    protected $fillable = [
        'selling_partner_id',
        'region',
        'lwa_client_id',
        'lwa_client_secret',
        'refresh_token',
        'seller_id',
    ];

    protected $casts = [
        'expires_at' => 'datetime',
    ];

    protected $access_token = null;
    protected $expires_at = null;

    /**
     * Convert this Credentials instance to a SellingPartnerApi\Configuration instance.
     *
     * @return \SellingPartnerApi\Credentials
     */
    public function toSpApiConfiguration(): Configuration
    {
        return new Configuration([
            'lwaClientId' => $this->lwa_client_id,
            'lwaClientSecret' => $this->lwa_client_secret,
            'lwaRefreshToken' => $this->lwa_refresh_token,
            'awsAccessKeyId' => config('spapi.aws.access_key_id'),
            'awsSecretAccessKey' => config('spapi.aws.secret_access_key'),
            'roleArn' => config('spapi.aws.role_arn'),
            'endpoint' => SellingPartnerApi::regionToEndpoint($this->region),
            'accessToken' => $this->access_token,
            'expiresAt' => $this->expires_at,
        ]);
    }

    /**
     * Get the cache key for the access token.
     *
     * @return string
     */
    public function getAccessTokenCacheKey(): string
    {
        if (config('spapi.installation_type') === 'singleuser') {
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
        if (config('spapi.installation_type') === 'singleuser') {
            return 'spapi:access_token_expiration';
        }
        return "spapi:access_token_expiration:{$this->id}";
    }

    /**
     * Get the Seller that owns the Credentials.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function seller(): BelongsTo {
        return $this->belongsTo(Seller::class);
    }

    /**
     * Retrieve the access token.
     *
     * @return \Illuminate\Database\Eloquent\Casts\Attribute
     */
    protected function accessToken(): Attribute {
        return Attribute::make(
            fn () => $this->_getAccessToken(),
        );
    }

    /**
     * Retrieve the access token expiration.
     *
     * @return \Illuminate\Database\Eloquent\Casts\Attribute
     */
    protected function expiresAt(): Attribute {
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
    private function _getAccessToken(): string
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
            $auth = new Authentication([
                'lwaClientId' => $this->lwa_client_id,
                'lwaClientSecret' => $this->lwa_client_secret,
                'lwaRefreshToken' => $this->lwa_refresh_token,
                'awsAccessKeyId' => config('spapi.aws.access_key_id'),
                'awsSecretAccessKey' => config('spapi.aws.secret_access_key'),
                'roleArn' => config('spapi.aws.role_arn'),
                'endpoint' => SellingPartnerApi::regionToEndpoint($this->region),
            ]);

            [$newAccessToken, $expiresTimestamp] = $auth->requestLWAToken();
            $cacheExpiration = Carbon::createFromTimestamp($expiresTimestamp);

            Cache::store($tokenCacheKey, $newAccessToken, $cacheExpiration);
            Cache::store($expirationCacheKey, $expiresTimestamp, $cacheExpiration);
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
    private function _getExpiresAt(): int
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
}
