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

namespace Sylius\Telemetry\Tests\Unit\EventListener;

use PHPUnit\Framework\TestCase;
use Sylius\Telemetry\EventListener\TelemetryNoticeListener;
use Symfony\Component\Cache\Adapter\ArrayAdapter;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Event\ConsoleTerminateEvent;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;

final class TelemetryNoticeListenerTest extends TestCase
{
    /** @var string|false */
    private $previousComposerBinary;

    /** @var mixed */
    private $previousServerComposerBinary;

    protected function setUp(): void
    {
        $this->previousComposerBinary = getenv('COMPOSER_BINARY');
        $this->previousServerComposerBinary = $_SERVER['COMPOSER_BINARY'] ?? null;

        putenv('COMPOSER_BINARY');
        unset($_SERVER['COMPOSER_BINARY']);
    }

    protected function tearDown(): void
    {
        if ($this->previousComposerBinary === false) {
            putenv('COMPOSER_BINARY');
        } else {
            putenv('COMPOSER_BINARY=' . $this->previousComposerBinary);
        }

        if ($this->previousServerComposerBinary !== null) {
            $_SERVER['COMPOSER_BINARY'] = $this->previousServerComposerBinary;
        }
    }

    public function testItShowsTheNoticeWithMigrationBlockFromSyliusOneTwelve(): void
    {
        $output = $this->runListener('1.12.25-DEV');

        self::assertStringContainsString('anonymous usage data', $output);
        self::assertStringContainsString('optional database migration', $output);
        self::assertStringContainsString('Version20251126120000', $output);
        self::assertStringContainsString('Version20251126120001', $output);
    }

    public function testItShowsTheNoticeWithoutMigrationBlockBelowSyliusOneTwelve(): void
    {
        $output = $this->runListener('1.11.5');

        self::assertStringContainsString('anonymous usage data', $output);
        self::assertStringNotContainsString('optional database migration', $output);
    }

    public function testItShowsTheNoticeOnlyOnce(): void
    {
        $cache = new ArrayAdapter();

        $first = $this->runListener('2.0.0', $cache);
        $second = $this->runListener('2.0.0', $cache);

        self::assertStringContainsString('anonymous usage data', $first);
        self::assertStringNotContainsString('anonymous usage data', $second);
    }

    public function testItDoesNotShowTheNoticeWhenTheCommandFailed(): void
    {
        $output = $this->runListener('2.0.0', null, 1);

        self::assertSame('', trim($output));
    }

    private function runListener(string $version, ?ArrayAdapter $cache = null, int $exitCode = 0): string
    {
        $listener = new TelemetryNoticeListener($cache ?? new ArrayAdapter(), $version);

        $output = new BufferedOutput();
        $event = new ConsoleTerminateEvent(new Command('app:test'), new ArrayInput([]), $output, $exitCode);

        $listener->onConsoleTerminate($event);

        return $output->fetch();
    }
}
