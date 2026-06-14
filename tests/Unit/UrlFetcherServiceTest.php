<?php

namespace Tests\Unit;

use App\Services\UrlFetcherService;
use Tests\TestCase;

class UrlFetcherServiceTest extends TestCase
{
    private UrlFetcherService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(UrlFetcherService::class);
    }

    public function test_is_internal_ip_detects_loopback(): void
    {
        $this->assertTrue(UrlFetcherService::isInternalIp('127.0.0.1'));
    }

    public function test_is_internal_ip_detects_private_range(): void
    {
        $this->assertTrue(UrlFetcherService::isInternalIp('10.0.0.1'));
        $this->assertTrue(UrlFetcherService::isInternalIp('192.168.1.1'));
        $this->assertTrue(UrlFetcherService::isInternalIp('172.16.0.1'));
    }

    public function test_is_internal_ip_detects_cloud_metadata_endpoint(): void
    {
        // 169.254.169.254 is the link-local address used by AWS/GCP/Azure for
        // instance metadata — the classic SSRF target.
        $this->assertTrue(UrlFetcherService::isInternalIp('169.254.169.254'));
    }

    public function test_is_internal_ip_returns_false_for_public_addresses(): void
    {
        $this->assertFalse(UrlFetcherService::isInternalIp('8.8.8.8'));
        $this->assertFalse(UrlFetcherService::isInternalIp('1.1.1.1'));
    }

    public function test_fetch_rejects_invalid_url(): void
    {
        $result = $this->service->fetch('not-a-valid-url');

        $this->assertNull($result['title']);
        $this->assertNull($result['favicon_url']);
    }

    public function test_fetch_blocks_localhost_hostname(): void
    {
        $result = $this->service->fetch('http://localhost/admin');

        $this->assertNull($result['title']);
        $this->assertNull($result['favicon_url']);
    }

    public function test_fetch_blocks_cloud_metadata_hostname(): void
    {
        $result = $this->service->fetch('http://metadata.google.internal/computeMetadata/v1/');

        $this->assertNull($result['title']);
        $this->assertNull($result['favicon_url']);
    }

    public function test_fetch_returns_nulls_when_dns_resolution_fails(): void
    {
        // A TLD that will never resolve.
        $result = $this->service->fetch('https://this-domain-does-not-exist-zzz.invalid/');

        $this->assertNull($result['title']);
        $this->assertNull($result['favicon_url']);
    }
}
