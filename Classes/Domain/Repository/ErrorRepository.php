<?php

declare(strict_types=1);

namespace HDNET\ErrorLog\Domain\Repository;

use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Persistence\QueryInterface;
use TYPO3\CMS\Extbase\Persistence\Repository;

class ErrorRepository extends Repository
{
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
        $table = 'tx_site_domain_model_error';
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable($table);

        return (array) $queryBuilder
            ->select('uri')
            ->addSelectLiteral('COUNT(*) as c')
            ->from($table)
            ->groupBy('uri')
            ->orderBy('c', 'DESC')
            ->setMaxResults(1000)
            ->execute()
            ->fetchAll();
    }
}
