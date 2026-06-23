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

namespace Sylius\Telemetry\Provider\Plugins;

use Composer\InstalledVersions;
use Sylius\Telemetry\DataProvider\DataProviderInterface;
use Sylius\Telemetry\DTO\Plugins\InstalledPluginsData;
use Sylius\Telemetry\DTO\Plugins\PluginData;
use Sylius\Telemetry\DTO\TelemetryDataInterface;

final class InstalledPluginsDataProvider implements DataProviderInterface
{
    private const PACKAGIST_NOTIFICATION_URL = 'https://packagist.org/downloads/';

    /** @var string|null */
    private $installedJsonPath;

    public function __construct(?string $installedJsonPath = null)
    {
        $this->installedJsonPath = $installedJsonPath;
    }

    public function provide(): TelemetryDataInterface
    {
        if (!class_exists(InstalledVersions::class)) {
            return new InstalledPluginsData();
        }

        $publicPackages = array_flip($this->getPublicPackageNames());
        $syliusPlugins = array_unique(InstalledVersions::getInstalledPackagesByType('sylius-plugin'));

        $plugins = [];
        foreach ($syliusPlugins as $name) {
            if (!isset($publicPackages[$name])) {
                continue;
            }

            $plugins[] = new PluginData(
                $name,
                InstalledVersions::getPrettyVersion($name) ?? 'unknown'
            );
        }

        return new InstalledPluginsData(...$plugins);
    }

    /** @return list<string> */
    private function getPublicPackageNames(): array
    {
        $installedJsonPath = $this->installedJsonPath !== null
            ? $this->installedJsonPath
            : $this->resolveInstalledJsonPath();

        $names = $this->readPackagistPackagesFrom($installedJsonPath);
        if ($names !== null) {
            return $names;
        }

        $fallback = $this->readPackagistPackagesFrom($this->resolveComposerLockPath($installedJsonPath));

        return $fallback !== null ? $fallback : [];
    }

    /**
     * Reads a Composer-shaped JSON file (installed.json or composer.lock) and returns the list of
     * package names whose `notification-url` points to Packagist. Both files share the same
     * top-level `packages` array shape.
     *
     * Returns null when the source is unavailable or malformed (so caller can try fallback);
     * returns an array (possibly empty) when the source parsed successfully.
     *
     * @return list<string>|null
     */
    private function readPackagistPackagesFrom(?string $path): ?array
    {
        if ($path === null || !file_exists($path) || !($content = file_get_contents($path))) {
            return null;
        }

        $data = json_decode($content, true);

        if (!is_array($data) || !is_array($data['packages'] ?? null)) {
            return null;
        }

        $names = [];
        foreach ($data['packages'] as $package) {
            if (!is_array($package)) {
                continue;
            }

            if (($package['notification-url'] ?? '') !== self::PACKAGIST_NOTIFICATION_URL) {
                continue;
            }

            $name = $package['name'] ?? null;
            if (is_string($name)) {
                $names[] = $name;
            }
        }

        return $names;
    }

    private function resolveInstalledJsonPath(): ?string
    {
        try {
            $fileName = (new \ReflectionClass(InstalledVersions::class))->getFileName();
        } catch (\ReflectionException $e) {
            return null;
        }

        if ($fileName === false) {
            return null;
        }

        return dirname($fileName) . '/installed.json';
    }

    private function resolveComposerLockPath(?string $installedJsonPath): ?string
    {
        if ($installedJsonPath === null) {
            return null;
        }

        // {projectDir}/vendor/composer/installed.json -> {projectDir}/composer.lock
        return dirname($installedJsonPath, 3) . '/composer.lock';
    }
}
