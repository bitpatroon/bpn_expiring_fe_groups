<?php
/***************************************************************
 *  Copyright notice
 *
 *  (c) 2019 Sjoerd Zonneveld <typo3@bitpatroon.nl>
 *  Date: 28-1-2019 17:14
 *
 *  All rights reserved
 *
 *  This script is part of the TYPO3 project. The TYPO3 project is
 *  free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 3 of the License, or
 *  (at your option) any later version.
 *
 *  The GNU General Public License can be found at
 *  http://www.gnu.org/copyleft/gpl.html.
 *
 *  This script is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  This copyright notice MUST APPEAR in all copies of the script!
 ***************************************************************/

use TYPO3\CMS\Core\Imaging\IconProvider\BitmapIconProvider;
use TYPO3\CMS\Core\Imaging\IconRegistry;
use TYPO3\CMS\Core\Utility\GeneralUtility;

if (TYPO3_MODE === 'BE') {
    /** @var IconRegistry $iconRegistry */
    $iconRegistry = GeneralUtility::makeInstance(IconRegistry::class);
    $iconRegistry->registerIcon(
        'bpn_expiring_fe_groups-expgrps', // Icon-Identifier, z.B. tx-myext-action-preview
        BitmapIconProvider::class,
        ['source' => 'EXT:bpn_expiring_fe_groups/ext_icon.png']
    );

#--- Add Module types for pages
    $GLOBALS['TCA']['pages']['ctrl']['typeicon_classes']['contains-expgrps'] = 'bpn_expiring_fe_groups-expgrps';
    $GLOBALS['TCA']['pages']['columns']['module']['config']['items'][] = array(
        'Expiring groups',
        'expgrps',
        'bpn_expiring_fe_groups-expgrps'
    );
}
