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

$GLOBALS['TL_DCA']['tl_member_settings'] = [

    'config' => [
        'dataContainer' => 'File',
        'closed' => true
    ],

    'palettes' => ['default' =>'{avatar_legend},defaultAvatar;'],

    'fields' => [
        'defaultAvatar' => [
            'label' => &$GLOBALS['TL_LANG']['tl_member_settings']['defaultAvatar'],
            'inputType' => 'fileTree',
            'eval' => array('fieldType'=>'radio', 'filesOnly'=>true, 'isGallery'=>true, 'extensions'=>Config::get('validImageTypes'), 'tl_class'=>'clr')
        ]
    ]
];
