<?php

namespace BPN\ExpiringFeGroups\Backend\UserFunction;

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

use BPN\ExpiringFeGroups\Domain\Repository\FrontEndUserGroupRepository;
use TYPO3\CMS\Backend\Form\Container\SingleFieldContainer;
use TYPO3\CMS\Backend\Form\Element\AbstractFormElement;
use TYPO3\CMS\Backend\Form\NodeFactory;
use TYPO3\CMS\Backend\Template\Components\Buttons\LinkButton;
use TYPO3\CMS\Core\Imaging\Icon;
use TYPO3\CMS\Core\Imaging\IconFactory;
use TYPO3\CMS\Core\Localization\LanguageService;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Object\ObjectManager;

/**
 * The TCA field for selecting the groups & dates.
 *
 * @author    Sander Leeuwesteijn | iTypo <info@itypo.nl>
 * @author    Radu Cocieru <raducocieru@hotmail.com>
 */
class ItemSelector extends AbstractFormElement
{
    public function render() : array
    {
        $parameterArray = $this->data['parameterArray'];

        $fieldInformationResult = $this->renderFieldInformation();
        $resultArray = $this->mergeChildReturnIntoExistingResult(
            $this->initializeResultArray(),
            $fieldInformationResult,
            false
        );

        $userGroups = $this->getFrontEndUserGroupRepository()->getUserGroups();
        $groups = $this->getSelectOptions($userGroups, 'uid', 'title');

        $currentSelectedExpiringGroupsFieldValues = explode(
            '*',
            $this->data['databaseRow']['tx_expiringfegroups_groups']
        );

        $selectedExpiringGroupOptions = [];
        foreach ($currentSelectedExpiringGroupsFieldValues as $expiringGroupValue) {
            if (empty($expiringGroupValue)) {
                continue;
            }

            [$groupId, $startTimeStamp, $endTimeStamp] = explode('|', $expiringGroupValue);
            $groupId = (int)$groupId;
            if (empty($groupId)) {
                continue;
            }

            $title = sprintf('INVALID VALUE [%d]', $groupId);
            if (array_key_exists($groupId, $userGroups)) {
                $title = $userGroups[$groupId]['title'];
            }

            $to = $this->translate('fe_users.to');

            $selectedExpiringGroupOptions[] =
                sprintf(
                    "<option value='%s'>%s (%s %s %s)</option>",
                    htmlspecialchars($expiringGroupValue),
                    $title,
                    !empty($startTimeStamp) ? $this->convertToDateTime($startTimeStamp) : '',
                    $to,
                    !empty($endTimeStamp) ? $this->convertToDateTime($endTimeStamp) : ''
                );
        }

        $resultArray['html'] = $this->renderControl($selectedExpiringGroupOptions, $groups, $parameterArray);

        return $resultArray;
    }

    /**
     * @param string[] $currentValues
     * @param string[] $groups
     * @param array    $paramArray
     *
     * @return string
     */
    protected function renderControl($currentValues, $groups, $paramArray)
    {
        /** @var IconFactory $iconFactory */
        $iconFactory = GeneralUtility::makeInstance(IconFactory::class);

        $removeButton = GeneralUtility::makeInstance(LinkButton::class);
        $removeButton
            ->setHref('#')
            ->setTitle($this->translate('fe_users.removegroup'))
            ->setClasses('action-button-delete')
            ->setIcon($iconFactory->getIcon('actions-edit-delete', Icon::SIZE_SMALL));

        $leftSelect = sprintf(
            '<select size="10" data-name="tx_exp_gr_ui" class="form-control tceforms-multiselect">%s</select>',
            implode(PHP_EOL, $currentValues)
        );

        $leftSelect = $this->wrapInDiv($leftSelect, 'form-wizards-element');
        $actionButtons = $this->wrapInDiv(
            $this->wrapInDiv(
                $removeButton->render(),
                'btn-group-vertical'
            ),
            'form-wizards-items'
        );

        $leftColumn = [
            sprintf('<label>%s</label>', $this->translate('fe_users.selected')),
            $this->wrapInDiv(
                [$leftSelect, $actionButtons],
                'form-wizards-wrap form-wizards-aside'
            ),
        ];

        $rightColumn = [
            '<div class="form-multigroup-item form-multigroup-element">',
            sprintf('<div><label>%s</label></div>', $this->translate('fe_users.available')),
            $this->inlineLabel($this->translate('fe_users.groups')),
            $this->inlineElement(
                sprintf('<select size="1" data-name="tx_exp_gr_select">%s</select>', implode(PHP_EOL, $groups))
            ),
            $this->inlineLabel($this->translate('fe_users.from')),
            $this->inlineElement($this->getDateFormField('tx_exp_gr_st', 'Startdate', time() - 86400)),
            $this->inlineLabel($this->translate('fe_users.until')),
            $this->inlineElement($this->getDateFormField('tx_exp_gr_ed', 'EndDate', time() + 86400)),
            sprintf(
                '<div class="button-bar"><input type="button" value="%s" class="action-button-add"/></div>',
                $this->translate('fe_users.add')
            ),
            '</div>',
        ];

        $content = [
            sprintf(
                '<div class="form-multigroup-item form-multigroup-element">%s</div>',
                implode(PHP_EOL, $leftColumn)
            ),
            sprintf(
                '<div class="form-multigroup-item form-multigroup-element">%s</div>',
                implode(PHP_EOL, $rightColumn)
            ),
            sprintf(
                '<div class="format" style="display:none">###TITLE### (###FROMSTRING### %s ###TOSTRING###)</div>',
                strtolower($this->translate('fe_users.until'))
            ),
        ];

        $currentValue = htmlspecialchars($paramArray['itemFormElValue']);
        $controlName = $paramArray['itemFormElName'];
        $content[] = sprintf(
            '<input type="hidden" value="%s" name="%s" class="hidden-tx_expiringfegroups_groups" />',
            $currentValue,
            $controlName
        );

        return sprintf(
            '<div class="form-multigroup-wrap t3js-formengine-field-group module-tx_expiringfegroups_groups">%s</div>',
            implode(PHP_EOL, $content)
        );
    }

