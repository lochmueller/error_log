<?php

declare(strict_types=1);

namespace HDNET\ErrorLog\Tests\Unit;

use Doctrine\DBAL\DriverManager;
use HDNET\ErrorLog\Domain\Repository\ErrorRepository;
use HDNET\ErrorLog\Tests\Unit\Fixtures\SqliteConnection;
use Symfony\Component\DependencyInjection\Container;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

/**
 * Abstract test.
 *
 * @internal
 */
#[\PHPUnit\Framework\Attributes\CoversNothing]
abstract class AbstractTest extends UnitTestCase
{
    protected bool $resetSingletonInstances = true;

    private bool $databaseCreated = false;

    private bool $containerRegistered = false;

    /**
     * Register an in-memory SQLite database as default connection and execute the given SQL statements.
     */
    protected function createDatabase(string ...$statements): Connection
    {
        /** @var SqliteConnection $connection */
        $connection = DriverManager::getConnection([
            'driver' => 'pdo_sqlite',
            'memory' => true,
            'wrapperClass' => SqliteConnection::class,
        ]);
        foreach ($statements as $statement) {
            $connection->executeStatement($statement);
        }

        $GLOBALS['TYPO3_CONF_VARS']['DB']['Connections']['Default'] = ['driver' => 'pdo_sqlite', 'memory' => true];
        (new \ReflectionProperty(ConnectionPool::class, 'connections'))->setValue(null, ['Default' => $connection]);
        $this->databaseCreated = true;

        return $connection;
    }

    /**
     * Register an in-memory SQLite database that contains the error log table.
     */
    protected function createErrorDatabase(): Connection
    {
        return $this->createDatabase(
            'CREATE TABLE ' . ErrorRepository::TABLE_NAME . ' (
                uid INTEGER PRIMARY KEY AUTOINCREMENT,
                pid INTEGER NOT NULL DEFAULT 0,
                uri TEXT NOT NULL DEFAULT "",
                crdate INTEGER NOT NULL DEFAULT 0,
                tstamp INTEGER NOT NULL DEFAULT 0
            )'
        );
    }

    /**
     * Register a DI container with the given services (service id => instance) for GeneralUtility::makeInstance().
     *
     * @param array<string, object> $services
     */
    protected function registerContainerServices(array $services): void
    {
        $container = new Container();
        foreach ($services as $id => $service) {
            $container->set($id, $service);
        }
        GeneralUtility::setContainer($container);
        $this->containerRegistered = true;
    }

    protected function tearDown(): void
    {
        if ($this->databaseCreated) {
            (new ConnectionPool())->resetConnections();
        }
        $containerRegistered = $this->containerRegistered;

        parent::tearDown();

        if ($containerRegistered) {
            // The container is not reset by "resetSingletonInstances"
            GeneralUtility::purgeInstances();
        }
    }
}
