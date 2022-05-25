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

use Contao\Backend;
use Contao\Controller;
use Contao\System;

System::loadLanguageFile('tl_member_settings');

// Add palettes to tl_module
// ToDo: Change to ArrayUtil::arrayInsert in the future
array_insert($GLOBALS['TL_DCA']['tl_module']['palettes'], 0, [
    'avatar' => '{title_legend},name,headline,type;{source_legend},imgSize;{template_legend:hide},customTpl;{protected_legend:hide},protected;{expert_legend:hide},guests,cssID',
    'deleteAvatar' => '{title_legend},name,headline,type;{template_legend:hide},customTpl;{protected_legend:hide},protected;{expert_legend:hide},guests,cssID',
    'memberList' => '{title_legend},name,headline,type;{config_legend},ext_order,ext_orderField,,numberOfItems,perPage,ext_groups,memberFields,imgSize;{redirect_legend},jumpTo;{template_legend:hide},customTpl,memberListTpl;{protected_legend:hide},protected;{expert_legend:hide},guests,cssID',
    'memberReader' => '{title_legend},name,headline,type;{config_legend},ext_groups,memberFields,imgSize;{template_legend:hide},customTpl,memberReaderTpl;{protected_legend:hide},protected;{expert_legend:hide},guests,cssID'
]);

$GLOBALS['TL_DCA']['tl_module']['fields']['memberListTpl'] = [
    'exclude' => true,
    'inputType' => 'select',
    'options_callback' => static fn () => Controller::getTemplateGroup('memberExtension_list_'),
    'eval' => ['includeBlankOption'=>true, 'chosen'=>true, 'tl_class'=>'w50'],
    'sql' => "varchar(64) NOT NULL default ''"
];

$GLOBALS['TL_DCA']['tl_module']['fields']['memberReaderTpl'] = [
    'exclude' => true,
    'inputType' => 'select',
    'options_callback' => static fn () => Controller::getTemplateGroup('memberExtension_reader_'),
    'eval' => ['includeBlankOption'=>true, 'chosen'=>true, 'tl_class'=>'w50'],
    'sql' => "varchar(64) NOT NULL default ''"
];

$GLOBALS['TL_DCA']['tl_module']['fields']['ext_order'] = [
    'exclude' => true,
    'inputType' => 'select',
    'options' => ['order_random', 'order_asc', 'order_desc'],
    'reference' => &$GLOBALS['TL_LANG']['tl_member_settings'],
    'eval' => ['tl_class'=>'w50 clr', 'includeBlankOption'=>true, 'chosen'=>true,],
    'sql' => "varchar(32) NOT NULL default ''"
];

$GLOBALS['TL_DCA']['tl_module']['fields']['ext_orderField'] = [
    'exclude' => true,
    'inputType' => 'select',
    'options_callback' => ['tl_module_extension', 'getViewableMemberFields'],
    'eval' => ['tl_class'=>'w50', 'includeBlankOption'=>true, 'chosen'=>true,],
    'sql' => "varchar(32) NOT NULL default ''"
];

$GLOBALS['TL_DCA']['tl_module']['fields']['memberFields'] = [
    'exclude' => true,
    'inputType' => 'checkboxWizard',
    'options_callback' => ['tl_module_extension', 'getMemberProperties'],
    'eval' => ['multiple'=>true, 'tl_class'=>'clr'],
    'sql' => "blob NULL"
];

$GLOBALS['TL_DCA']['tl_module']['fields']['ext_groups'] = [
    'exclude' => true,
    'inputType' => 'checkbox',
    'foreignKey' => 'tl_member_group.name',
    'eval' => ['multiple'=>true, 'tl_class'=>'clr'],
    'sql' => "blob NULL",
    'relation' => ['type'=>'hasMany', 'load'=>'lazy']
];

class tl_module_extension extends Backend
{
    /**
     * Import the back end user object
     */
    public function __construct()
    {
        parent::__construct();
        $this->import('Contao\BackendUser', 'User');
    }

    /**
     * Check permissions to edit the table
     *
     * @throws Contao\CoreBundle\Exception\AccessDeniedException
     */
    public function checkPermission()
    {
        if ($this->User->isAdmin)
        {
            return;
        }

        if (!$this->User->hasAccess('modules', 'themes')) {
            throw new Contao\CoreBundle\Exception\AccessDeniedException('Not enough permissions to access the front end modules module.');
        }
    }

    /**
     * Return all fields of table tl_member without account data
     *
     * @return array
     */
    public function getMemberProperties()
    {
        $return = [];

        Contao\System::loadLanguageFile('tl_member');
        $this->loadDataContainer('tl_member');

        foreach ($GLOBALS['TL_DCA']['tl_member']['fields'] as $k=>$v)
        {
            if (!empty($v['inputType']) && $v['inputType'] !== 'password')
            {
                $return[$k] = $GLOBALS['TL_DCA']['tl_member']['fields'][$k]['label'][0];
            }
        }

        return $return;
    }

    /**
     * Return all sortable fields of table tl_member
     *
     * @return array
     */
    public function getViewableMemberFields()
    {
        $return = [];

        Contao\System::loadLanguageFile('tl_member');
        $this->loadDataContainer('tl_member');

        foreach ($GLOBALS['TL_DCA']['tl_member']['fields'] as $k=>$v)
        {
            if (!empty($v['inputType']) && $v['eval']['feViewable'] === true && $k !== 'avatar')
            {
                $return[$k] = $GLOBALS['TL_DCA']['tl_member']['fields'][$k]['label'][0] . ' ['.$k.']';
            }
        }

        return $return;
    }
}
