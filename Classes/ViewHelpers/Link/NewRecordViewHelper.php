<?php

declare(strict_types=1);

namespace HDNET\ErrorLog\ViewHelpers\Link;

use TYPO3\CMS\Backend\Routing\UriBuilder;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3Fluid\Fluid\Core\Rendering\RenderingContextInterface;

class NewRecordViewHelper extends \TYPO3\CMS\Backend\ViewHelpers\Uri\NewRecordViewHelper
{
    /**
     * initializeArguments.
     */
    public function initializeArguments()
    {
        parent::initializeArguments();

        $this->registerArgument('fields', 'array', '', false, []);
    }

    /**
     * @throws \TYPO3\CMS\Backend\Routing\Exception\RouteNotFoundException
     */
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
