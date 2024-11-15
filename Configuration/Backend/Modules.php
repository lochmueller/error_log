<?php

use HDNET\ErrorLog\Controller\ErrorController;

return [
    'site_ErrorLog' => [
        'parent' => 'site',
        'access' => 'user,group',
        'workspaces' => 'live',
        'iconIdentifier' => 'tx-error-log',
        'path' => '/module/error-log',
        'labels' => 'LLL:EXT:error_log/Resources/Private/Language/locallang_error.xlf',
        'extensionName' => 'ErrorLog',
        'controllerActions' => [
            ErrorController::class => [
                'index',
                'delete',
            ],
        ],
    ],
];
