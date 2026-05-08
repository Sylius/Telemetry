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

namespace Sylius\Telemetry\Provider\Business;

use Doctrine\DBAL\Connection;
use Sylius\Telemetry\DataProvider\DataProviderInterface;
use Sylius\Telemetry\DTO\Business\CurrenciesData;
use Sylius\Telemetry\DTO\TelemetryDataInterface;
use Sylius\Telemetry\Query\TimeoutRunner;

final class CurrenciesDataProvider implements DataProviderInterface
{
    /** @var Connection */
    private $connection;

    /** @var TimeoutRunner */
    private $queryTimeoutRunner;

    public function __construct(Connection $connection, TimeoutRunner $queryTimeoutRunner)
    {
        $this->connection = $connection;
        $this->queryTimeoutRunner = $queryTimeoutRunner;
    }

    public function provide(): TelemetryDataInterface
    {
        try {
            $currencies = $this->queryTimeoutRunner->fetchFirstColumn(
                $this->connection,
                'SELECT code FROM sylius_currency'
            );

            return new CurrenciesData($currencies);
        } catch (\Throwable $e) {
            return new CurrenciesData([]);
        }
    }
}
