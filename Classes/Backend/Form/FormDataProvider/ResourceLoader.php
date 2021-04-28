<?php
/***************************************************************
 *  Copyright notice
 *
 *  (c) 2019 Sjoerd Zonneveld  <typo3@bitpatroon.nl>
 *  Date: 4-4-2019 17:24
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

namespace BPN\ExpiringFeGroups\Backend\Form\FormDataProvider;

use TYPO3\CMS\Backend\Form\FormDataProviderInterface;
use TYPO3\CMS\Core\Page\PageRenderer;
use TYPO3\CMS\Core\SingletonInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\PathUtility;

class ResourceLoader implements SingletonInterface, FormDataProviderInterface
{
    /** @var bool */
    private $alreadyAdded;

    /**
     * Add form data to result array
     *
     * @param array $result Initialized result array
     *
     * @return array Result filled with more data
     */
    public function addData(array $result)
    {
        $table = $result['tableName'];
        if ($table !== 'fe_users') {
            return $result;
        }

        if ($this->alreadyAdded) {
            return $result;
        }
        $this->alreadyAdded = true;

        /** @var PageRenderer $pageRenderer */
        $pageRenderer = GeneralUtility::makeInstance(PageRenderer::class);
        $pageRenderer->loadRequireJsModule('TYPO3/CMS/BpnExpiringFeGroups/Backend');

        $cssFile = GeneralUtility::getFileAbsFileName(
            'EXT:bpn_expiring_fe_groups/Resources/Public/CSS/Backend/Backend.css'
        );
        $cssFile = PathUtility::getAbsoluteWebPath($cssFile);
        $pageRenderer->addCssFile($cssFile);

        return $result;
    }
}
