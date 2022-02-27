<?php

declare(strict_types=1);

/*
 * This file is part of Oveleon ContaoMemberExtension Bundle.
 *
 * @package     contao-member-extension-bundle
 * @license     MIT
 * @author      Daniele Sciannimanica   <https://github.com/doishub>
 * @author      Fabian Ekert            <https://github.com/eki89>
 * @author      Sebastian Zoglowek      <https://github.com/zoglo>
 * @copyright   Oveleon                 <https://www.oveleon.de/>
 */

// Back end modules
use Contao\System;

$GLOBALS['BE_MOD']['system']['member_settings'] = array
(
    'tables'            => ['tl_member_settings'],
    'hideInNavigation'  => true,
);

// Front end modules
// ToDo: Change to ArrayUtil::arrayInsert in the future
array_insert($GLOBALS['FE_MOD']['user'], -1, [
    'avatar'       => 'Oveleon\ContaoMemberExtensionBundle\ModuleAvatar',
    'deleteAvatar' => 'Oveleon\ContaoMemberExtensionBundle\ModuleDeleteAvatar',
    'memberList'   => 'Oveleon\ContaoMemberExtensionBundle\ModuleMemberList',
    'memberReader' => 'Oveleon\ContaoMemberExtensionBundle\ModuleMemberReader'
]);

// Register hooks
$GLOBALS['TL_HOOKS']['createNewUser'][] = ['Oveleon\ContaoMemberExtensionBundle\Member', 'createAvatar'];
$GLOBALS['TL_HOOKS']['updatePersonalData'][] = ['Oveleon\ContaoMemberExtensionBundle\Member', 'updateAvatar'];

// Style sheet
$request = System::getContainer()->get('request_stack')->getCurrentRequest();

if ($request && System::getContainer()->get('contao.routing.scope_matcher')->isBackendRequest($request))
{
    $GLOBALS['TL_CSS'][] = 'bundles/contaomemberextension/style.css|static';
}
