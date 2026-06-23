<?php

declare(strict_types=1);

namespace Sylius\Telemetry\Tests\Unit;

use PHPUnit\Framework\TestCase;
use Sylius\Telemetry\TelemetryCompilerPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;

final class TelemetryCompilerPassTest extends TestCase
{
    public function testItSkipsWhenTelemetryServicesAlreadyRegistered(): void
    {
        $container = $this->createContainer('prod');
        $container->register('sylius.telemetry.send_manager');

        (new TelemetryCompilerPass())->process($container);

        // No additional services loaded — send_manager was already there
        $this->assertTrue($container->has('sylius.telemetry.send_manager'));
        $this->assertFalse($container->has('sylius.telemetry.sender'));
    }

    public function testItSkipsInDevEnvironment(): void
    {
        $container = $this->createContainer('dev');

        (new TelemetryCompilerPass())->process($container);

        $this->assertFalse($container->has('sylius.telemetry.send_manager'));
    }

    public function testItSkipsInTestEnvironment(): void
    {
        $container = $this->createContainer('test');

        (new TelemetryCompilerPass())->process($container);

        $this->assertFalse($container->has('sylius.telemetry.send_manager'));
    }

    public function testItSkipsWhenTelemetryIsNotSupported(): void
    {
        $container = $this->createContainer('prod');
        $this->setTelemetryParameters($container);

        $pass = new class () extends TelemetryCompilerPass {
            protected function isTelemetrySupported(): bool
            {
                return false;
            }
        };
        $pass->process($container);

        $this->assertFalse($container->has('sylius.telemetry.send_manager'));
    }

    public function testItLoadsTelemetryServicesInProd(): void
    {
        $container = $this->createContainer('prod');
        $this->setTelemetryParameters($container);

        (new TelemetryCompilerPass())->process($container);

        $this->assertTrue($container->has('sylius.telemetry.send_manager'));
        $this->assertTrue($container->has('sylius.telemetry.sender'));
        $this->assertTrue($container->has('sylius.telemetry.listener'));
    }

    public function testItLoadsTelemetryServicesInStagingEnvironment(): void
    {
        $container = $this->createContainer('staging');
        $this->setTelemetryParameters($container);

        (new TelemetryCompilerPass())->process($container);

        $this->assertTrue($container->has('sylius.telemetry.send_manager'));
    }

    private function createContainer(string $environment): ContainerBuilder
    {
        $container = new ContainerBuilder();
        $container->setParameter('kernel.environment', $environment);

        return $container;
    }

    private function setTelemetryParameters(ContainerBuilder $container): void
    {
        $container->setParameter('sylius_core.telemetry.salt', 'test-salt');
        $container->setParameter('sylius_core.telemetry.url', 'https://prism.sylius.com/telemetry');
        $container->setParameter('sylius_core.telemetry.technical', true);
        $container->setParameter('sylius_core.telemetry.plugins', true);
        $container->setParameter('sylius_core.telemetry.business', true);
        $container->setParameter('sylius.security.api_admin_route', '/admin');
        $container->setParameter('kernel.project_dir', '/tmp');
        $container->setParameter('locale', 'en');
    }
}
