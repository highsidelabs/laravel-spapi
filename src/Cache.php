<?php

declare(strict_types=1);

namespace HighsideLabs\LaravelSpApi;

use DateTimeImmutable;
use Illuminate\Cache\TaggableStore;
use Illuminate\Support\Facades\Cache as LaravelCache;
use SellingPartnerApi\Authentication\AccessTokenAuthenticator;
use SellingPartnerApi\Contracts\TokenCache;

class Cache implements TokenCache
{
    private const TAG = 'spapi';

    private string $credsTag;

    public function __construct(int $credsId)
    {
        $this->credsTag = "creds$credsId";
    }

    public function get(string $key): AccessTokenAuthenticator|false
    {
        if (self::isTaggableCache()) {
            $token = LaravelCache::tags([self::TAG, $this->credsTag])->get($key);
        } else {
            $token = LaravelCache::get($key);
        }

        if (! $token) {
            return false;
        }

        return unserialize($token);
    }

    public function set(string $key, AccessTokenAuthenticator $authenticator): void
    {
        $ttl = $authenticator->getExpiresAt()->getTimestamp() - (new DateTimeImmutable)->getTimestamp();
        if (self::isTaggableCache()) {
            LaravelCache::tags([self::TAG, $this->credsTag])->put($key, serialize($authenticator), $ttl);
        } else {
            LaravelCache::put($key, serialize($authenticator), $ttl);
        }
    }

    public function forget(string $key): void
    {
        LaravelCache::tags([self::TAG, $this->credsTag])->forget($key);
    }

    public function clearForCreds(): void
    {
        if (self::isTaggableCache()) {
            LaravelCache::tags([self::TAG, $this->credsTag])->flush();
        }
    }

    public function clear(): void
    {
        if (self::isTaggableCache()) {
            LaravelCache::tags([self::TAG])->flush();
        }
    }

    private static function isTaggableCache(): bool
    {
        return LaravelCache::getStore() instanceof TaggableStore;
    }
}
