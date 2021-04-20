<?php
/**
 * $EM_CONF
 *
 * @package    Hdnet
 * @author     Tim Lochmüller <tim.lochmueller@hdnet.de>
 */

$EM_CONF[$_EXTKEY] = [
    'title' => 'Error log',
    'description' => 'Log 404 errors and provider a backend module',
    'category' => 'misc',
    'version' => '1.0.0',
    'dependencies' => 'hdnet',
    'state' => 'stable',
    'author' => 'HDNET GmbH & Co. KG',
    'author_email' => '',
    'author_company' => 'hdnet.de',
    'constraints' => [
        'depends' => [
            'typo3' => '9.5.0-0.0.0',
            'redirects' => '9.5.0-0.0.0',
            'autoloader' => '6.0.0-0.0.0',
        ],
    ],
];
