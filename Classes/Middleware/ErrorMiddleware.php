<?php

declare(strict_types=1);

namespace HDNET\ErrorLog\Middleware;

use HDNET\ErrorLog\Domain\Repository\ErrorRepository;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Http\NormalizedParams;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\PathUtility;

/**
 * ErrorMiddleware.
 */
class ErrorMiddleware implements MiddlewareInterface
{
    public const DO_NOT_LOG_EXTENSION = 'js,css';

    /**
     * Maximum length of the stored user agent (see ext_tables.sql).
     */
    public const USER_AGENT_MAX_LENGTH = 500;

    /**
     * Process an incoming server request.
     *
     * Processes an incoming server request in order to produce a response.
     * If unable to produce the response itself, it may delegate to the provided
     * request handler to do so.
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $response = $handler->handle($request);

        if (404 === $response->getStatusCode()) {
            try {
                $uriIn = (string) $request->getUri();
                $extension = (string) PathUtility::pathinfo((string) \parse_url($uriIn, PHP_URL_PATH), PATHINFO_EXTENSION);
                if (false === \mb_strpos($uriIn, '/typo3temp/') && false === \mb_strpos($uriIn, '/typo3conf/') && !\in_array($extension, $this->getDoNotLogExtensions(), true)) {
                    $connection = GeneralUtility::makeInstance(ConnectionPool::class)->getConnectionForTable(ErrorRepository::TABLE_NAME);
                    $connection->insert(ErrorRepository::TABLE_NAME, [
                        'uri' => $uriIn,
                        'user_agent' => $this->getUserAgent($request),
                        'crdate' => \time(),
                        'tstamp' => \time(),
                    ]);
                }
            } catch (\Exception $exception) {
                // nothing
            }
        }

        return $response;
    }

    protected function getUserAgent(ServerRequestInterface $request): string
    {
        $normalizedParams = $request->getAttribute('normalizedParams');
        $userAgent = $normalizedParams instanceof NormalizedParams ? $normalizedParams->getHttpUserAgent() : $request->getHeaderLine('User-Agent');

        return \mb_substr(\trim($userAgent), 0, self::USER_AGENT_MAX_LENGTH);
    }

    protected function getDoNotLogExtensions(): array
    {
        return GeneralUtility::trimExplode(',', self::DO_NOT_LOG_EXTENSION . ',' . $GLOBALS['TYPO3_CONF_VARS']['SYS']['mediafile_ext'], true);
    }
}
