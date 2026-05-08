<?php

declare(strict_types=1);

namespace Sylius\Telemetry;

use Sylius\Component\Core\Telemetry\TelemetrySendManagerInterface;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\XmlFileLoader;

class TelemetryCompilerPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container): void
    {
        if ($container->has('sylius.telemetry.send_manager')) {
            return;
        }

        if (!$this->isTelemetrySupported()) {
            return;
        }

        $env = (string) $container->getParameter('kernel.environment');
        if (str_starts_with($env, 'dev') || str_starts_with($env, 'test')) {
            return;
        }

        $loader = new XmlFileLoader($container, new FileLocator(__DIR__ . '/Resources/config'));
        $loader->load('services.xml');
    }

    protected function isTelemetrySupported(): bool
    {
        return interface_exists(TelemetrySendManagerInterface::class)
            || interface_exists(\Sylius\Telemetry\TelemetrySendManagerInterface::class);
    }
}
