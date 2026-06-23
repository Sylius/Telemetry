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

final class CurrenciesData implements TelemetryDataInterface
{
    /** @var list<string> */
    public $currencies;

    /** @param list<string> $currencies */
    public function __construct(array $currencies)
    {
        $this->currencies = $currencies;
    }

    public function normalize(): array
    {
        return [
            'currencies' => $this->currencies,
        ];
    }
}
