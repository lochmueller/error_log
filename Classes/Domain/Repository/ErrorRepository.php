<?php

declare(strict_types=1);

namespace HDNET\ErrorLog\Domain\Repository;

use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Persistence\QueryInterface;
use TYPO3\CMS\Extbase\Persistence\Repository;

class ErrorRepository extends Repository
{

    public const TABLE_NAME = 'tx_errorlog_domain_model_error';

    /**
     * @return QueryInterface
     */
    public function createQuery()
    {
        $query = parent::createQuery();
        $query->getQuerySettings()->setRespectStoragePage(false);

        return $query;
    }

    public function findLatest(): array
    {
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable(self::TABLE_NAME);

        // @todo migration to iterator

        return (array) $queryBuilder
            ->select('uri')
            ->addSelectLiteral('COUNT(*) as c')
            ->from(self::TABLE_NAME)
            ->groupBy('uri')
            ->orderBy('c', 'DESC')
            ->setMaxResults(1000)
            ->executeQuery()
            ->fetchAllAssociative();
    }
}
