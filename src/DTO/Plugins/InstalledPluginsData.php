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

namespace Sylius\Telemetry\DTO\Plugins;

use Sylius\Telemetry\DTO\TelemetryDataInterface;

final class InstalledPluginsData implements TelemetryDataInterface
{
    /** @var list<PluginData> */
    public $plugins;

    public function __construct(PluginData ...$plugins)
    {
        $this->plugins = $plugins;
    }

    /** @return list<array<string, string>> */
    public function normalize(): array
    {
        return array_map(
            static function (PluginData $plugin) {
                return $plugin->normalize();
            },
            $this->plugins
        );
    }
}
