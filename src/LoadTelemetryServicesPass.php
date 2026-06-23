<?php

declare(strict_types=1);

namespace Sylius\Telemetry;

use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\XmlFileLoader;

final class LoadTelemetryServicesPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container): void
    {
        if (!$container->hasParameter('sylius_core.telemetry.url')) {
            return;
        }

        if (!$container->getParameter('sylius_core.telemetry.enabled')) {
            return;
        }

        $env = (string) $container->getParameter('kernel.environment');
        if (0 === strpos($env, 'dev') || 0 === strpos($env, 'test')) {
            return;
        }

        $loader = new XmlFileLoader($container, new FileLocator(__DIR__ . '/Resources/config'));
        $loader->load('services.xml');
    }
}
