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

final class PaymentMethodsData implements TelemetryDataInterface
{
    /** @var list<PaymentProviderData> */
    public $paymentProviders;

    public function __construct(PaymentProviderData ...$paymentProviders)
    {
        $this->paymentProviders = $paymentProviders;
    }

    public function normalize(): array
    {
        return [
            'payment_providers' => array_map(
                static function (PaymentProviderData $provider) {
                    return $provider->normalize();
                },
                $this->paymentProviders
            ),
        ];
    }
}
