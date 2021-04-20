<?php

declare(strict_types=1);

namespace HDNET\ErrorLog\Domain\Model;

use TYPO3\CMS\Extbase\DomainObject\AbstractEntity;

/**
 * Error.
 *
 * @db
 * @smartExclude EnableFields,Language,Workspaces
 */
class Error extends AbstractEntity
{
    /**
     * URI.
     *
     * @var string
     * @db
     */
    protected $uri = '';

    public function getUri(): string
    {
        return $this->uri;
    }

    public function setUri(string $uri): void
    {
        $this->uri = $uri;
    }
}
