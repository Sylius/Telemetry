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

final class CountriesData implements TelemetryDataInterface
{
    /** @var list<string> */
    public $countries;

    /** @param list<string> $countries */
    public function __construct(array $countries)
    {
        $this->countries = $countries;
    }

    /** @return array<string, list<string>> */
    public function normalize(): array
    {
        return [
            'countries' => $this->countries,
        ];
    }
}
