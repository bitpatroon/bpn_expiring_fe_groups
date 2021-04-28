<?php

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2021 Sjoerd Zonneveld  <code@bitpatroon.nl>
 *  Date: 29-3-2021 12:57
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

namespace BPN\ExpiringFeGroups\Domain\Repository;

use BPN\ExpiringFeGroups\Domain\Model\FrontEndUserGroup;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class FrontEndUserGroupRepository extends \TYPO3\CMS\Extbase\Domain\Repository\FrontendUserGroupRepository
{
    public function findFirstByMailDomain(string $mailDomain)
    {
        //$table = self::TABLE;
        $table = 'fe_groups';
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getQueryBuilderForTable($table);

        $queryBuilder
            ->select('*')
            ->from($table)
            ->where(
                $queryBuilder->expr()->inSet(
                    'maildomains',
                    $queryBuilder->createNamedParameter(
                        $mailDomain,
                        Connection::PARAM_STR
                    )
                ),
            );

        // retrieve single record
        $groupId = $queryBuilder->execute()->fetchOne();
        if ($groupId) {
            return $this->findByUid($groupId);
        }

        return null;
    }

    /**
     * @param array $ids
     * @param null  $all
     *
     * 1,3,4
     *
     * 1 -> 2
     * 2 -> 3
     * 3
     * 4 -> 6, 8
     *
     * 1,2,3,4,6,8
     *
     * @return FrontEndUserGroup[]
     */
    public function getAllGroupsAndSubGroups(array $ids)
    {
        if ($ids == null) {
            return [];
        }

        $all = $this->getAll();

        //todo: implement

        $result = [];
        $this->getAllGroupsAndSubGroupsInner($ids, $all, $result);
        return $result;
    }

    protected function getAllGroupsAndSubGroupsInner(array $ids, array $all = [], array &$result = []){
        if(!$all || !$ids){
            return;
        }

        foreach ($ids as $id) {
            if ($all[$id]) {
                $result[$id] = $all[$id];
                $subgroups = $all[$id]['subgroup'];
                if($subgroups){
                    $this->getAllGroupsAndSubGroupsInner(GeneralUtility::intExplode(',', $subgroups), $all, $result);
                }
            }
        }
    }

    /**
     * Gets the usergroups from the database
     *
     * @return array|NULL
     */
    public function getUserGroups()
    {
        /** Connection $connection */
        $connection = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(ConnectionPool  ::class)
            ->getConnectionForTable('fe_groups');

        $groups = $connection->select(
            ['uid', 'title'],
            'fe_groups',
            ['deleted' => 0],
            [],
            ['title' => 'ASC']
        );

        $result = [];
        foreach ($groups as $group) {
            $id = (int)$group['uid'];
            $result[$id] = $group;
        }

        return $result;
    }

    private function getAll()
    {
        /** Connection $connection */
        $connection = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(ConnectionPool::class)
            ->getConnectionForTable('fe_groups');
        $groups = $connection->select(['*'], 'fe_groups')->fetchAllAssociative();
        $result = [];
        foreach ($groups as $group) {
            $uid = $group['uid'];
            $result[$uid] = $group;
        }

        return $result;
    }


}
