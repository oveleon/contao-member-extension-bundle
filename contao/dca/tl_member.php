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

use Contao\Config;
use Contao\CoreBundle\DataContainer\PaletteManipulator;

// Extend the default palette
PaletteManipulator::create()
    ->addField(['avatar'], 'personal_legend', PaletteManipulator::POSITION_APPEND)
    ->applyToPalette('default', 'tl_member')
;

// Add global operations
$GLOBALS['TL_DCA']['tl_member']['list']['global_operations']['settings'] = [
    'label'         => &$GLOBALS['TL_LANG']['tl_member']['settings'],
    'href'          => 'do=member_settings',
    'icon'          => 'edit.svg',
    'attributes'    => 'onclick="Backend.getScrollOffset()" accesskey="e"'
];

// Add fields to tl_user
$GLOBALS['TL_DCA']['tl_member']['fields']['avatar'] = [
    'label'         => &$GLOBALS['TL_LANG']['tl_member']['avatar'],
    'exclude'       => true,
    'inputType'     => 'fileTree',
    'eval'          => ['feEditable'=>true, 'feViewable'=>true, 'feGroup'=>'personal', 'fieldType'=>'radio', 'filesOnly'=>true, 'isGallery'=>true, 'extensions'=>Config::get('validImageTypes'), 'tl_class'=>'clr'],
    'sql'           => "binary(16) NULL"
];