    protected function getDateFormField(string $fieldName, string $title = 'date', int $defaultTime = 0) : string
    {
        $fieldConfig = [
            'fieldConf'           => [
                'config' => [
                    'type'       => 'input',
                    'renderType' => 'inputDateTime',
                    'size'       => 12,
                    'eval'       => 'datetime,int',
                    'default'    => $defaultTime,
                    'checkbox'   => 0,
                    'form_type'  => 'input',
                ],
            ],
            'label'               => $title,
            'fieldTSConfig'       => '',
            'itemFormElName'      => $fieldName,
            'itemFormElValue'     => 0,
            'itemFormElName_file' => $fieldName,
            'onFocus'             => '',
            'fieldChangeFunc'     => [
                'TBE_EDITOR_fieldChanged' => '',
                'alert'                   => '',
            ],
        ];

        $table = 'fe_users';
        $databaseRow = $this->data['databaseRow'];
        $data = array_merge_recursive(
            $fieldConfig,
            [
                'tableName'       => $table,
                'fieldName'       => $fieldName,
                'databaseRow'     => $databaseRow,
                'processedTca'    => [
                    'columns' => [$fieldName => $fieldConfig['fieldConf']],
                ],
                'inlineStructure' => [],
            ]
        );

        $singleFieldContainer = new SingleFieldContainer(
            new NodeFactory(), $data
        );
        $result = $singleFieldContainer->render();

        return $result['html'];
    }

    /**
     * @param string|array $value
     */
    protected function wrapInDiv($value, $class) : string
    {
        $content = is_array($value)
            ? implode('', $value)
            : $value;

        return sprintf('<div class="%s">%s</div>', $class, $content);
    }

    /**
     * converts an array to options for a select.
     */
    protected function getSelectOptions(array $userGroups, string $valueField, string $titleField) : array
    {
        $result = [];

        foreach ($userGroups as $row) {
            $result[] = sprintf(
                "<option value='%s'>%s</option>",
                htmlspecialchars($row[$valueField]),
                $row[$titleField]
            );
        }

        return $result;
    }

    protected function convertToDateTime(int $timeStamp) : string
    {
        $format = '%d-%m-%Y';
        if ('00:00' !== strftime('%H:%M', $timeStamp)) {
            $format = '%H:%M %d-%m-%Y';
        }

        return strftime($format, $timeStamp);
    }

    protected function translate(string $key) : string
    {
        /** @var LanguageService $lang */
        $lang = $GLOBALS['LANG'];

        return $lang->sL('LLL:EXT:bpn_expiring_fe_groups/Resources/Private/Language/locallang_db.xlf:' . $key);
    }

    protected function inlineLabel(string $text) : string
    {
        return sprintf('<label class="inline-label">%s</label>', $text);
    }

    protected function inlineElement(string $text) : string
    {
        return $this->wrapInDiv($text, 'inline-element');
    }

    protected function getFrontEndUserGroupRepository() : FrontEndUserGroupRepository
    {
        /* @var FrontEndUserGroupRepository $frontEndUserGroupRepository */
        return GeneralUtility::makeInstance(ObjectManager::class)
            ->get(FrontEndUserGroupRepository::class);
    }
}
