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
    'version' => '2.1.1',
    'dependencies' => 'hdnet',
    'state' => 'stable',
    'author' => 'HDNET GmbH & Co. KG',
    'author_email' => '',
    'author_company' => 'hdnet.de',
    'constraints' => [
        'depends' => [
            'php' => '8.2.0-8.99.99',
            'typo3' => '13.4.0-14.99.99',
            'redirects' => '13.4.0-14.99.99',
            'extbase' => '13.4.0-14.99.99'
        ],
    ],
];
