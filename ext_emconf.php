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
    'version' => '2.0.2',
    'dependencies' => 'hdnet',
    'state' => 'stable',
    'author' => 'HDNET GmbH & Co. KG',
    'author_email' => '',
    'author_company' => 'hdnet.de',
    'constraints' => [
        'depends' => [
            'typo3' => '12.4.0-12.4.99',
            'redirects' => '12.4.0-12.4.99'
        ],
    ],
];
