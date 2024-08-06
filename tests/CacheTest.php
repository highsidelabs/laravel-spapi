<?php

declare(strict_types=1);

namespace HighsideLabs\LaravelSpApi\Tests;

use DateTimeImmutable;
use HighsideLabs\LaravelSpApi\Cache;
use HighsideLabs\LaravelSpApi\Models\Credentials;
use HighsideLabs\LaravelSpApi\Models\Seller;
use SellingPartnerApi\Authentication\AccessTokenAuthenticator;
use SellingPartnerApi\Contracts\TokenCache;

class CacheTest extends TestCase
{
    private TokenCache $cache;

    public function setUp(): void
    {
        parent::setUp();

        $seller = Seller::create(['name' => 'seller-1']);
        $creds = Credentials::create([
            'seller_id' => $seller->id,
            'selling_partner_id' => 'spid01',
            'region' => 'NA',
            'client_id' => 'client-id',
            'client_secret' => 'client-secret',
            'refresh_token' => 'refresh-token',
        ]);

        $this->cache = new Cache($creds->id);
    }

    public function testStoresToken(): void
    {
        $expiration = new DateTimeImmutable('1 hour');
        $token = new AccessTokenAuthenticator('access-token', expiresAt: $expiration);
        $this->cache->set('token-1', $token);

        $fetched = $this->cache->get('token-1');
        $this->assertEquals($token, $fetched);
    }

    public function testExpiresStoredToken(): void
    {
        $token = new AccessTokenAuthenticator('access-token', expiresAt: new \DateTimeImmutable('-1 hour'));
        $this->cache->set('token-1', $token);

        $fetched = $this->cache->get('token-1');
        $this->assertFalse($fetched);
    }

    public function testDeletesKey(): void
    {
        $this->cache->set('token-1', new AccessTokenAuthenticator('access-token', expiresAt: new \DateTimeImmutable('+1 hour')));
        $this->cache->forget('token-1');
        $this->assertFalse($this->cache->get('token-1'));
    }
}
