<?php

declare(strict_types=1);

namespace HDNET\ErrorLog\Tests\Unit\Domain\Model;

use HDNET\ErrorLog\Domain\Model\Error;
use HDNET\ErrorLog\Tests\Unit\AbstractTest;
use TYPO3\CMS\Extbase\DomainObject\AbstractEntity;

/**
 * ErrorTest.
 *
 * @internal
 */
#[\PHPUnit\Framework\Attributes\CoversNothing]
final class ErrorTest extends AbstractTest
{
    public function testIsExtbaseEntity(): void
    {
        self::assertInstanceOf(AbstractEntity::class, new Error());
    }

    public function testUriIsEmptyByDefault(): void
    {
        self::assertSame('', (new Error())->getUri());
    }

    /**
     * @return array<string, array{string}>
     */
    public static function uriDataProvider(): array
    {
        return [
            'empty uri' => [''],
            'absolute uri' => ['https://example.com/not-found'],
            'path only' => ['/not-found'],
            'uri with query string' => ['https://example.com/not-found?foo=bar'],
            'uri with umlauts' => ['https://example.com/grüße'],
        ];
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('uriDataProvider')]
    public function testSetAndGetUri(string $uri): void
    {
        $error = new Error();
        $error->setUri($uri);

        self::assertSame($uri, $error->getUri());
    }
}
