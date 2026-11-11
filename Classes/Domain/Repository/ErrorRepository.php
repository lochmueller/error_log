<?php

declare(strict_types=1);

namespace HDNET\ErrorLog\Domain\Repository;

use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\QueryBuilder;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Persistence\QueryInterface;
use TYPO3\CMS\Extbase\Persistence\Repository;

class ErrorRepository extends Repository
{
    public const TABLE_NAME = 'tx_errorlog_domain_model_error';

    /**
     * Maximum amount of URIs that are listed in the backend module.
     */
    public const MAX_RESULTS = 1000;

    /**
     * Amount of URIs that are passed to a single user agent lookup.
     */
    protected const URI_CHUNK_SIZE = 100;

    /**
     * @return QueryInterface
     */
    public function createQuery()
    {
        $query = parent::createQuery();
        $query->getQuerySettings()->setRespectStoragePage(false);

        return $query;
    }

    /**
     * Most often requested URIs including the user agents that requested them.
     *
     * @return iterable<int, array{uri: string, c: int, agents: array<int, array{agent: string, c: int}>}>
     */
    public function findLatest(): iterable
    {
        $errors = [];
        foreach ($this->findMostRequestedUris() as $row) {
            $uri = (string) $row['uri'];
            $errors[$uri] = [
                'uri' => $uri,
                'c' => (int) $row['c'],
                'agents' => [],
            ];
        }

        if ([] === $errors) {
            return;
        }

        foreach ($this->findUserAgents(\array_keys($errors)) as $row) {
            $userAgent = \trim((string) ($row['user_agent'] ?? ''));
            if ('' === $userAgent) {
                continue;
            }
            $errors[(string) $row['uri']]['agents'][] = [
                'agent' => $userAgent,
                'c' => (int) $row['c'],
            ];
        }

        yield from \array_values($errors);
    }

    /**
     * @return iterable<int, array<string, mixed>>
     */
    protected function findMostRequestedUris(): iterable
    {
        $queryBuilder = $this->createQueryBuilder();

        yield from $queryBuilder
            ->select('uri')
            ->addSelectLiteral('COUNT(*) as c')
            ->from(self::TABLE_NAME)
            ->groupBy('uri')
            ->orderBy('c', 'DESC')
            ->setMaxResults(self::MAX_RESULTS)
            ->executeQuery()
            ->iterateAssociative();
    }

    /**
     * @param array<int, string> $uris
     *
     * @return iterable<int, array<string, mixed>>
     */
    protected function findUserAgents(array $uris): iterable
    {
        foreach (\array_chunk($uris, self::URI_CHUNK_SIZE) as $chunk) {
            $queryBuilder = $this->createQueryBuilder();

            yield from $queryBuilder
                ->select('uri', 'user_agent')
                ->addSelectLiteral('COUNT(*) as c')
                ->from(self::TABLE_NAME)
                ->where($queryBuilder->expr()->in('uri', $queryBuilder->createNamedParameter($chunk, Connection::PARAM_STR_ARRAY)))
                ->groupBy('uri', 'user_agent')
                ->orderBy('c', 'DESC')
                ->executeQuery()
                ->iterateAssociative();
        }
    }

    protected function createQueryBuilder(): QueryBuilder
    {
        return GeneralUtility::makeInstance(ConnectionPool::class)
            ->getQueryBuilderForTable(self::TABLE_NAME);
    }
}
