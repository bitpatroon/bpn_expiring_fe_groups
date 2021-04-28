<?php

use BPN\ExpiringFeGroups\Backend\Form\FormDataProvider\ResourceLoader;
use BPN\ExpiringFeGroups\Backend\UserFunction\ItemSelector;
use BPN\ExpiringFeGroups\Service\ExpiringFeGroupsService;
use TYPO3\CMS\Backend\Form\FormDataGroup\TcaDatabaseRecord;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;

if (!defined('TYPO3_MODE')) {
    die('Access denied.');
}

ExtensionManagementUtility::addService(
    $_EXTKEY,
    'auth' /* sv type */,
    ExpiringFeGroupsService::class,
    [
        'title'       => 'Expiring FE Groups',
        'description' => 'Filters out any expired FE groups.',
        'subtype'     => 'getGroupsFE',
        'available'   => true,
        'priority'    => 15,
        'quality'     => 15,
        'os'          => '',
        'exec'        => '',
        'className'   => ExpiringFeGroupsService::class,
    ]
);

if (TYPO3_MODE === 'BE') {

    $GLOBALS['TYPO3_CONF_VARS']['SYS']['formEngine']['nodeRegistry'][1619622485] = [
        'nodeName' => 'expiringGroupsSelector',
        'priority' => 40,
        'class' => ItemSelector::class,
    ];

    $GLOBALS['TYPO3_CONF_VARS']['SYS']['formEngine']['formDataGroup']['tcaDatabaseRecord'][ResourceLoader::class] = [
        'depends' => [TcaDatabaseRecord::class],
    ];

}

