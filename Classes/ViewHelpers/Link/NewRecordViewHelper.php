<?php

declare(strict_types=1);

namespace HDNET\ErrorLog\ViewHelpers\Link;

use TYPO3\CMS\Backend\Routing\UriBuilder;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3Fluid\Fluid\Core\ViewHelper\AbstractTagBasedViewHelper;

class NewRecordViewHelper extends AbstractTagBasedViewHelper
{
    public function initializeArguments(): void
    {
        $this->registerArgument('uid', 'int', 'uid < 0 will insert the record after the given uid', false);
        $this->registerArgument('pid', 'int', 'the page id where the record will be created', false);
        $this->registerArgument('table', 'string', 'target database table', true);
        $this->registerArgument('returnUrl', 'string', 'return to this URL after closing the edit dialog', false, '');
        $this->registerArgument('defaultValues', 'array', 'default values for fields of the new record', false, []);

        $this->registerArgument('fields', 'array', '', false, []);
    }

    public function render(): string
    {
        if ($this->arguments['uid'] && $this->arguments['pid']) {
            throw new \InvalidArgumentException('Can\'t handle both uid and pid for new records', 1526136338);
        }
        if (isset($this->arguments['uid']) && $this->arguments['uid'] >= 0) {
            throw new \InvalidArgumentException('Uid must be negative integer, ' . $this->arguments['uid'] . ' given', 1526136362);
        }

        $returnUrl = $this->arguments['returnUrl'];
        if (empty($returnUrl)) {
            $returnUrl = GeneralUtility::getIndpEnv('REQUEST_URI');
        }

        $params = [
            'edit' => [$this->arguments['table'] => [$this->arguments['uid'] ?? $this->arguments['pid'] ?? 0 => 'new']],
            'returnUrl' => $returnUrl,
            'defVals' => [
                $this->arguments['table'] => $this->arguments['fields'],
            ],
        ];

        $uriBuilder = GeneralUtility::makeInstance(UriBuilder::class);

        return (string) $uriBuilder->buildUriFromRoute('record_edit', $params);
    }
}
