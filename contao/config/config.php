<?php

declare(strict_types=1);

/*
 * This file is part of Oveleon ContaoMemberExtension Bundle.
 *
 * @package     contao-member-extension-bundle
 * @license     MIT
 * @author      Sebastian Zoglowek     <https://github.com/zoglo>
 * @author      Daniele Sciannimanica  <https://github.com/doishub>
 * @author      Fabian Ekert           <https://github.com/eki89>
 * @copyright   Oveleon                <https://www.oveleon.de/>
 */

// Back end modules
use Contao\ArrayUtil;

$GLOBALS['BE_MOD']['system']['member_settings'] = [
    'tables'            => ['tl_member_settings'],
    'hideInNavigation'  => true,
];

// Front end modules
ArrayUtil::arrayInsert($GLOBALS['FE_MOD']['user'], -1, [
    'avatar'       => 'Oveleon\ContaoMemberExtensionBundle\ModuleAvatar',
    'deleteAvatar' => 'Oveleon\ContaoMemberExtensionBundle\ModuleDeleteAvatar',
    'memberList'   => 'Oveleon\ContaoMemberExtensionBundle\ModuleMemberList',
    'memberReader' => 'Oveleon\ContaoMemberExtensionBundle\ModuleMemberReader'
]);

// Register hooks
$GLOBALS['TL_HOOKS']['createNewUser'][] =      ['Oveleon\ContaoMemberExtensionBundle\Member', 'createAvatar'];
$GLOBALS['TL_HOOKS']['updatePersonalData'][] = ['Oveleon\ContaoMemberExtensionBundle\Member', 'updateAvatar'];
