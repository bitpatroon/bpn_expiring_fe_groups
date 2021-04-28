<?php

$EM_CONF[$_EXTKEY] = [
    'title'              => 'Bpn Expiring FE Groups',
    'description'        => 'Allows to give an fe_group to an fe_user for a specified amount of time.',
    'category'           => 'fe',
    'version'            => '10.4',
    'state'              => 'stable',
    'author'             => 'Sjoerd Zonneveld | Bitpatroon, Sander Leeuwesteijn | iTypo, Radu Cocieru',
    'author_email'       => 'info@itypo.nl',
    'author_company'     => 'iTypo',
    'CGLcompliance'      => '',
    'CGLcompliance_note' => '',
    'constraints'        => [
        'depends'   => [
            'typo3' => '10.4.0 - 10.9.99'
        ],
        'conflicts' => [
            'rc_expiring_fe_groups'    => '',
            'itypo_expiring_fe_groups' => '',
        ],
    ],
];
