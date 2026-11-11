<?php

declare(strict_types=1);

namespace HDNET\ErrorLog\Domain\Model;

use TYPO3\CMS\Extbase\DomainObject\AbstractEntity;

class Error extends AbstractEntity
{
    protected string $uri = '';

    protected string $userAgent = '';

    public function getUri(): string
    {
        return $this->uri;
    }

    public function setUri(string $uri): void
    {
        $this->uri = $uri;
    }

    public function getUserAgent(): string
    {
        return $this->userAgent;
    }

    public function setUserAgent(string $userAgent): void
    {
        $this->userAgent = $userAgent;
    }
}
