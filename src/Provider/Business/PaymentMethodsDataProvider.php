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
use Sylius\Telemetry\DTO\Business\PaymentMethodsData;
use Sylius\Telemetry\DTO\Business\PaymentProviderData;
use Sylius\Telemetry\DTO\TelemetryDataInterface;
use Sylius\Telemetry\Mapper\ValueRangeMapper;
use Sylius\Telemetry\Query\TimeoutRunner;

final class PaymentMethodsDataProvider implements DataProviderInterface
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
            $oneMonthAgo = (new \DateTimeImmutable('-1 month'))->format('Y-m-d H:i:s');

            $results = $this->queryTimeoutRunner->fetchAllAssociative(
                $this->connection,
                'SELECT pm.code, gc.factory_name, pm.is_enabled, COUNT(p.id) as payments_count
                 FROM sylius_payment_method pm
                 JOIN sylius_gateway_config gc ON pm.gateway_config_id = gc.id
                 LEFT JOIN sylius_payment p ON p.method_id = pm.id AND p.created_at >= :oneMonthAgo
                 WHERE EXISTS (SELECT 1 FROM sylius_payment_method_channels pmc WHERE pmc.payment_method_id = pm.id)
                 GROUP BY pm.id, pm.code, gc.factory_name, pm.is_enabled',
                ['oneMonthAgo' => $oneMonthAgo]
            );

            $providers = [];
            foreach ($results as $row) {
                $providers[] = new PaymentProviderData(
                    $row['code'],
                    $row['factory_name'] ?? '',
                    ValueRangeMapper::mapPaymentsCount((int) $row['payments_count']),
                    (bool) $row['is_enabled']
                );
            }

            return new PaymentMethodsData(...$providers);
        } catch (\Throwable $e) {
            return new PaymentMethodsData();
        }
    }
}
