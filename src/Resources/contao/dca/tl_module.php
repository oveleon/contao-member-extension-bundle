<?php

/*
 * This file is part of Oveleon ContaoMemberExtension Bundle.
 *
 * (c) https://www.oveleon.de/
 */

array_insert($GLOBALS['TL_DCA']['tl_module']['palettes'], 0, array
(
    'avatar' => '{title_legend},name,headline,type;{source_legend},imgSize;{template_legend:hide},memberTpl;{protected_legend:hide},protected;{expert_legend:hide},guests,cssID'
));
