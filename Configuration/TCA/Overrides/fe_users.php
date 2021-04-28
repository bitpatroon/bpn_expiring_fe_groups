<?php

use BPN\ExpiringFeGroups\Backend\UserFunction\ItemSelector;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;

defined('TYPO3_MODE') or die('¯\_(ツ)_/¯');

ExtensionManagementUtility::addTCAcolumns(
    'fe_users',
    [
        'tx_expiringfegroups_groups' => [
            'exclude' => 0,
            'label'   => 'LLL:EXT:bpn_expiring_fe_groups/Resources/Private/Language/locallang_db.xlf:fe_users.tx_expiringfegroups_groups',
            'config'  => [
                'type'     => 'user',
                'renderType' => 'expiringGroupsSelector'
            ]
        ],
    ]
);

ExtensionManagementUtility::addToAllTCAtypes(
    'fe_users',
    'tx_expiringfegroups_groups',
    '',
    'after:usergroup'
);
