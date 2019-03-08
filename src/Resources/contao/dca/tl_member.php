<?php

/*
 * This file is part of Oveleon ContaoMemberExtension Bundle.
 *
 * (c) https://www.oveleon.de/
 */

// Extend the default palette
Contao\CoreBundle\DataContainer\PaletteManipulator::create()
    ->addField(array('avatar'), 'personal_legend', Contao\CoreBundle\DataContainer\PaletteManipulator::POSITION_APPEND)
    ->applyToPalette('default', 'tl_member')
;

// Add global operations
array_insert($GLOBALS['TL_DCA']['tl_member']['list']['global_operations'], 0, array
(
    'settings' => array
    (
        'label'               => &$GLOBALS['TL_LANG']['tl_member']['settings'],
        'href'                => 'do=member_settings',
        'icon'                => 'edit.svg',
        'attributes'          => 'onclick="Backend.getScrollOffset()" accesskey="e"'
    )
));

// Add fields to tl_user
$GLOBALS['TL_DCA']['tl_member']['fields']['avatar'] = array
(
    'label'                   => &$GLOBALS['TL_LANG']['tl_member']['avatar'],
    'exclude'                 => true,
    'inputType'               => 'fileTree',
    'eval'                    => array('feEditable'=>true, 'feViewable'=>true, 'feGroup'=>'personal', 'fieldType'=>'radio', 'filesOnly'=>true, 'isGallery'=>true, 'extensions'=>Config::get('validImageTypes'), 'tl_class'=>'clr'),
    'sql'                     => "binary(16) NULL"
);