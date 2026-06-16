<?php

declare(strict_types=1);

namespace Sylius\Telemetry\EventListener;

use Psr\Cache\CacheItemPoolInterface;
use Symfony\Component\Console\Event\ConsoleTerminateEvent;
use Symfony\Component\Console\Style\SymfonyStyle;

final class TelemetryNoticeListener
{
    private const CACHE_KEY = 'sylius_telemetry_notice_shown';

    /** @var CacheItemPoolInterface */
    private $cache;

    /** @var string */
    private $syliusVersion;

    public function __construct(CacheItemPoolInterface $cache, string $syliusVersion)
    {
        $this->cache = $cache;
        $this->syliusVersion = $syliusVersion;
    }

    public function onConsoleTerminate(ConsoleTerminateEvent $event): void
    {
        try {
            if ($event->getExitCode() !== 0) {
                return;
            }

            if ($this->isRunningInComposerScript()) {
                return;
            }

            $item = $this->cache->getItem(self::CACHE_KEY);
            if ($item->isHit()) {
                return;
            }

            $io = new SymfonyStyle($event->getInput(), $event->getOutput());
            $io->newLine();
            $io->block('Sylius Telemetry', null, 'bg=cyan;fg=black', '  ', true);
            $io->text($this->buildMessage());
            $io->newLine();

            $item->set(true);
            $this->cache->save($item);
        } catch (\Throwable $exception) {
        }
    }

    /** @return string[] */
    private function buildMessage(): array
    {
        $lines = [
            'Sylius collects <comment>anonymous usage data</> to help improve the platform.',
            'No personal or sensitive information is collected.',
            '',
            '  * Learn more: <comment>https://docs.sylius.com/the-book/configuration/telemetry</>',
        ];

        if ($this->shouldShowMigrationNotice()) {
            $lines = array_merge($lines, [
                '',
                'This release includes an optional database migration that adds an index to improve',
                'telemetry query performance. To run the migration:',
                '',
                '  <comment>php bin/console doctrine:migrations:migrate</>',
                '',
                'To skip, mark the migration as executed without running it:',
                '  MySQL:      <comment>php bin/console doctrine:migrations:version \'Sylius\\Bundle\\CoreBundle\\Migrations\\Version20251126120000\' --add --no-interaction</>',
                '  PostgreSQL: <comment>php bin/console doctrine:migrations:version \'Sylius\\Bundle\\CoreBundle\\Migrations\\Version20251126120001\' --add --no-interaction</>',
            ]);
        }

        return $lines;
    }

    private function shouldShowMigrationNotice(): bool
    {
        if (preg_match('/^(\d+)\.(\d+)/', $this->syliusVersion, $matches) !== 1) {
            return false;
        }

        $major = (int) $matches[1];
        $minor = (int) $matches[2];

        return $major > 1 || ($major === 1 && $minor >= 12);
    }

    private function isRunningInComposerScript(): bool
    {
        return getenv('COMPOSER_BINARY') !== false || isset($_SERVER['COMPOSER_BINARY']);
    }
}
