<?php

return [
    'ctrl' => [
        'crdate' => 'crdate',
        'cruser_id' => 'cruser_id',
        'delete' => 'deleted',
        'dividers2tabs' => 1,
        'iconfile' => 'EXT:error_log/Resources/Public/Icons/Extension.svg',
        'label' => 'uri',
        'sortby' => 'sorting',
        'title' => 'LLL:EXT:error_log/Resources/Private/Language/locallang.xlf:tx_errorlog_domain_model_error',
        'tstamp' => 'tstamp',
    ],
    'columns' => [
        'uri' => [
            'config' => [
                'type' => 'input',
            ],
            'label' => 'LLL:EXT:error_log/Resources/Private/Language/locallang.xlf:tx_errorlog_domain_model_error.uri',
        ],
    ],
    'palettes' => [
        'access' => [
            'showitem' => 'starttime, endtime, --linebreak--, hidden, editlock, --linebreak--, fe_group',
        ],
    ],
    'types' => [
        '1' => [
            'showitem' => 'uri,--div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:language,--palette--;;language,--div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:access,--palette--;LLL:EXT:frontend/Resources/Private/Language/locallang_tca.xlf:pages.palettes.access;access,--div--;LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:tabs.extended',
        ],
    ],
];
