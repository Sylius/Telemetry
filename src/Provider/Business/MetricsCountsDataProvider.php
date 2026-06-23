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
use Sylius\Telemetry\DTO\Business\MetricsCountsData;
use Sylius\Telemetry\DTO\TelemetryDataInterface;
use Sylius\Telemetry\Mapper\ValueRangeMapper;
use Sylius\Telemetry\Query\TimeoutRunner;

final class MetricsCountsDataProvider implements DataProviderInterface
{
    // Mirrors Sylius\Component\Core\OrderCheckoutStates::STATE_COMPLETED.
    private const ORDER_CHECKOUT_STATE_COMPLETED = 'completed';

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

            $counts = $this->queryTimeoutRunner->fetchAssociative(
                $this->connection,
                'SELECT
                    (SELECT COUNT(id) FROM sylius_customer WHERE created_at >= :oneMonthAgo) as customers_count,
                    (SELECT COUNT(id) FROM sylius_product) as products_count,
                    (SELECT COUNT(id) FROM sylius_product_variant) as product_variants_count,
                    (SELECT COUNT(id) FROM sylius_product_variant WHERE shipping_required = false) as virtual_product_variants_count,
                    (SELECT COUNT(id) FROM sylius_order
                        WHERE checkout_state = :completedState
                          AND checkout_completed_at >= :oneMonthAgo) as orders_count,
                    (SELECT COUNT(id) FROM sylius_channel WHERE enabled = true) as channels_count',
                [
                    'completedState' => self::ORDER_CHECKOUT_STATE_COMPLETED,
                    'oneMonthAgo' => $oneMonthAgo,
                ]
            );

            if (!is_array($counts)) {
                return $this->emptyDto();
            }

            return new MetricsCountsData(
                ValueRangeMapper::mapCustomersCount((int) $counts['customers_count']),
                ValueRangeMapper::mapProductsCount((int) $counts['products_count']),
                ValueRangeMapper::mapVariantsCount((int) $counts['product_variants_count']),
                ValueRangeMapper::mapVirtualVariantsCount((int) $counts['virtual_product_variants_count']),
                (int) $counts['orders_count'],
                (int) $counts['channels_count']
            );
        } catch (\Throwable $e) {
            return $this->emptyDto();
        }
    }

    private function emptyDto(): MetricsCountsData
    {
        return new MetricsCountsData(
            ValueRangeMapper::mapCustomersCount(0),
            ValueRangeMapper::mapProductsCount(0),
            ValueRangeMapper::mapVariantsCount(0),
            ValueRangeMapper::mapVirtualVariantsCount(0),
            0,
            0
        );
    }
}
