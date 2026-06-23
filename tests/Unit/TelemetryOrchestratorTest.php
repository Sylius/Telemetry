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
use Sylius\Telemetry\Collector\TelemetryDataCollectorInterface;
use Sylius\Telemetry\Generator\InstallationIdGeneratorInterface;
use Sylius\Telemetry\TelemetryOrchestrator;
use Symfony\Component\HttpFoundation\Request;

final class TelemetryOrchestratorTest extends TestCase
{
    public function testItReturnsBaseEnvelopeWithSchemaVersion(): void
    {
        $orchestrator = new TelemetryOrchestrator(
            $this->stubGenerator('install-xyz'),
            [],
        );

        $data = $orchestrator->getData(Request::create('https://example.com'));

        self::assertSame(3, $data['schema_version']);
        self::assertSame('install-xyz', $data['installation_id']);
        self::assertArrayHasKey('collected_at', $data);
        self::assertArrayHasKey('period', $data);
        self::assertArrayHasKey('start', $data['period']);
        self::assertArrayHasKey('end', $data['period']);
    }

    public function testItIncludesCollectorOutputUnderItsName(): void
    {
        $collector = $this->stubCollector('business', true, ['orders' => 42]);

        $orchestrator = new TelemetryOrchestrator(
            $this->stubGenerator('install'),
            [$collector],
        );

        $data = $orchestrator->getData(Request::create('https://example.com'));

        self::assertSame(['orders' => 42], $data['business']);
    }

    public function testItSkipsDisabledCollectors(): void
    {
        $collector = $this->stubCollector('plugins', false, ['plugins' => []]);

        $orchestrator = new TelemetryOrchestrator(
            $this->stubGenerator('install'),
            [$collector],
        );

        $data = $orchestrator->getData(Request::create('https://example.com'));

        self::assertArrayNotHasKey('plugins', $data);
    }

    public function testItSwallowsExceptionsFromIndividualCollectors(): void
    {
        $broken = $this->createMock(TelemetryDataCollectorInterface::class);
        $broken->method('isEnabled')->willReturn(true);
        $broken->method('getName')->willReturn('broken');
        $broken->method('collect')->willThrowException(new \RuntimeException('boom'));

        $orchestrator = new TelemetryOrchestrator(
            $this->stubGenerator('install'),
            [$broken],
        );

        $data = $orchestrator->getData(Request::create('https://example.com'));

        self::assertArrayNotHasKey('broken', $data);
        self::assertSame('install', $data['installation_id']);
    }

    private function stubGenerator(string $value): InstallationIdGeneratorInterface
    {
        $stub = $this->createMock(InstallationIdGeneratorInterface::class);
        $stub->method('generate')->willReturn($value);

        return $stub;
    }

    /** @param array<string, mixed> $payload */
    private function stubCollector(string $name, bool $enabled, array $payload): TelemetryDataCollectorInterface
    {
        $stub = $this->createMock(TelemetryDataCollectorInterface::class);
        $stub->method('getName')->willReturn($name);
        $stub->method('isEnabled')->willReturn($enabled);
        $stub->method('collect')->willReturn($payload);

        return $stub;
    }
}
