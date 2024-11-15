<?php

declare(strict_types=1);

namespace HDNET\ErrorLog\ViewHelpers\Link;

use TYPO3\CMS\Backend\Routing\UriBuilder;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3Fluid\Fluid\Core\Rendering\RenderingContextInterface;
use TYPO3Fluid\Fluid\Core\ViewHelper\AbstractTagBasedViewHelper;
use TYPO3Fluid\Fluid\Core\ViewHelper\Traits\CompileWithRenderStatic;

class NewRecordViewHelper extends AbstractTagBasedViewHelper
{

    use CompileWithRenderStatic;
    public function initializeArguments(): void
    {
        $this->registerArgument('uid', 'int', 'uid < 0 will insert the record after the given uid', false);
        $this->registerArgument('pid', 'int', 'the page id where the record will be created', false);
        $this->registerArgument('table', 'string', 'target database table', true);
        $this->registerArgument('returnUrl', 'string', 'return to this URL after closing the edit dialog', false, '');
        $this->registerArgument('defaultValues', 'array', 'default values for fields of the new record', false, []);

        $this->registerArgument('fields', 'array', '', false, []);
    }

    public static function renderStatic(array $arguments, \Closure $renderChildrenClosure, RenderingContextInterface $renderingContext): string
    {
        if ($arguments['uid'] && $arguments['pid']) {
            throw new \InvalidArgumentException('Can\'t handle both uid and pid for new records', 1526136338);
        }
        if (isset($arguments['uid']) && $arguments['uid'] >= 0) {
            throw new \InvalidArgumentException('Uid must be negative integer, ' . $arguments['uid'] . ' given', 1526136362);
        }

        if (empty($arguments['returnUrl'])) {
            $arguments['returnUrl'] = GeneralUtility::getIndpEnv('REQUEST_URI');
        }

        $params = [
            'edit' => [$arguments['table'] => [$arguments['uid'] ?? $arguments['pid'] ?? 0 => 'new']],
            'returnUrl' => $arguments['returnUrl'],
            'defVals' => [
                $arguments['table'] => $arguments['fields'],
            ],
        ];

        $uriBuilder = GeneralUtility::makeInstance(UriBuilder::class);

        return (string)$uriBuilder->buildUriFromRoute('record_edit', $params);
    }
}
