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

use Contao\Controller;
use Contao\System;
use Oveleon\ContaoMemberExtensionBundle\EventListener\DataContainer\MemberFieldsOptionsListener;

System::loadLanguageFile('tl_member_settings');

// Add palettes to tl_module
$GLOBALS['TL_DCA']['tl_module']['palettes']['avatar'] = '{title_legend},name,headline,type;{source_legend},imgSize;{template_legend:hide},customTpl;{protected_legend:hide},protected;{expert_legend:hide},guests,cssID';
$GLOBALS['TL_DCA']['tl_module']['palettes']['deleteAvatar'] = '{title_legend},name,headline,type;{template_legend:hide},customTpl;{protected_legend:hide},protected;{expert_legend:hide},guests,cssID';
$GLOBALS['TL_DCA']['tl_module']['palettes']['memberList'] = '{title_legend},name,headline,type;{config_legend},ext_order,ext_orderField,numberOfItems,perPage,ext_groups,memberFields,imgSize,ext_activateFilter,ext_parseDetails,ext_memberAlias;{redirect_legend},jumpTo;{template_legend:hide},customTpl,memberListTpl;{protected_legend:hide},protected;{expert_legend:hide},guests,cssID';
$GLOBALS['TL_DCA']['tl_module']['palettes']['memberReader'] = '{title_legend},name,headline,type;{config_legend},ext_groups,memberFields,imgSize,ext_parseDetails,overviewPage,customLabel;{template_legend:hide},customTpl,memberReaderTpl;{protected_legend:hide},protected;{expert_legend:hide},guests,cssID';

$GLOBALS['TL_DCA']['tl_module']['fields']['memberListTpl'] = [
    'exclude' => true,
    'inputType' => 'select',
    'options_callback' => static fn() => Controller::getTemplateGroup('memberExtension_list_'),
    'eval' => ['includeBlankOption' => true, 'chosen' => true, 'tl_class' => 'w50'],
    'sql' => "varchar(64) NOT NULL default ''"
];

$GLOBALS['TL_DCA']['tl_module']['fields']['memberReaderTpl'] = [
    'exclude' => true,
    'inputType' => 'select',
    'options_callback' => static fn() => Controller::getTemplateGroup('memberExtension_reader_'),
    'eval' => ['includeBlankOption' => true, 'chosen' => true, 'tl_class' => 'w50'],
    'sql' => "varchar(64) NOT NULL default ''"
];

$GLOBALS['TL_DCA']['tl_module']['fields']['ext_order'] = [
    'exclude' => true,
    'inputType' => 'select',
    'options' => ['order_random', 'order_asc', 'order_desc'],
    'reference' => &$GLOBALS['TL_LANG']['tl_member_settings'],
    'eval' => ['tl_class' => 'w50 clr', 'includeBlankOption' => true, 'chosen' => true],
    'sql' => "varchar(32) NOT NULL default ''"
];

$GLOBALS['TL_DCA']['tl_module']['fields']['ext_orderField'] = [
    'exclude' => true,
    'inputType' => 'select',
    'eval' => ['tl_class' => 'w50', 'includeBlankOption' => true, 'chosen' => true],
    'sql' => "varchar(32) NOT NULL default ''"
];

$GLOBALS['TL_DCA']['tl_module']['fields']['memberFields'] = [
    'exclude' => true,
    'inputType' => 'checkboxWizard',
    'eval' => ['multiple' => true, 'tl_class' => 'clr'],
    'sql' => "blob NULL"
];

$GLOBALS['TL_DCA']['tl_module']['fields']['ext_parseDetails'] = [
    'exclude' => true,
    'inputType' => 'checkbox',
    'eval' => ['tl_class' => 'w50 clr'],
    'sql' => "char(1) NOT NULL default ''"
];

$GLOBALS['TL_DCA']['tl_module']['fields']['ext_memberAlias'] = [
    'exclude' => true,
    'inputType' => 'checkbox',
    'eval' => ['tl_class' => 'w50'],
    'sql' => "char(1) NOT NULL default ''"
];

$GLOBALS['TL_DCA']['tl_module']['fields']['ext_groups'] = [
    'exclude' => true,
    'inputType' => 'checkbox',
    'foreignKey' => 'tl_member_group.name',
    'eval' => ['multiple' => true, 'tl_class' => 'clr'],
    'sql' => "blob NULL",
    'relation' => ['type' => 'hasMany', 'load' => 'lazy']
];

$GLOBALS['TL_DCA']['tl_module']['fields']['ext_activateFilter'] = [
    'exclude' => true,
    'inputType' => 'checkbox',
    'eval' => ['tl_class' => 'w50 m12'],
    'sql' => "char(1) NOT NULL default ''"
];
