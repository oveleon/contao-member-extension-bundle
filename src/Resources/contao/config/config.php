<?php

/*
 * This file is part of Oveleon ContaoMemberExtension Bundle.
 *
 * (c) https://www.oveleon.de/
 */

// Back end modules
$GLOBALS['BE_MOD']['system']['member_settings'] = array
(
    'tables'            => array('tl_member_settings'),
    'hideInNavigation'  => true,
);

// Front end modules
array_insert($GLOBALS['FE_MOD']['user'], -1, array
(
    'avatar'       => 'Oveleon\ContaoMemberExtensionBundle\ModuleAvatar',
    'memberList'   => 'Oveleon\ContaoMemberExtensionBundle\ModuleMemberList',
    'memberReader' => 'Oveleon\ContaoMemberExtensionBundle\ModuleMemberReader'
));

// Register hooks
$GLOBALS['TL_HOOKS']['updatePersonalData'][] = array('Oveleon\ContaoMemberExtensionBundle\Member', 'updateAvatar');

// Style sheet
if (TL_MODE == 'BE')
{
    $GLOBALS['TL_CSS'][] = 'bundles/contaomemberextension/style.css|static';
}
