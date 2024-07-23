<?php

declare(strict_types=1);

namespace HighsideLabs\LaravelSpApi;

use DateTimeImmutable;
use Illuminate\Support\Facades\Cache as LaravelCache;
use SellingPartnerApi\Authentication\AccessTokenAuthenticator;
use SellingPartnerApi\Contracts\TokenCache;

class Cache implements TokenCache
{
    private const TAG = 'spapi';
    private const TOKEN_PREFIX = 'token::';
    private const EXPIRATION_PREFIX = 'expiration::';

    private string $credsTag;

    public function __construct(int $credsId) {
        $this->credsTag = "creds$credsId";
    }

    public function get(string $key): AccessTokenAuthenticator|false
    {
        $token = LaravelCache::tags([self::TAG, $this->credsTag])->get(self::TOKEN_PREFIX . $key);
        $expiration = LaravelCache::tags([self::TAG, $this->credsTag])->get(self::EXPIRATION_PREFIX . $key);
        if (! $token || ! $expiration) {
            return false;
        }

        $expirationDt = (new DateTimeImmutable())->setTimestamp($expiration);

        return new AccessTokenAuthenticator($token, expiresAt: $expirationDt);
    }

    public function set(string $key, AccessTokenAuthenticator $authenticator): void
    {
        $ttl = $authenticator->getExpiresAt()->getTimestamp() - (new DateTimeImmutable())->getTimestamp();
        LaravelCache::tags([self::TAG, $this->credsTag])->put(self::TOKEN_PREFIX . $key, $authenticator->getAccessToken(), $ttl);
        LaravelCache::tags([self::TAG, $this->credsTag])->put(self::EXPIRATION_PREFIX . $authenticator->getExpiresAt()->getTimestamp(), $ttl);
    }

    public function forget(string $key): void
    {
        LaravelCache::tags([self::TAG, $this->credsTag])->forget(self::TOKEN_PREFIX . $key);
        LaravelCache::tags([self::TAG, $this->credsTag])->forget(self::EXPIRATION_PREFIX . $key);
    }

    public function clearForCreds(): void
    {
        LaravelCache::tags([self::TAG, $this->credsTag])->flush();
    }

    public function clear(): void
    {
        LaravelCache::tags([self::TAG])->flush();
    }
}