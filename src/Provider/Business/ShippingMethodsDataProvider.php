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
use Sylius\Telemetry\DTO\Business\ShippingMethodsData;
use Sylius\Telemetry\DTO\Business\ShippingProviderData;
use Sylius\Telemetry\DTO\TelemetryDataInterface;
use Sylius\Telemetry\Mapper\ValueRangeMapper;
use Sylius\Telemetry\Query\TimeoutRunner;

final class ShippingMethodsDataProvider implements DataProviderInterface
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
                'SELECT sm.code, sm.calculator, sm.is_enabled, COUNT(s.id) as shipments_count
                 FROM sylius_shipping_method sm
                 LEFT JOIN sylius_shipment s ON s.method_id = sm.id AND s.created_at >= :oneMonthAgo
                 WHERE sm.archived_at IS NULL
                   AND EXISTS (SELECT 1 FROM sylius_shipping_method_channels smc WHERE smc.shipping_method_id = sm.id)
                 GROUP BY sm.id, sm.code, sm.calculator, sm.is_enabled',
                ['oneMonthAgo' => $oneMonthAgo]
            );

            $providers = [];
            foreach ($results as $row) {
                $providers[] = new ShippingProviderData(
                    $row['code'],
                    $row['calculator'] ?? '',
                    ValueRangeMapper::mapShipmentsCount((int) $row['shipments_count']),
                    (bool) $row['is_enabled']
                );
            }

            return new ShippingMethodsData(...$providers);
        } catch (\Throwable $e) {
            return new ShippingMethodsData();
        }
    }
}
