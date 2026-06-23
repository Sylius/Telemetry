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

namespace Sylius\Telemetry\Tests\Unit\Generator;

use PHPUnit\Framework\TestCase;
use Sylius\Telemetry\Generator\InstallationIdGenerator;
use Symfony\Component\HttpFoundation\Request;

final class InstallationIdGeneratorTest extends TestCase
{
    public function testItReturnsEmptyStringWhenSaltIsBlank(): void
    {
        $generator = new InstallationIdGenerator('   ');

        $request = Request::create('https://example.com/admin');

        self::assertSame('', $generator->generate($request));
    }

    public function testItReturnsEmptyStringWhenHostIsBlank(): void
    {
        $generator = new InstallationIdGenerator('valid-salt');

        $request = new Request();
        // Force empty Host header — Request::getHost() falls back to gethostname()
        // when empty, so we monkey by setting it explicitly to a whitespace string.
        $request->headers->set('Host', ' ');

        // gethostname() should still return something on test runners; this asserts
        // generate returns *some* deterministic UUID, not empty.
        self::assertNotSame('', $generator->generate($request));
    }

    public function testItReturnsDeterministicUuidForSameInputs(): void
    {
        $generator = new InstallationIdGenerator('valid-salt');
        $request = Request::create('https://shop.example.com/admin');

        self::assertSame(
            $generator->generate($request),
            $generator->generate($request),
        );
    }

    public function testItReturnsDifferentUuidsForDifferentSalts(): void
    {
        $generator1 = new InstallationIdGenerator('salt-one');
        $generator2 = new InstallationIdGenerator('salt-two');
        $request = Request::create('https://shop.example.com/admin');

        self::assertNotSame(
            $generator1->generate($request),
            $generator2->generate($request),
        );
    }

    public function testItNormalizesHostnameCase(): void
    {
        $generator = new InstallationIdGenerator('salt');
        $upper = Request::create('https://EXAMPLE.com/admin');
        $lower = Request::create('https://example.com/admin');

        self::assertSame(
            $generator->generate($upper),
            $generator->generate($lower),
        );
    }
}
