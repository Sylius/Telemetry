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

namespace Sylius\Telemetry\Tests\Unit;

use PHPUnit\Framework\TestCase;
use Sylius\Telemetry\Cache\TelemetryCacheInterface;
use Sylius\Telemetry\Sender\TelemetrySenderInterface;
use Sylius\Telemetry\TelemetryOrchestratorInterface;
use Sylius\Telemetry\TelemetrySendManager;
use Symfony\Component\HttpFoundation\Request;

final class TelemetrySendManagerTest extends TestCase
{
    public function testItDoesNothingWhenCacheRefuses(): void
    {
        $cache = $this->createMock(TelemetryCacheInterface::class);
        $cache->method('shouldSendTelemetry')->willReturn(false);

        $orchestrator = $this->createMock(TelemetryOrchestratorInterface::class);
        $orchestrator->expects(self::never())->method('getData');

        $sender = $this->createMock(TelemetrySenderInterface::class);
        $sender->expects(self::never())->method('send');

        $manager = new TelemetrySendManager($orchestrator, $cache, $sender);
        $manager->sendIfNeeded(Request::create('/admin'));
    }

    public function testItDoesNothingWhenInstallationIdIsEmpty(): void
    {
        $cache = $this->createMock(TelemetryCacheInterface::class);
        $cache->method('shouldSendTelemetry')->willReturn(true);
        $cache->method('getCachedTelemetryData')->willReturn(null);

        $orchestrator = $this->createMock(TelemetryOrchestratorInterface::class);
        $orchestrator->method('getData')->willReturn(['installation_id' => '']);

        $sender = $this->createMock(TelemetrySenderInterface::class);
        $sender->expects(self::never())->method('send');

        $manager = new TelemetrySendManager($orchestrator, $cache, $sender);
        $manager->sendIfNeeded(Request::create('/admin'));
    }

    public function testItStoresSuccessOnSuccessfulSend(): void
    {
        $payload = ['installation_id' => 'abc', 'business' => ['orders' => 10]];

        $cache = $this->createMock(TelemetryCacheInterface::class);
        $cache->method('shouldSendTelemetry')->willReturn(true);
        $cache->method('getCachedTelemetryData')->willReturn(null);
        $cache->expects(self::once())->method('storeSuccess')->with('abc');
        $cache->expects(self::never())->method('storeFailure');

        $orchestrator = $this->createMock(TelemetryOrchestratorInterface::class);
        $orchestrator->method('getData')->willReturn($payload);

        $sender = $this->createMock(TelemetrySenderInterface::class);
        $sender->method('send')->with($payload)->willReturn(true);

        $manager = new TelemetrySendManager($orchestrator, $cache, $sender);
        $manager->sendIfNeeded(Request::create('/admin'));
    }

    public function testItStoresFailureOnFailedSend(): void
    {
        $payload = ['installation_id' => 'abc'];

        $cache = $this->createMock(TelemetryCacheInterface::class);
        $cache->method('shouldSendTelemetry')->willReturn(true);
        $cache->method('getCachedTelemetryData')->willReturn(null);
        $cache->expects(self::never())->method('storeSuccess');
        $cache->expects(self::once())->method('storeFailure')->with('abc', $payload);

        $orchestrator = $this->createMock(TelemetryOrchestratorInterface::class);
        $orchestrator->method('getData')->willReturn($payload);

        $sender = $this->createMock(TelemetrySenderInterface::class);
        $sender->method('send')->willReturn(false);

        $manager = new TelemetrySendManager($orchestrator, $cache, $sender);
        $manager->sendIfNeeded(Request::create('/admin'));
    }

    public function testItPrefersCachedTelemetryDataOverFreshOrchestratorData(): void
    {
        $cached = ['installation_id' => 'cached-id', 'business' => ['orders' => 1]];

        $cache = $this->createMock(TelemetryCacheInterface::class);
        $cache->method('shouldSendTelemetry')->willReturn(true);
        $cache->method('getCachedTelemetryData')->willReturn($cached);

        $orchestrator = $this->createMock(TelemetryOrchestratorInterface::class);
        $orchestrator->expects(self::never())->method('getData');

        $sender = $this->createMock(TelemetrySenderInterface::class);
        $sender->expects(self::once())->method('send')->with($cached)->willReturn(true);

        $manager = new TelemetrySendManager($orchestrator, $cache, $sender);
        $manager->sendIfNeeded(Request::create('/admin'));
    }

    public function testItTreatsSenderExceptionsAsFailure(): void
    {
        $payload = ['installation_id' => 'abc'];

        $cache = $this->createMock(TelemetryCacheInterface::class);
        $cache->method('shouldSendTelemetry')->willReturn(true);
        $cache->method('getCachedTelemetryData')->willReturn(null);
        $cache->expects(self::once())->method('storeFailure')->with('abc', $payload);

        $orchestrator = $this->createMock(TelemetryOrchestratorInterface::class);
        $orchestrator->method('getData')->willReturn($payload);

        $sender = $this->createMock(TelemetrySenderInterface::class);
        $sender->method('send')->willThrowException(new \RuntimeException('network error'));

        $manager = new TelemetrySendManager($orchestrator, $cache, $sender);
        $manager->sendIfNeeded(Request::create('/admin'));
    }
}
