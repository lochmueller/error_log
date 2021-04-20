<?php

use HDNET\ErrorLog\Middleware\ErrorMiddleware;

return [
    'frontend' => [
        'site/error' => [
            'target' => ErrorMiddleware::class,
            'before' => [
                'typo3/cms-frontend/tsfe',
            ],
        ],
    ],
];
