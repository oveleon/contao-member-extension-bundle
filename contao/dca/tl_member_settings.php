<?php

declare(strict_types=1);

use Contao\DC_File;

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

$GLOBALS['TL_DCA']['tl_member_settings'] = [

    'config' => [
        'dataContainer' => DC_File::class,
        'closed' => true
    ],

    'palettes' => ['default' => '{avatar_legend},defaultAvatar;'],

    'fields' => [
        'defaultAvatar' => [
            'inputType' => 'fileTree',
            'eval' => ['fieldType' => 'radio', 'filesOnly' => true, 'isGallery' => true, 'extensions' => '%contao.image.valid_extensions%', 'tl_class' => 'clr']
        ]
    ]
];
