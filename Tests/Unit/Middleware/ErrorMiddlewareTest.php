<?php

declare(strict_types=1);

namespace HDNET\ErrorLog\Tests\Unit\Middleware;

use HDNET\ErrorLog\Domain\Repository\ErrorRepository;
use HDNET\ErrorLog\Middleware\ErrorMiddleware;
use HDNET\ErrorLog\Tests\Unit\AbstractTest;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Http\HtmlResponse;
use TYPO3\CMS\Core\Http\ServerRequest;

/**
 * ErrorMiddlewareTest.
 *
 * @internal
 */
#[\PHPUnit\Framework\Attributes\CoversNothing]
final class ErrorMiddlewareTest extends AbstractTest
{
    protected function setUp(): void
    {
        parent::setUp();
        $GLOBALS['TYPO3_CONF_VARS']['SYS']['mediafile_ext'] = 'gif,jpg,jpeg,png,svg,pdf';
    }

    public function testResponseOfTheHandlerIsReturned(): void
    {
        $this->createErrorDatabase();
        $response = new HtmlResponse('content');

        self::assertSame($response, $this->process('https://example.com/', $response));
    }

    /**
     * @return array<string, array{string}>
     */
    public static function loggedUriDataProvider(): array
    {
        return [
            'simple path' => ['https://example.com/not-found'],
            'path with query string' => ['https://example.com/not-found?foo=bar'],
            'path without extension' => ['https://example.com/some/deep/page'],
            'html extension' => ['https://example.com/page.html'],
        ];
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('loggedUriDataProvider')]
    public function testNotFoundResponseIsLogged(string $uri): void
    {
        $connection = $this->createErrorDatabase();

        $this->process($uri, new HtmlResponse('not found', 404));

        $rows = $this->getRows($connection);
        self::assertCount(1, $rows);
        self::assertSame($uri, $rows[0]['uri']);
        self::assertGreaterThan(0, (int) $rows[0]['crdate']);
        self::assertSame((int) $rows[0]['crdate'], (int) $rows[0]['tstamp']);
    }

    /**
     * @return array<string, array{int}>
     */
    public static function statusCodeDataProvider(): array
    {
        return [
            'ok' => [200],
            'moved permanently' => [301],
            'forbidden' => [403],
            'gone' => [410],
            'server error' => [500],
        ];
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('statusCodeDataProvider')]
    public function testOtherStatusCodesAreNotLogged(int $statusCode): void
    {
        $connection = $this->createErrorDatabase();

        $this->process('https://example.com/not-found', new HtmlResponse('content', $statusCode));

        self::assertSame([], $this->getRows($connection));
    }

    /**
     * @return array<string, array{string}>
     */
    public static function ignoredUriDataProvider(): array
    {
        return [
            'javascript file' => ['https://example.com/assets/app.js'],
            'stylesheet file' => ['https://example.com/assets/app.css'],
            'media file from TYPO3_CONF_VARS' => ['https://example.com/assets/logo.png'],
            'media file with query string' => ['https://example.com/assets/logo.svg?v=2'],
            'typo3temp path' => ['https://example.com/typo3temp/assets/missing'],
            'typo3conf path' => ['https://example.com/typo3conf/ext/foo/missing'],
            'typo3temp in the query string' => ['https://example.com/page?path=/typo3temp/'],
        ];
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('ignoredUriDataProvider')]
    public function testIgnoredUrisAreNotLogged(string $uri): void
    {
        $connection = $this->createErrorDatabase();

        $this->process($uri, new HtmlResponse('not found', 404));

        self::assertSame([], $this->getRows($connection));
    }

    public function testDatabaseErrorsDoNotBreakTheResponse(): void
    {
        // No table is created, so the insert fails
        $this->createDatabase();
        $response = new HtmlResponse('not found', 404);

        self::assertSame($response, $this->process('https://example.com/not-found', $response));
    }

    private function process(string $uri, ResponseInterface $response): ResponseInterface
    {
        $handler = new class ($response) implements RequestHandlerInterface {
            public function __construct(private readonly ResponseInterface $response) {}

            public function handle(ServerRequestInterface $request): ResponseInterface
            {
                return $this->response;
            }
        };

        return (new ErrorMiddleware())->process(new ServerRequest($uri), $handler);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function getRows(Connection $connection): array
    {
        return $connection->executeQuery('SELECT * FROM ' . ErrorRepository::TABLE_NAME . ' ORDER BY uid')->fetchAllAssociative();
    }
}
