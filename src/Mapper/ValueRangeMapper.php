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

namespace Sylius\Telemetry\Mapper;

final class ValueRangeMapper
{
    private const GMV_RANGES = [
        10000 => '0-10K',
        50000 => '10K-50K',
        100000 => '50K-100K',
        500000 => '100K-500K',
        1000000 => '500K-1M',
        5000000 => '1M-5M',
        10000000 => '5M-10M',
        50000000 => '10M-50M',
        \PHP_INT_MAX => '50M+',
    ];

    private const AOV_RANGES = [
        50 => '0-50',
        100 => '50-100',
        250 => '100-250',
        500 => '250-500',
        1000 => '500-1K',
        5000 => '1K-5K',
        10000 => '5K-10K',
        25000 => '10K-25K',
        50000 => '25K-50K',
        \PHP_INT_MAX => '50K+',
    ];

    private const COUNT_RANGES = [
        100 => '0-100',
        1000 => '100-1K',
        10000 => '1K-10K',
        100000 => '10K-100K',
        500000 => '100K-500K',
        1000000 => '500K-1M',
        2000000 => '1M-2M',
        \PHP_INT_MAX => '2M+',
    ];

    /**
     * Monthly new customers (`created_at >= -1 month`). Decoupled from the
     * payment/shipment monthly ranges so it can be re-tuned independently once
     * real monthly data is available. Boundaries are reasoned about scale, not
     * fitted to the legacy all-time CSV export.
     */
    private const MONTHLY_CUSTOMERS_RANGES = [
        10 => '0-10',
        25 => '10-25',
        50 => '25-50',
        100 => '50-100',
        250 => '100-250',
        500 => '250-500',
        1000 => '500-1K',
        5000 => '1K-5K',
        10000 => '5K-10K',
        \PHP_INT_MAX => '10K+',
    ];

    /**
     * Per-provider monthly counts (payments, shipments) live at a much smaller
     * scale than all-time totals, so the low end is denser and the ceiling lower.
     */
    private const MONTHLY_COUNT_RANGES = [
        10 => '0-10',
        25 => '10-25',
        50 => '25-50',
        100 => '50-100',
        250 => '100-250',
        500 => '250-500',
        1000 => '500-1K',
        5000 => '1K-5K',
        10000 => '5K-10K',
        \PHP_INT_MAX => '10K+',
    ];

    /**
     * Most stores have few or no virtual variants, so the low end is denser
     * than the shared COUNT_RANGES to keep resolution where the data sits.
     */
    private const VIRTUAL_VARIANTS_RANGES = [
        10 => '0-10',
        50 => '10-50',
        100 => '50-100',
        500 => '100-500',
        1000 => '500-1K',
        5000 => '1K-5K',
        10000 => '5K-10K',
        \PHP_INT_MAX => '10K+',
    ];

    private const AVG_ITEMS_RANGES = [
        5 => '0-5',
        10 => '5-10',
        20 => '10-20',
        \PHP_INT_MAX => '20+',
    ];

    /** @param int|float $value */
    public static function mapGmv($value): string
    {
        return self::mapToRange($value, self::GMV_RANGES);
    }

    /** @param int|float $value */
    public static function mapAov($value): string
    {
        return self::mapToRange($value, self::AOV_RANGES);
    }

    public static function mapProductsCount(int $value): string
    {
        return self::mapToRange($value, self::COUNT_RANGES);
    }

    public static function mapVariantsCount(int $value): string
    {
        return self::mapToRange($value, self::COUNT_RANGES);
    }

    public static function mapVirtualVariantsCount(int $value): string
    {
        return self::mapToRange($value, self::VIRTUAL_VARIANTS_RANGES);
    }

    public static function mapCustomersCount(int $value): string
    {
        return self::mapToRange($value, self::MONTHLY_CUSTOMERS_RANGES);
    }

    /** @param int|float $value */
    public static function mapAvgItems($value): string
    {
        return self::mapToRange($value, self::AVG_ITEMS_RANGES);
    }

    public static function mapShipmentsCount(int $value): string
    {
        return self::mapToRange($value, self::MONTHLY_COUNT_RANGES);
    }

    public static function mapPaymentsCount(int $value): string
    {
        return self::mapToRange($value, self::MONTHLY_COUNT_RANGES);
    }

    /**
     * @param int|float $value
     * @param array<int, string> $ranges
     */
    private static function mapToRange($value, array $ranges): string
    {
        foreach ($ranges as $threshold => $label) {
            if ($value < $threshold) {
                return $label;
            }
        }

        return end($ranges);
    }
}
