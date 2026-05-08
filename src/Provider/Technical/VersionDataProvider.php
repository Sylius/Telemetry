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

namespace Sylius\Telemetry\Provider\Technical;

use Composer\InstalledVersions;
use Sylius\Telemetry\DataProvider\DataProviderInterface;
use Sylius\Telemetry\DTO\Technical\VersionData;
use Sylius\Telemetry\DTO\TelemetryDataInterface;
use Symfony\Component\HttpKernel\Kernel;
use Twig\Environment;

final class VersionDataProvider implements DataProviderInterface
{
    /** @var string|null */
    private $syliusVersion;

    public function __construct(?string $syliusVersion = null)
    {
        $this->syliusVersion = $syliusVersion;
    }

    public function provide(): TelemetryDataInterface
    {
        return new VersionData(
            $this->syliusVersion ?? $this->getInstalledPackageVersion('sylius/sylius') ?? 'unknown',
            PHP_VERSION,
            Kernel::VERSION,
            $this->getInstalledPackageVersion('doctrine/orm'),
            class_exists(Environment::class) ? Environment::VERSION : null,
            $this->getApiPlatformVersion()
        );
    }

    private function getApiPlatformVersion(): ?string
    {
        $version = $this->getInstalledPackageVersion('api-platform/core');
        if ($version !== null) {
            return $version;
        }

        return $this->getInstalledPackageVersion('api-platform/symfony');
    }

    private function getInstalledPackageVersion(string $package): ?string
    {
        if (!class_exists(InstalledVersions::class)) {
            return null;
        }

        if (!InstalledVersions::isInstalled($package)) {
            return null;
        }

        return InstalledVersions::getPrettyVersion($package);
    }
}
