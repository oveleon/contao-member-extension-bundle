<?php

/*
 * This file is part of Oveleon ContaoMemberExtension Bundle.
 *
 * (c) https://www.oveleon.de/
 */

array_insert($GLOBALS['TL_DCA']['tl_module']['palettes'], 0, array
(
    'avatar'       => '{title_legend},name,headline,type;{source_legend},imgSize;{template_legend:hide},memberTpl;{protected_legend:hide},protected;{expert_legend:hide},guests,cssID',
    'memberList'   => '{title_legend},name,headline,type;{config_legend},groups,memberFields,imgSize;{redirect_legend},jumpTo;{template_legend:hide},customTpl,memberListTpl;{protected_legend:hide},protected;{expert_legend:hide},guests,cssID',
    'memberReader' => '{title_legend},name,headline,type;{config_legend},groups,memberFields,imgSize;{template_legend:hide},customTpl,memberReaderTpl;{protected_legend:hide},protected;{expert_legend:hide},guests,cssID'
));

$GLOBALS['TL_DCA']['tl_module']['fields']['memberListTpl'] = array
(
    'exclude'                 => true,
    'inputType'               => 'select',
    'options_callback' => static function ()
    {
        return Contao\Controller::getTemplateGroup('member_list_');
    },
    'eval'                    => array('includeBlankOption'=>true, 'chosen'=>true, 'tl_class'=>'w50'),
    'sql'                     => "varchar(64) NOT NULL default ''"
);

$GLOBALS['TL_DCA']['tl_module']['fields']['memberReaderTpl'] = array
(
    'exclude'                 => true,
    'inputType'               => 'select',
    'options_callback' => static function ()
    {
        return Contao\Controller::getTemplateGroup('member_reader_');
    },
    'eval'                    => array('includeBlankOption'=>true, 'chosen'=>true, 'tl_class'=>'w50'),
    'sql'                     => "varchar(64) NOT NULL default ''"
);

$GLOBALS['TL_DCA']['tl_module']['fields']['memberFields'] = array
(
    'exclude'                 => true,
    'inputType'               => 'checkboxWizard',
    'options_callback'        => array('tl_module_extension', 'getMemberProperties'),
    'eval'                    => array('multiple'=>true),
    'sql'                     => "blob NULL"
);


/**
 * Provide miscellaneous methods that are used by the data configuration array.
 *
 * @author Daniele Sciannimanica <https://github.com/doishub>
 */
class tl_module_extension extends Contao\Backend
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
        if ($this->User->isAdmin) {
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
        $return = array();

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
}
