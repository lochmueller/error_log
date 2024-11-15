<?php

declare(strict_types=1);

use TYPO3\CMS\Core\Imaging\IconProvider\BitmapIconProvider;
use TYPO3\CMS\Core\Imaging\IconProvider\SvgIconProvider;

return [
    'tx-error-log' => [
        'provider' => SvgIconProvider::class,
        'source' => 'EXT:error_log/Resources/Public/Icons/Extension.svg',
    ],
];
