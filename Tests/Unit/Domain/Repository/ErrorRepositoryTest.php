<?php

declare(strict_types=1);

namespace HDNET\ErrorLog\Tests\Unit\Domain\Repository;

use HDNET\ErrorLog\Domain\Repository\ErrorRepository;
use HDNET\ErrorLog\Tests\Unit\AbstractTest;
use TYPO3\CMS\Extbase\Persistence\Generic\QuerySettingsInterface;
use TYPO3\CMS\Extbase\Persistence\PersistenceManagerInterface;
use TYPO3\CMS\Extbase\Persistence\QueryInterface;

/**
 * ErrorRepositoryTest.
 *
 * @internal
 */
#[\PHPUnit\Framework\Attributes\CoversNothing]
final class ErrorRepositoryTest extends AbstractTest
{
    public function testTableNameMatchesDatabaseSchema(): void
    {
        self::assertSame('tx_errorlog_domain_model_error', ErrorRepository::TABLE_NAME);
    }

    public function testCreateQueryIgnoresStoragePage(): void
    {
        $querySettings = $this->createMock(QuerySettingsInterface::class);
        $querySettings->expects(self::once())->method('setRespectStoragePage')->with(false);

        $query = $this->createMock(QueryInterface::class);
        $query->method('getQuerySettings')->willReturn($querySettings);

        $persistenceManager = $this->createMock(PersistenceManagerInterface::class);
        $persistenceManager->method('createQueryForType')->willReturn($query);

        $repository = new ErrorRepository();
        $repository->injectPersistenceManager($persistenceManager);

        self::assertSame($query, $repository->createQuery());
    }

    public function testFindLatestReturnsNothingForEmptyTable(): void
    {
        $this->createErrorDatabase();

        self::assertSame([], iterator_to_array((new ErrorRepository())->findLatest()));
    }

    public function testFindLatestGroupsByUriAndOrdersByCount(): void
    {
        $connection = $this->createErrorDatabase();
        foreach (['/a', '/b', '/a', '/c', '/b', '/a'] as $uri) {
            $connection->insert(ErrorRepository::TABLE_NAME, ['uri' => $uri, 'crdate' => 1, 'tstamp' => 1]);
        }

        $result = iterator_to_array((new ErrorRepository())->findLatest());

        self::assertSame([
            ['uri' => '/a', 'c' => 3],
            ['uri' => '/b', 'c' => 2],
            ['uri' => '/c', 'c' => 1],
        ], array_map(static fn(array $row): array => ['uri' => $row['uri'], 'c' => (int) $row['c']], $result));
    }

    public function testFindLatestIsLazy(): void
    {
        $this->createErrorDatabase();

        self::assertInstanceOf(\Generator::class, (new ErrorRepository())->findLatest());
    }
}
