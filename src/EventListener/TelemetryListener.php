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

namespace Sylius\Telemetry\EventListener;

use Sylius\Telemetry\Cache\TelemetryCacheInterface;
use Sylius\Telemetry\TelemetrySendManagerInterface;
use Symfony\Component\HttpKernel\Event\TerminateEvent;

class TelemetryListener
{
    /** @var TelemetrySendManagerInterface */
    private $telemetrySendManager;

    /** @var TelemetryCacheInterface */
    private $telemetryCache;

    /** @var string */
    private $adminApiPrefix;

    public function __construct(
        TelemetrySendManagerInterface $telemetrySendManager,
        TelemetryCacheInterface $telemetryCache,
        string $adminApiPrefix
    ) {
        $this->telemetrySendManager = $telemetrySendManager;
        $this->telemetryCache = $telemetryCache;
        $this->adminApiPrefix = $adminApiPrefix;
    }

    public function onAdminAccess(TerminateEvent $event): void
    {
        $request = $event->getRequest();

        if (!$this->isAdminRequest($request->attributes->get('_route'), $request->getPathInfo())) {
            return;
        }

        if ($this->telemetryCache->wasRecentlyTriggered()) {
            return;
        }

        try {
            $this->telemetryCache->markAsRecentlyTriggered();
            $this->telemetrySendManager->sendIfNeeded($request);
        } catch (\Throwable $e) {
        }
    }

    private function isAdminRequest(?string $route, string $path): bool
    {
        if ($route !== null && 0 === strpos($route, 'sylius_admin_')) {
            return true;
        }

        if (0 === strpos($path, $this->adminApiPrefix)) {
            return true;
        }

        return false;
    }
}
