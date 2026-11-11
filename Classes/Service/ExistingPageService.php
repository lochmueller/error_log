<?php

declare(strict_types=1);

namespace HDNET\ErrorLog\Service;

use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Domain\Repository\PageRepository;
use TYPO3\CMS\Core\Site\Entity\Site;
use TYPO3\CMS\Core\Site\Entity\SiteLanguage;
use TYPO3\CMS\Core\Site\SiteFinder;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Check logged 404 URIs against the slugs of the existing (non-deleted) pages.
 *
 * The site configuration is taken into account, so a slug only matches if the
 * domain (and the base path of the site/language) of the logged URI belongs to
 * the site of the page.
 */
class ExistingPageService
{
    public const TABLE_NAME = 'pages';

    /**
     * Doktypes that never deliver a frontend page, even if the record owns a slug.
     */
    protected const NON_ROUTABLE_DOKTYPES = [
        PageRepository::DOKTYPE_SPACER,
        PageRepository::DOKTYPE_SYSFOLDER,
        255, // recycler (constant is removed in TYPO3 v13)
    ];

    /**
     * Max amount of slugs per database request.
     */
    protected const SLUG_CHUNK_SIZE = 500;

    /**
     * Resolved root page IDs by page ID.
     *
     * @var array<int, int|null>
     */
    protected array $rootPageIds = [];

    /**
     * @var array<int, bool>|null
     */
    protected ?array $siteRootPageIds = null;

    public function __construct(protected readonly SiteFinder $siteFinder) {}

    /**
     * Drop all entries that point to an existing page of the matching site.
     *
     * @param array<int|string, array<string, mixed>> $errors
     *
     * @return array<int, array<string, mixed>>
     */
    public function filterExistingPages(array $errors): array
    {
        $candidates = [];
        foreach ($errors as $key => $error) {
            $candidate = $this->createCandidate((string) ($error['uri'] ?? ''));
            if (null !== $candidate) {
                $candidates[$key] = $candidate;
            }
        }

        if ([] === $candidates) {
            return \array_values($errors);
        }

        $pages = $this->findPagesBySlugs(\array_unique(\array_column($candidates, 'slug')));

        foreach ($candidates as $key => $candidate) {
            foreach ($pages[$candidate['slug']] ?? [] as $page) {
                if ($this->isPageOfCandidate($page, $candidate)) {
                    unset($errors[$key]);
                    break;
                }
            }
        }

        return \array_values($errors);
    }

    /**
     * Build the slug (incl. site and language) that the given URI would address.
     *
     * @return array{site: Site, language: SiteLanguage, slug: string}|null
     */
    protected function createCandidate(string $uri): ?array
    {
        if ('' === $uri) {
            return null;
        }

        $parts = \parse_url($uri);
        if (false === $parts) {
            return null;
        }

        $host = \mb_strtolower((string) ($parts['host'] ?? ''));
        $path = \rawurldecode((string) ($parts['path'] ?? '/'));

        $base = $this->findBase($host, $path);
        if (null === $base) {
            return null;
        }

        return [
            'site' => $base['site'],
            'language' => $base['language'],
            'slug' => $this->normalizeSlug(\mb_substr($path, \mb_strlen($base['path']))),
        ];
    }

    /**
     * Find the site language that serves the given host and path.
     *
     * @return array{site: Site, language: SiteLanguage, path: string, score: int}|null
     */
    protected function findBase(string $host, string $path): ?array
    {
        $base = null;

        foreach ($this->siteFinder->getAllSites() as $site) {
            foreach ($site->getLanguages() as $language) {
                $languageBase = $language->getBase();
                $baseHost = \mb_strtolower($languageBase->getHost());
                if ('' !== $baseHost && $baseHost !== $host) {
                    continue;
                }

                $basePath = \rtrim($languageBase->getPath(), '/');
                if ('' !== $basePath && $path !== $basePath && !\str_starts_with($path, $basePath . '/')) {
                    continue;
                }

                // Prefer the longest base path and - if equal - a base with an explicit host
                $score = (\mb_strlen($basePath) * 2) + ('' !== $baseHost ? 1 : 0);
                if (null === $base || $score > $base['score']) {
                    $base = ['site' => $site, 'language' => $language, 'path' => $basePath, 'score' => $score];
                }
            }
        }

        return $base;
    }

