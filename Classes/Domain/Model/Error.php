<?php

declare(strict_types=1);

namespace HDNET\ErrorLog\Domain\Model;

use TYPO3\CMS\Extbase\DomainObject\AbstractEntity;

class Error extends AbstractEntity
{
    protected string $uri = '';

    public function getUri(): string
    {
        return $this->uri;
    }

    public function setUri(string $uri): void
    {
        $this->uri = $uri;
    }
}
