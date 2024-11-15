<?php

declare(strict_types=1);

namespace HDNET\ErrorLog\Controller;

use HDNET\ErrorLog\Domain\Repository\ErrorRepository;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Messaging\FlashMessage;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Mvc\Controller\ActionController;

class ErrorController extends ActionController
{

    public function __construct(protected \HDNET\ErrorLog\Domain\Repository\ErrorRepository $errorRepository)
    {
    }

    public function indexAction()
    {

        // @todo new backend layout!!!


        // @todo response
        $errors = $this->errorRepository->findLatest();

        // @todo viewHelper?!?!
        $errors = \array_map(function ($item) {
            $uriParts = \parse_url($item['uri']);
            $item['fields'] = [
                'source_host' => $uriParts['host'],
                'source_path' => $uriParts['path'],
            ];

            return $item;
        }, $errors);

        $this->view->assignMultiple([
            'errors' => $errors,
        ]);
    }

    public function deleteAction()
    {
        $this->addFlashMessage('Die 404-Error Liste wurde erfolgreich zurückgesetzt.', 'Liste geleert', FlashMessage::OK, true);

        $connection = GeneralUtility::makeInstance(ConnectionPool::class)->getConnectionForTable(ErrorRepository::TABLE_NAME);
        $connection->truncate(ErrorRepository::TABLE_NAME);

        $this->forward('index');
    }
}
