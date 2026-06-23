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

namespace Sylius\Telemetry\Provider\Technical;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\Persistence\ManagerRegistry;
use Sylius\Telemetry\DataProvider\DataProviderInterface;
use Sylius\Telemetry\DTO\Technical\DatabaseData;
use Sylius\Telemetry\DTO\TelemetryDataInterface;

final class DatabasePlatformDataProvider implements DataProviderInterface
{
    /** @var ManagerRegistry */
    private $managerRegistry;

    public function __construct(ManagerRegistry $managerRegistry)
    {
        $this->managerRegistry = $managerRegistry;
    }

    public function provide(): TelemetryDataInterface
    {
        $connection = $this->getConnection();
        if (null === $connection) {
            return new DatabaseData(null, null);
        }

        $databaseType = $this->guessDatabaseType($connection);

        return new DatabaseData(
            $databaseType,
            $this->detectDatabaseVersion($connection, $databaseType)
        );
    }

    private function getConnection(): ?Connection
    {
        try {
            return $this->managerRegistry->getConnection();
        } catch (\Throwable $e) {
            return null;
        }
    }

    private function guessDatabaseType(Connection $connection): ?string
    {
        try {
            $platform = $connection->getDatabasePlatform();
            if ($platform instanceof AbstractPlatform) {
                $type = $this->classifyPlatform($platform);
                if ($type !== null) {
                    return $type;
                }
            }
        } catch (\Throwable $e) {
        }

        $params = $connection->getParams();
        if (isset($params['driver'])) {
            return $this->mapDriverToDatabase((string) $params['driver']);
        }

        return null;
    }

    private function classifyPlatform(AbstractPlatform $platform): ?string
    {
        // MariaDB before MySQL — MariaDb1027Platform extends MySqlPlatform on DBAL 2.
        if ((class_exists('Doctrine\\DBAL\\Platforms\\MariaDBPlatform')
                && $platform instanceof \Doctrine\DBAL\Platforms\MariaDBPlatform)
            || (class_exists('Doctrine\\DBAL\\Platforms\\MariaDb1027Platform')
                && $platform instanceof \Doctrine\DBAL\Platforms\MariaDb1027Platform)
        ) {
            return 'mariadb';
        }

        if ((class_exists('Doctrine\\DBAL\\Platforms\\AbstractMySQLPlatform')
                && $platform instanceof \Doctrine\DBAL\Platforms\AbstractMySQLPlatform)
            || (class_exists('Doctrine\\DBAL\\Platforms\\MySqlPlatform')
                && $platform instanceof \Doctrine\DBAL\Platforms\MySqlPlatform)
        ) {
            return 'mysql';
        }

        if ((class_exists('Doctrine\\DBAL\\Platforms\\PostgreSQLPlatform')
                && $platform instanceof \Doctrine\DBAL\Platforms\PostgreSQLPlatform)
            || (class_exists('Doctrine\\DBAL\\Platforms\\PostgreSqlPlatform')
                && $platform instanceof \Doctrine\DBAL\Platforms\PostgreSqlPlatform)
        ) {
            return 'postgresql';
        }

        if (class_exists('Doctrine\\DBAL\\Platforms\\SqlitePlatform')
            && $platform instanceof \Doctrine\DBAL\Platforms\SqlitePlatform
        ) {
            return 'sqlite';
        }

        return null;
    }

    private function mapDriverToDatabase(string $driver): ?string
    {
        if (false !== strpos($driver, 'mysql') || false !== strpos($driver, 'mysqli')) {
            return 'mysql';
        }
        if (false !== strpos($driver, 'pgsql') || false !== strpos($driver, 'postgres')) {
            return 'postgresql';
        }
        if (false !== strpos($driver, 'sqlite')) {
            return 'sqlite';
        }
        if (false !== strpos($driver, 'sqlsrv') || false !== strpos($driver, 'mssql')) {
            return 'mssql';
        }
        if (false !== strpos($driver, 'oci') || false !== strpos($driver, 'oracle')) {
            return 'oracle';
        }

        return null;
    }

    private function detectDatabaseVersion(Connection $connection, ?string $databaseType): ?string
    {
        $params = $connection->getParams();
        $serverVersion = $params['serverVersion'] ?? null;
        if (is_string($serverVersion) && '' !== trim($serverVersion)) {
            return $serverVersion;
        }

        foreach ($this->getVersionQueries($databaseType) as $query) {
            try {
                $result = $connection->fetchOne($query);
                if (is_string($result) && '' !== trim($result)) {
                    return $result;
                }
            } catch (\Throwable $e) {
            }
        }

        return null;
    }

    /** @return list<string> */
    private function getVersionQueries(?string $databaseType): array
    {
        switch ($databaseType) {
            case 'postgresql':
                return [
                    'SHOW server_version',
                    "SELECT current_setting('server_version')",
                    'SELECT VERSION()',
                ];
            case 'mysql':
            case 'mariadb':
                return [
                    'SELECT VERSION()',
                ];
            case 'sqlite':
                return [
                    'SELECT sqlite_version()',
                ];
            case 'mssql':
                return [
                    'SELECT @@VERSION',
                    "SELECT SERVERPROPERTY('ProductVersion')",
                ];
            case 'oracle':
                return [
                    "SELECT BANNER FROM v\$version WHERE BANNER LIKE 'Oracle%'",
                    'SELECT version FROM v$instance',
                    "SELECT * FROM PRODUCT_COMPONENT_VERSION WHERE PRODUCT LIKE 'Oracle%'",
                ];
            default:
                return [
                    'SELECT VERSION()',
                ];
        }
    }
}
