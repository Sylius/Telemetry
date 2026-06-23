<?php

/*
 * This file is part of the Sylius package.
 *
 * (c) Sylius Sp. z o.o.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Sylius\Telemetry\Tests\Unit\Cache;

use PHPUnit\Framework\TestCase;
use Sylius\Telemetry\Cache\TelemetryCache;
use Symfony\Component\Cache\Adapter\ArrayAdapter;

final class TelemetryCacheTest extends TestCase
{
    public function testItShouldSendTelemetryWhenCacheIsEmpty(): void
    {
        $cache = new TelemetryCache(new ArrayAdapter());

        self::assertTrue($cache->shouldSendTelemetry());
    }

    public function testItShouldNotSendTelemetryAfterSuccess(): void
    {
        $cache = new TelemetryCache(new ArrayAdapter());

        $cache->storeSuccess('install-id-abc');

        self::assertFalse($cache->shouldSendTelemetry());
    }

    public function testItShouldRetryAfterFailureWhenRetryDelayPassed(): void
    {
        $adapter = new ArrayAdapter();
        $cache = new TelemetryCache($adapter);

        $cache->storeFailure('install-id-abc', ['foo' => 'bar']);

        self::assertFalse(
            $cache->shouldSendTelemetry(),
            'Right after a failure, the next attempt must wait for the retry delay.',
        );
    }

    public function testItStopsRetryingAfterMaxAttempts(): void
    {
        $cache = new TelemetryCache(new ArrayAdapter());

        $cache->storeFailure('install-id-abc', []);
        $cache->storeFailure('install-id-abc', []);
        $cache->storeFailure('install-id-abc', []);

        self::assertFalse($cache->shouldSendTelemetry());
    }

    public function testItRecordsRecentTrigger(): void
    {
        $cache = new TelemetryCache(new ArrayAdapter());

        self::assertFalse($cache->wasRecentlyTriggered());

        $cache->markAsRecentlyTriggered();

        self::assertTrue($cache->wasRecentlyTriggered());
    }

    public function testClearRemovesBothCacheEntries(): void
    {
        $cache = new TelemetryCache(new ArrayAdapter());

        $cache->storeSuccess('install-id');
        $cache->markAsRecentlyTriggered();

        $cache->clear();

        self::assertTrue($cache->shouldSendTelemetry());
        self::assertFalse($cache->wasRecentlyTriggered());
    }

    public function testItExposesCachedTelemetryDataOnFailure(): void
    {
        $cache = new TelemetryCache(new ArrayAdapter());
        $payload = ['business' => ['orders' => 42]];

        self::assertNull($cache->getCachedTelemetryData());

        $cache->storeFailure('install-id', $payload);

        self::assertSame($payload, $cache->getCachedTelemetryData());
    }
}
