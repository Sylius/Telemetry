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

namespace Sylius\Telemetry\Query;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Platforms\AbstractPlatform;

final class TimeoutRunner
{
    /** @var int */
    private $timeoutMs;

    public function __construct(int $timeoutMs = 60000)
    {
        $this->timeoutMs = $timeoutMs;
    }

    /**
     * @param array<string, mixed> $params
     *
     * @return list<array<string, mixed>>
     */
    public function fetchAllAssociative(Connection $connection, string $sql, array $params = []): array
    {
        $sql = $this->applyTimeout($connection, $sql);

        return $this->executeInTimeoutContext($connection, function () use ($connection, $sql, $params) {
            return $connection->fetchAllAssociative($sql, $params);
        });
    }

    /**
     * @param array<string, mixed> $params
     *
     * @return array<string, mixed>|false
     */
    public function fetchAssociative(Connection $connection, string $sql, array $params = [])
    {
        $sql = $this->applyTimeout($connection, $sql);

        return $this->executeInTimeoutContext($connection, function () use ($connection, $sql, $params) {
            return $connection->fetchAssociative($sql, $params);
        });
    }

    /**
     * @param array<string, mixed> $params
     *
     * @return list<mixed>
     */
    public function fetchFirstColumn(Connection $connection, string $sql, array $params = []): array
    {
        $sql = $this->applyTimeout($connection, $sql);

        return $this->executeInTimeoutContext($connection, function () use ($connection, $sql, $params) {
            return $connection->fetchFirstColumn($sql, $params);
        });
    }

    private function applyTimeout(Connection $connection, string $sql): string
    {
        $platform = $connection->getDatabasePlatform();

        // MariaDB: SET STATEMENT max_statement_time=X FOR SELECT ... (per-statement, seconds)
        // Must check MariaDB before MySQL because MariaDb1027Platform extends MySqlPlatform on DBAL 2.
        if (self::isMariaDB($platform)) {
            $timeoutSeconds = $this->timeoutMs / 1000;

            return sprintf('SET STATEMENT max_statement_time=%s FOR %s', $timeoutSeconds, $sql);
        }

        // MySQL: handled in executeInTimeoutContext via the max_execution_time session var.
        // The /*+ MAX_EXECUTION_TIME(X) */ optimizer hint is silently NOT enforced on MySQL 8
        // for these queries (verified on 8.0.45: a 130s query ran to completion under a 5s
        // hint), whereas the session variable aborts correctly.
        // PostgreSQL: handled in executeInTimeoutContext via SET LOCAL inside a transaction.
        return $sql;
    }

    /**
     * @template T
     *
     * @param callable(): T $callback
     *
     * @return T
     */
    private function executeInTimeoutContext(Connection $connection, callable $callback)
    {
        $platform = $connection->getDatabasePlatform();

        // PostgreSQL: SET LOCAL only works within a transaction and resets on COMMIT/ROLLBACK.
        if (self::isPostgreSQL($platform)) {
            $connection->beginTransaction();

            try {
                $connection->executeStatement(sprintf("SET LOCAL statement_timeout = '%d'", $this->timeoutMs));
                $result = $callback();
                $connection->commit();

                return $result;
            } catch (\Throwable $e) {
                $connection->rollBack();

                throw $e;
            }
        }

        // MySQL (not MariaDB): the optimizer hint is unreliable, so use the session variable,
        // which is honored. Reset afterwards because the connection may be reused.
        if (self::isMySQL($platform) && !self::isMariaDB($platform)) {
            $connection->executeStatement('SET max_execution_time = ' . (int) $this->timeoutMs);

            try {
                return $callback();
            } finally {
                $connection->executeStatement('SET max_execution_time = 0');
            }
        }

        // MariaDB: timeout is embedded in the SQL itself (SET STATEMENT ... FOR).
        return $callback();
    }

    private static function isMariaDB(AbstractPlatform $platform): bool
    {
        return (class_exists('Doctrine\\DBAL\\Platforms\\MariaDBPlatform')
                && $platform instanceof \Doctrine\DBAL\Platforms\MariaDBPlatform)
            || (class_exists('Doctrine\\DBAL\\Platforms\\MariaDb1027Platform')
                && $platform instanceof \Doctrine\DBAL\Platforms\MariaDb1027Platform);
    }

    private static function isMySQL(AbstractPlatform $platform): bool
    {
        return (class_exists('Doctrine\\DBAL\\Platforms\\AbstractMySQLPlatform')
                && $platform instanceof \Doctrine\DBAL\Platforms\AbstractMySQLPlatform)
            || (class_exists('Doctrine\\DBAL\\Platforms\\MySqlPlatform')
                && $platform instanceof \Doctrine\DBAL\Platforms\MySqlPlatform);
    }

    private static function isPostgreSQL(AbstractPlatform $platform): bool
    {
        return (class_exists('Doctrine\\DBAL\\Platforms\\PostgreSQLPlatform')
                && $platform instanceof \Doctrine\DBAL\Platforms\PostgreSQLPlatform)
            || (class_exists('Doctrine\\DBAL\\Platforms\\PostgreSqlPlatform')
                && $platform instanceof \Doctrine\DBAL\Platforms\PostgreSqlPlatform);
    }
}
