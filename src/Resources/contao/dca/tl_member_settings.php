<?php

/*
 * This file is part of Oveleon ContaoMemberExtension Bundle.
 *
 * (c) https://www.oveleon.de/
 */

$GLOBALS['TL_DCA']['tl_member_settings'] = array
(

	// Config
	'config' => array
	(
		'dataContainer'               => 'File',
		'closed'                      => true
	),

	// Palettes
	'palettes' => array
	(
		'default'                     => '{avatar_legend},defaultAvatar;'
	),

	// Fields
	'fields' => array
	(
		'defaultAvatar' => array
		(
            'label'                   => &$GLOBALS['TL_LANG']['tl_member_settings']['defaultAvatar'],
            'inputType'               => 'fileTree',
            'eval'                    => array('fieldType'=>'radio', 'filesOnly'=>true, 'isGallery'=>true, 'extensions'=>Config::get('validImageTypes'), 'tl_class'=>'clr')
		)
	)
);