    /**
     * Slugs are stored with a leading and without a trailing slash.
     */
    protected function normalizeSlug(string $slug): string
    {
        return '/' . \trim($slug, '/');
    }

    /**
     * @param array<int, string> $slugs
     *
     * @return array<string, array<int, array<string, mixed>>>
     */
    protected function findPagesBySlugs(array $slugs): array
    {
        $pages = [];

        foreach (\array_chunk(\array_values($slugs), self::SLUG_CHUNK_SIZE) as $chunk) {
            $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
                ->getQueryBuilderForTable(self::TABLE_NAME);
            $queryBuilder->getRestrictions()->removeAll();

            $rows = $queryBuilder
                ->select('uid', 'pid', 'slug', 'sys_language_uid', 'l10n_parent')
                ->from(self::TABLE_NAME)
                ->where(
                    $queryBuilder->expr()->eq('deleted', 0),
                    $queryBuilder->expr()->in('slug', $queryBuilder->createNamedParameter($chunk, Connection::PARAM_STR_ARRAY)),
                    $queryBuilder->expr()->notIn('doktype', $queryBuilder->createNamedParameter(self::NON_ROUTABLE_DOKTYPES, Connection::PARAM_INT_ARRAY))
                )
                ->executeQuery()
                ->fetchAllAssociative();

            foreach ($rows as $row) {
                $pages[(string) $row['slug']][] = $row;
            }
        }

        return $pages;
    }

    /**
     * @param array<string, mixed>                           $page
     * @param array{site: Site, language: SiteLanguage, slug: string} $candidate
     */
    protected function isPageOfCandidate(array $page, array $candidate): bool
    {
        if (!$this->isLanguageOfCandidate((int) $page['sys_language_uid'], $candidate['language'])) {
            return false;
        }

        $pageId = (int) ($page['l10n_parent'] ?? 0) ?: (int) $page['uid'];

        return $this->findRootPageId($pageId) === $candidate['site']->getRootPageId();
    }

    /**
     * A page is delivered in the requested language, if it is translated into
     * this language or if the language falls back to the language of the page.
     */
    protected function isLanguageOfCandidate(int $languageId, SiteLanguage $language): bool
    {
        if ($languageId === $language->getLanguageId()) {
            return true;
        }

        if ('fallback' !== $language->getFallbackType()) {
            return false;
        }

        return \in_array($languageId, $language->getFallbackLanguageIds(), true);
    }

    /**
     * Walk up the page tree until a configured site root is reached.
     */
    protected function findRootPageId(int $pageId): ?int
    {
        $rootPageIds = $this->getSiteRootPageIds();
        $rootPageId = null;
        $chain = [];

        while ($pageId > 0 && !isset($chain[$pageId])) {
            if (\array_key_exists($pageId, $this->rootPageIds)) {
                $rootPageId = $this->rootPageIds[$pageId];
                break;
            }
            if (isset($rootPageIds[$pageId])) {
                $rootPageId = $pageId;
                $this->rootPageIds[$pageId] = $rootPageId;
                break;
            }

            $chain[$pageId] = true;
            $pageId = $this->findParentPageId($pageId);
        }

        foreach (\array_keys($chain) as $cachePageId) {
            $this->rootPageIds[$cachePageId] = $rootPageId;
        }

        return $rootPageId;
    }

    protected function findParentPageId(int $pageId): int
    {
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getQueryBuilderForTable(self::TABLE_NAME);
        $queryBuilder->getRestrictions()->removeAll();

        $parentPageId = $queryBuilder
            ->select('pid')
            ->from(self::TABLE_NAME)
            ->where(
                $queryBuilder->expr()->eq('deleted', 0),
                $queryBuilder->expr()->eq('uid', $queryBuilder->createNamedParameter($pageId, Connection::PARAM_INT))
            )
            ->setMaxResults(1)
            ->executeQuery()
            ->fetchOne();

        return false === $parentPageId ? 0 : (int) $parentPageId;
    }

    /**
     * @return array<int, bool>
     */
    protected function getSiteRootPageIds(): array
    {
        if (null === $this->siteRootPageIds) {
            $this->siteRootPageIds = [];
            foreach ($this->siteFinder->getAllSites() as $site) {
                $this->siteRootPageIds[$site->getRootPageId()] = true;
            }
        }

        return $this->siteRootPageIds;
    }
}
