<?php

\HDNET\Autoloader\Loader::extTables('HDNET', 'error_log', [
    'StaticTyposcript',
    'ExtensionTypoScriptSetup',
    'SmartObjects',
    'TcaFiles',
    'FluidNamespace',
    'Plugins',
    'Icon',
]);

\TYPO3\CMS\Extbase\Utility\ExtensionUtility::registerModule(
    'HDNET.ErrorLog',
    'site',
    'error', '',
    [
        'Error' => 'index,delete'
    ],
    [
        'access' => 'user,group',
        'icon' => 'EXT:error_log/' . \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::getExtensionIcon(\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extPath('error_log')),
        'labels' => 'LLL:EXT:error_log/Resources/Private/Language/locallang_error.xlf',
    ]
);
