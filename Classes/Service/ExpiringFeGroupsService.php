<?php

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2013 Sander Leeuwesteijn | iTypo (info@itypo.nl)
 *  Based on extension from: (c) 2004 Radu Cocieru (raducocieru@hotmail.com)
 *  All rights reserved
 *
 *  This script is part of the TYPO3 project. The TYPO3 project is
 *  free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 2 of the License, or
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

namespace BPN\ExpiringFeGroups\Service;

use BPN\ExpiringFeGroups\Domain\Repository\FrontEndUserGroupRepository;
use TYPO3\CMS\Core\Authentication\AbstractAuthenticationService;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Object\ObjectManager;

class ExpiringFeGroupsService extends AbstractAuthenticationService
{
    /**
     * @param array     data of user
     * @param array     Group data array of already known groups. This is handy if you want select other related groups.
     *
     * @return mixed groups array
     */
    public function getGroups(
        /* @noinspection PhpUnusedParameterInspection */
        $user,
        $knownGroups
    ) {
        if (!$user) {
            return [];
        }

        if ('getGroupsFE' !== $this->mode) {
            return [];
        }

        $addGroups = [];
        $now = time();

        $possibleGroups = GeneralUtility::trimExplode('*', $user['tx_expiringfegroups_groups']);
        if (!is_array($possibleGroups)) {
            $possibleGroups = [$possibleGroups];
        }
        foreach ($possibleGroups as $groupName => $groupRecord) {
            if (!$groupRecord) {
                continue;
            }
            list($groupId, $startDate, $expirationDate) = GeneralUtility::trimExplode('|', $groupRecord);

            if ($startDate > 0 && $startDate > $now) {
                continue;
            }
            if ($expirationDate > 0 && $now > $expirationDate) {
                continue;
            }
            $addGroups[] = $groupId;
        }

        if ($addGroups) {
            // get all subgroups
            $groupDataArr = $this->getFrontendUserGroupRepository()->getAllGroupsAndSubGroups($addGroups);
        }

        return $groupDataArr;
    }

    private function getFrontendUserGroupRepository(): FrontEndUserGroupRepository
    {
        /* @var FrontEndUserGroupRepository $frontEndUserGroupRepository */
        return GeneralUtility::makeInstance(ObjectManager::class)
            ->get(FrontEndUserGroupRepository::class);
    }
}
