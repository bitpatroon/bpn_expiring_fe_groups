<?php

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2018 Sjoerd Zonneveld  <typo3@bitpatroon.nl>
 *  Date: 28-8-2018 14:03
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

namespace BPN\ExpiringFeGroups;

use BPN\ExpiringFeGroups\Domain\Repository\FrontEndUserRepository;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Object\ObjectManager;

class Api
{
    public const EXP_FIELD_ENDDATE = 'enddate';
    public const EXP_FIELD_STARTDATE = 'startdate';
    public const EXP_FIELD_ID = 'id';

    public function is_member_of(&$user, $group_id)
    {
        $exp = $this->expires_in($user, $group_id);

        return $exp && ($exp > 0);
    }

    /**
     * Determines if a user has access
     *
     * @param $user
     * @param $group_id
     *
     * @return bool|int
     */
    public function expires_in(&$user, $group_id)
    {
        $userRecs = $this->fetchGroups($user);
        $userRecs = $userRecs[$group_id];

        if (!is_array($userRecs)) {
            return false;
        }

        $tt = 0;
        $t = 0;
        $now = time();
        foreach ($userRecs as $item) {
            if ($item[self::EXP_FIELD_STARTDATE] > 0 && $item[self::EXP_FIELD_STARTDATE] > $now) {
                continue;
            }
            if ($item[self::EXP_FIELD_ENDDATE] > 0 && $now > $item[self::EXP_FIELD_ENDDATE]) {
                continue;
            }
            $tt = $item[self::EXP_FIELD_ENDDATE] - $now;
            if ($tt > $t) {
                $t = $tt;
            }
        }

        return $tt;
    }

    /**
     * @param array $user a user record
     *
     * @return array the groups
     */
    public function fetchGroups($user)
    {
        $fieldValue = $user[FrontEndUserRepository::USER_FIELD_EXPIRINGGROUPS];

        return $this->convertIntoArray($fieldValue);
    }

    /**
     * Converts into an array
     *
     * @param string $fieldValue
     *
     * @return array of items (id, startdate, enddate)
     */
    public function convertIntoArray($fieldValue)
    {
        $groups = explode('*', $fieldValue);
        if (!is_array($groups)) {
            $groups = [$groups];
        }

        $result = [];
        foreach ($groups as $group) {
            if (empty($group)) {
                continue;
            }
            list($groupId, $startDate, $expirationDate) = explode('|', $group);

            $result[$groupId][] = [
                self::EXP_FIELD_ID        => (int)$groupId,
                self::EXP_FIELD_STARTDATE => (int)$startDate,
                self::EXP_FIELD_ENDDATE   => (int)$expirationDate,
            ];
        }

        return $result;
    }

    /**
     * @param array $user      the user object
     * @param int   $group_id  the id of the group
     * @param int   $startTime epoch timestamp
     * @param int   $endTime   epoch timestamp
     * @param bool  $extend    true to extend (default)
     */
    public function addToGroup(&$user, $group_id, $startTime, $endTime = 0, $extend = true)
    {
        $groups = $this->fetchGroups($user);
        if (($endTime == 0) || ($endTime < $startTime)) {
            $endTime = time() + $startTime;
            $startTime = time();
        }
        $duration = $endTime - $startTime;

        if (!$extend || !is_array($groups[$group_id])) {
            $groups[$group_id][] = [
                self::EXP_FIELD_ID        => $group_id,
                self::EXP_FIELD_STARTDATE => $startTime,
                self::EXP_FIELD_ENDDATE   => $endTime,
            ];
        } else {
            $len = count($groups[$group_id]) - 1;
            if ($groups[$group_id][$len]['' . self::EXP_FIELD_ENDDATE . ''] > time()) {
                $groups[$group_id][$len][self::EXP_FIELD_ENDDATE] += $duration;
            } else {
                $groups[$group_id][] = [
                    self::EXP_FIELD_ID        => $group_id,
                    self::EXP_FIELD_STARTDATE => $startTime,
                    self::EXP_FIELD_ENDDATE   => $endTime,
                ];
            }
        }

        $user[FrontEndUserRepository::USER_FIELD_EXPIRINGGROUPS] = $this->compactArray($groups);
    }

    /**
     * Convert the array
     */
    public function compactArray(array $groups) : string
    {
        $parts = [];
        foreach ($groups as $group) {
            foreach ($group as $record) {
                $parts[] = implode(
                    '|',
                    [
                        $record[self::EXP_FIELD_ID],
                        $record[self::EXP_FIELD_STARTDATE],
                        $record[self::EXP_FIELD_ENDDATE]
                    ]
                );
            }
        }

        return implode('*', $parts);
    }

    /**
     * Gets the active groups
     */
    public function getActiveGroupIds(string $fieldValue): array
    {
        $groups = $this->convertIntoArray($fieldValue);
        $result = [];
        if (!empty($groups)) {
            $now = time();
            foreach ($groups as $groupId => $groupRecords) {
                if (empty($groupRecords)) {
                    continue;
                }
                $currGroup = [];
                foreach ($groupRecords as $group) {
                    if ($group[self::EXP_FIELD_STARTDATE] > 0 && $group[self::EXP_FIELD_STARTDATE] > $now) {
                        continue;
                    }
                    if ($group[self::EXP_FIELD_ENDDATE] > 0 && $now > $group[self::EXP_FIELD_ENDDATE]) {
                        continue;
                    }
                    $currGroup[] = $group;
                }
                if (empty($currGroup)) {
                    continue;
                }
                $result[$groupId] = $currGroup;
            }
        }

        return $result;
    }

    /**
     * @param int $userId
     *
     * @return string
     * @deprecated use \BPN\ExpiringFeGroups\Domain\Repository\FrontEndUserRepository::getExpiringGroupsByUserId
     */
    public function getExpiringGroupsByUserId(int $userId)
    {
        return $this->getFrontEndUserRepository()->getExpiringGroupsByUserId($userId);
    }

    protected function getFrontEndUserRepository() : FrontEndUserRepository
    {
        /* @var FrontEndUserRepository $frontEndUserGroupRepository */
        return GeneralUtility::makeInstance(ObjectManager::class)
            ->get(FrontEndUserRepository::class);
    }

}
