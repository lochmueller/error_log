<?php

declare(strict_types=1);

namespace HDNET\ErrorLog\Tests\Unit\ViewHelpers\Link;

use HDNET\ErrorLog\Tests\Unit\AbstractTest;
use HDNET\ErrorLog\ViewHelpers\Link\NewRecordViewHelper;
use TYPO3\CMS\Backend\Routing\UriBuilder;
use TYPO3\CMS\Core\Http\Uri;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3Fluid\Fluid\Core\Rendering\RenderingContextInterface;

/**
 * NewRecordViewHelperTest.
 *
 * @internal
 */
#[\PHPUnit\Framework\Attributes\CoversNothing]
final class NewRecordViewHelperTest extends AbstractTest
{
    /**
     * @var array<int, array{string, array<string, mixed>}>
     */
    private array $buildUriCalls = [];

    protected function setUp(): void
    {
        parent::setUp();
        $this->buildUriCalls = [];
    }

    public function testInitializeArgumentsRegistersTheExpectedArguments(): void
    {
        $viewHelper = new NewRecordViewHelper();
        $viewHelper->initializeArguments();

        $definitions = $viewHelper->prepareArguments();

        foreach (['uid', 'pid', 'table', 'returnUrl', 'defaultValues', 'fields'] as $name) {
            self::assertArrayHasKey($name, $definitions);
        }
        self::assertTrue($definitions['table']->isRequired());
        self::assertFalse($definitions['uid']->isRequired());
        self::assertSame('', $definitions['returnUrl']->getDefaultValue());
        self::assertSame([], $definitions['fields']->getDefaultValue());
    }

    public function testUidAndPidTogetherAreRejected(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionCode(1526136338);

        $this->renderStatic(['uid' => -1, 'pid' => 12, 'table' => 'pages']);
    }

    /**
     * @return array<string, array{int}>
     */
    public static function invalidUidDataProvider(): array
    {
        return [
            'zero' => [0],
            'positive' => [12],
        ];
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('invalidUidDataProvider')]
    public function testNonNegativeUidIsRejected(int $uid): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionCode(1526136362);

        $this->renderStatic(['uid' => $uid, 'table' => 'pages']);
    }

    public function testUriIsBuiltForANewRecordAfterTheGivenUid(): void
    {
        $this->registerUriBuilder();

        $uri = $this->renderStatic([
            'uid' => -5,
            'table' => 'sys_redirect',
            'returnUrl' => '/typo3/module/site/redirects',
            'fields' => ['source_path' => '/not-found'],
        ]);

        self::assertSame('/typo3/record/edit', $uri);
        self::assertCount(1, $this->buildUriCalls);
        self::assertSame('record_edit', $this->buildUriCalls[0][0]);
        self::assertSame([
            'edit' => ['sys_redirect' => [-5 => 'new']],
            'returnUrl' => '/typo3/module/site/redirects',
            'defVals' => ['sys_redirect' => ['source_path' => '/not-found']],
        ], $this->buildUriCalls[0][1]);
    }

    public function testUriIsBuiltForANewRecordOnTheGivenPage(): void
    {
        $this->registerUriBuilder();

        $this->renderStatic(['pid' => 12, 'table' => 'sys_redirect', 'returnUrl' => '/typo3/']);

        self::assertSame(['sys_redirect' => [12 => 'new']], $this->buildUriCalls[0][1]['edit']);
    }

    public function testUriIsBuiltWithoutUidAndPid(): void
    {
        $this->registerUriBuilder();

        $this->renderStatic(['table' => 'sys_redirect', 'returnUrl' => '/typo3/']);

        self::assertSame(['sys_redirect' => [0 => 'new']], $this->buildUriCalls[0][1]['edit']);
    }

    public function testEmptyReturnUrlFallsBackToTheCurrentRequestUri(): void
    {
        $this->registerUriBuilder();
        $_SERVER['REQUEST_URI'] = '/typo3/module/web/errorlog';
        GeneralUtility::flushInternalRuntimeCaches();

        $this->renderStatic(['table' => 'sys_redirect', 'returnUrl' => '']);

        self::assertSame('/typo3/module/web/errorlog', $this->buildUriCalls[0][1]['returnUrl']);
    }

    /**
     * @param array<string, mixed> $arguments
     */
    private function renderStatic(array $arguments): string
    {
        // Fluid always passes all registered arguments, so missing ones are null
        $arguments += ['uid' => null, 'pid' => null, 'returnUrl' => '', 'defaultValues' => [], 'fields' => []];

        return NewRecordViewHelper::renderStatic(
            $arguments,
            static fn(): string => '',
            $this->createMock(RenderingContextInterface::class)
        );
    }

    private function registerUriBuilder(): void
    {
        $uriBuilder = $this->createMock(UriBuilder::class);
        $uriBuilder->method('buildUriFromRoute')->willReturnCallback(
            function (string $name, array $parameters = []): Uri {
                $this->buildUriCalls[] = [$name, $parameters];

                return new Uri('/typo3/record/edit');
            }
        );
        GeneralUtility::setSingletonInstance(UriBuilder::class, $uriBuilder);
    }
}
