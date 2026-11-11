<?php

declare(strict_types=1);

namespace HDNET\ErrorLog\Tests\Unit\Service;

use HDNET\ErrorLog\Service\ExistingPageService;
use HDNET\ErrorLog\Tests\Unit\AbstractTest;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Site\Entity\Site;
use TYPO3\CMS\Core\Site\SiteFinder;

/**
 * ExistingPageServiceTest.
 *
 * @internal
 */
#[\PHPUnit\Framework\Attributes\CoversNothing]
final class ExistingPageServiceTest extends AbstractTest
{
    public function testEmptyErrorListIsHandled(): void
    {
        self::assertSame([], $this->createService()->filterExistingPages([]));
    }

    public function testUriOfExistingPageIsRemoved(): void
    {
        self::assertSame([], $this->filter('https://example.com/contact'));
    }

    public function testUriOfExistingRootPageIsRemoved(): void
    {
        self::assertSame([], $this->filter('https://example.com/'));
    }

    public function testQueryStringAndTrailingSlashAreIgnored(): void
    {
        self::assertSame([], $this->filter('https://example.com/contact/?foo=bar'));
    }

    public function testEncodedUriIsRemoved(): void
    {
        self::assertSame([], $this->filter('https://example.com/kontakt-b%C3%BCro'));
    }

    public function testUnknownUriIsKept(): void
    {
        self::assertSame(['https://example.com/not-here'], $this->filter('https://example.com/not-here'));
    }

    public function testSlugOfOtherSiteIsKept(): void
    {
        self::assertSame(['https://other.com/contact'], $this->filter('https://other.com/contact'));
    }

    public function testUriOfUnknownHostIsKept(): void
    {
        self::assertSame(['https://unknown.com/contact'], $this->filter('https://unknown.com/contact'));
    }

    public function testDeletedPageIsKept(): void
    {
        self::assertSame(['https://example.com/trashed'], $this->filter('https://example.com/trashed'));
    }

    public function testPageWithoutFrontendOutputIsKept(): void
    {
        self::assertSame(['https://example.com/storage'], $this->filter('https://example.com/storage'));
    }

    public function testTranslatedSlugOfLanguageBaseIsRemoved(): void
    {
        self::assertSame([], $this->filter('https://example.com/de/kontakt'));
    }

    public function testTranslatedSlugOutsideOfLanguageBaseIsKept(): void
    {
        self::assertSame(['https://example.com/kontakt'], $this->filter('https://example.com/kontakt'));
    }

    public function testSlugOfFallbackLanguageIsRemoved(): void
    {
        self::assertSame([], $this->filter('https://example.com/de/contact'));
    }

    public function testSlugOfStrictLanguageWithoutTranslationIsKept(): void
    {
        self::assertSame(['https://example.com/fr/contact'], $this->filter('https://example.com/fr/contact'));
    }

    public function testPageOfOtherSiteInSameDomainIsRemoved(): void
    {
        self::assertSame([], $this->filter('https://example.com/shop/cart'));
    }

    public function testRemainingErrorsKeepTheirDataAndOrder(): void
    {
        $errors = $this->createService()->filterExistingPages([
            ['uri' => 'https://example.com/first', 'c' => 5],
            ['uri' => 'https://example.com/contact', 'c' => 4],
            ['uri' => 'https://example.com/second', 'c' => 3],
        ]);

        self::assertSame([
            ['uri' => 'https://example.com/first', 'c' => 5],
            ['uri' => 'https://example.com/second', 'c' => 3],
        ], $errors);
    }

    /**
     * @return array<int, string>
     */
    private function filter(string ...$uris): array
    {
        $errors = \array_map(static fn(string $uri): array => ['uri' => $uri], $uris);

        return \array_column($this->createService()->filterExistingPages($errors), 'uri');
    }

    private function createService(): ExistingPageService
    {
        $this->createPages();

        $siteFinder = $this->createStub(SiteFinder::class);
        $siteFinder->method('getAllSites')->willReturn($this->createSites());

        return new ExistingPageService($siteFinder);
    }

    /**
     * @return array<string, Site>
     */
    private function createSites(): array
    {
        return [
            'main' => new Site('main', 1, [
                'base' => 'https://example.com/',
                'languages' => [
                    ['languageId' => 0, 'locale' => 'en_US.UTF-8', 'base' => '/'],
                    ['languageId' => 1, 'locale' => 'de_DE.UTF-8', 'base' => '/de/', 'fallbackType' => 'fallback', 'fallbacks' => '0'],
                    ['languageId' => 2, 'locale' => 'fr_FR.UTF-8', 'base' => '/fr/', 'fallbackType' => 'strict'],
                ],
            ]),
            'shop' => new Site('shop', 50, [
                'base' => 'https://example.com/shop/',
                'languages' => [
                    ['languageId' => 0, 'locale' => 'en_US.UTF-8', 'base' => '/'],
                ],
            ]),
            'other' => new Site('other', 100, [
                'base' => 'https://other.com/',
                'languages' => [
                    ['languageId' => 0, 'locale' => 'en_US.UTF-8', 'base' => '/'],
                ],
            ]),
        ];
    }

    private function createPages(): void
    {
        $connection = $this->createPageDatabase();

        $pages = [
            // site "main"
            ['uid' => 1, 'pid' => 0, 'slug' => '/'],
            ['uid' => 2, 'pid' => 1, 'slug' => '/contact'],
            ['uid' => 3, 'pid' => 1, 'slug' => '/kontakt-büro'],
            ['uid' => 4, 'pid' => 1, 'slug' => '/trashed', 'deleted' => 1],
            ['uid' => 5, 'pid' => 1, 'slug' => '/storage', 'doktype' => 254],
            ['uid' => 6, 'pid' => 1, 'slug' => '/kontakt', 'sys_language_uid' => 1, 'l10n_parent' => 2],
            // site "shop" inside of the page tree of site "main"
            ['uid' => 50, 'pid' => 1, 'slug' => '/shop'],
            ['uid' => 51, 'pid' => 50, 'slug' => '/cart'],
            // site "other"
            ['uid' => 100, 'pid' => 0, 'slug' => '/'],
        ];

        foreach ($pages as $page) {
            $connection->insert('pages', $page + [
                'doktype' => 1,
                'sys_language_uid' => 0,
                'l10n_parent' => 0,
                'deleted' => 0,
            ], ['uid' => Connection::PARAM_INT]);
        }
    }
}
