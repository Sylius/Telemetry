<?php

declare(strict_types=1);

namespace Sylius\Telemetry\Tests\Double;

use Sylius\Component\Core\Telemetry\Cache\TelemetryCacheInterface;

final class AlwaysSendTelemetryCache implements TelemetryCacheInterface
{
    public function shouldSendTelemetry(): bool
    {
        return true;
    }

    public function getCachedTelemetryData(): ?array
    {
        return ['installation_id' => 'test-installation'];
    }

    public function wasRecentlyTriggered(): bool
    {
        return false;
    }

    public function markAsRecentlyTriggered(): void
    {
    }

    public function storeSuccess(string $installationId): void
    {
    }

    public function storeFailure(string $installationId, array $telemetryData): void
    {
    }

    public function clear(): void
    {
    }
}
