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

namespace Sylius\Telemetry\Tests\Unit\Mapper;

use PHPUnit\Framework\TestCase;
use Sylius\Telemetry\Mapper\ValueRangeMapper;

final class ValueRangeMapperTest extends TestCase
{
    /** @dataProvider gmvCases */
    public function testItMapsGmv(float $value, string $expected): void
    {
        self::assertSame($expected, ValueRangeMapper::mapGmv($value));
    }

    /** @return iterable<array{float, string}> */
    public static function gmvCases(): iterable
    {
        yield 'zero' => [0.0, '0-10K'];
        yield 'just below 10K' => [9999.99, '0-10K'];
        yield 'exactly 10K is in next bucket' => [10000.0, '10K-50K'];
        yield 'at 1M boundary' => [999999.0, '500K-1M'];
        yield 'above 50M' => [123456789.0, '50M+'];
    }

    /** @dataProvider aovCases */
    public function testItMapsAov(float $value, string $expected): void
    {
        self::assertSame($expected, ValueRangeMapper::mapAov($value));
    }

    /** @return iterable<array{float, string}> */
    public static function aovCases(): iterable
    {
        yield [0.0, '0-50'];
        yield [49.99, '0-50'];
        yield [50.0, '50-100'];
        yield [49999.0, '25K-50K'];
        yield [99999.0, '50K+'];
    }

    public function testItMapsCustomersCountOnMonthlyRanges(): void
    {
        self::assertSame('0-10', ValueRangeMapper::mapCustomersCount(0));
        self::assertSame('50-100', ValueRangeMapper::mapCustomersCount(50));
        self::assertSame('1K-5K', ValueRangeMapper::mapCustomersCount(1_500));
        self::assertSame('10K+', ValueRangeMapper::mapCustomersCount(2_000_000));
    }

    public function testItMapsPaymentsAndShipmentsOnMonthlyRanges(): void
    {
        self::assertSame('0-10', ValueRangeMapper::mapShipmentsCount(0));
        self::assertSame('50-100', ValueRangeMapper::mapShipmentsCount(50));
        self::assertSame('100-250', ValueRangeMapper::mapPaymentsCount(100));
        self::assertSame('10K+', ValueRangeMapper::mapPaymentsCount(2_000_000));
    }

    public function testItMapsCountRanges(): void
    {
        self::assertSame('0-100', ValueRangeMapper::mapProductsCount(0));
        self::assertSame('1K-10K', ValueRangeMapper::mapVariantsCount(1500));
        self::assertSame('2M+', ValueRangeMapper::mapProductsCount(3_000_000));
    }

    public function testItMapsVirtualVariantsOnDenserRanges(): void
    {
        self::assertSame('0-10', ValueRangeMapper::mapVirtualVariantsCount(0));
        self::assertSame('10-50', ValueRangeMapper::mapVirtualVariantsCount(10));
        self::assertSame('50-100', ValueRangeMapper::mapVirtualVariantsCount(99));
        self::assertSame('10K+', ValueRangeMapper::mapVirtualVariantsCount(3_000_000));
    }

    public function testItMapsAvgItems(): void
    {
        self::assertSame('0-5', ValueRangeMapper::mapAvgItems(0.0));
        self::assertSame('5-10', ValueRangeMapper::mapAvgItems(7.5));
        self::assertSame('20+', ValueRangeMapper::mapAvgItems(100.0));
    }
}
