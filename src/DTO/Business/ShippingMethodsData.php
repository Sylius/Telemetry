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

namespace Sylius\Telemetry\DTO\Business;

use Sylius\Telemetry\DTO\TelemetryDataInterface;

final class ShippingMethodsData implements TelemetryDataInterface
{
    /** @var list<ShippingProviderData> */
    public $shippingProviders;

    public function __construct(ShippingProviderData ...$shippingProviders)
    {
        $this->shippingProviders = $shippingProviders;
    }

    public function normalize(): array
    {
        return [
            'shipping_providers' => array_map(
                static function (ShippingProviderData $provider) {
                    return $provider->normalize();
                },
                $this->shippingProviders
            ),
        ];
    }
}
