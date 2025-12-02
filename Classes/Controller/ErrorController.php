<?php

declare(strict_types=1);

namespace HDNET\ErrorLog\Controller;

use HDNET\ErrorLog\Domain\Repository\ErrorRepository;
use Psr\Http\Message\ResponseInterface;
use TYPO3\CMS\Backend\Template\ModuleTemplate;
use TYPO3\CMS\Backend\Template\ModuleTemplateFactory;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Mvc\Controller\ActionController;

class ErrorController extends ActionController
{

    public function __construct(
        protected ErrorRepository       $errorRepository,
        protected ModuleTemplateFactory $moduleTemplateFactory
    )
    {
    }

    public function indexAction(): ResponseInterface
    {
        $errors = iterator_to_array($this->errorRepository->findLatest());

        // @todo viewHelper?!?!
        $errors = \array_map(function ($item) {
            $uriParts = \parse_url($item['uri']);
            $item['fields'] = [
                'source_host' => $uriParts['host'],
                'source_path' => $uriParts['path'],
            ];

            return $item;
        }, $errors);

        return $this->createModuleTemplate()
            ->assignMultiple([
                'errors' => $errors,
            ])
            ->renderResponse('Error/Index');
    }

    public function deleteAction(): ResponseInterface
    {
        $this->addFlashMessage('Die 404-Error Liste wurde erfolgreich zurückgesetzt.', 'Liste geleert');

        $connection = GeneralUtility::makeInstance(ConnectionPool::class)->getConnectionForTable(ErrorRepository::TABLE_NAME);
        $connection->truncate(ErrorRepository::TABLE_NAME);

        return $this->redirect('index');
    }

    protected function createModuleTemplate(): ModuleTemplate
    {
        return $this->moduleTemplateFactory->create($this->request)
            ->setFlashMessageQueue($this->getFlashMessageQueue())
            ->setModuleClass('tx-errorlog');
    }
}
