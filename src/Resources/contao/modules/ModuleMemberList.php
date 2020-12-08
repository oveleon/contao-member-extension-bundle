<?php

/*
 * This file is part of Oveleon ContaoMemberExtension Bundle.
 *
 * (c) https://www.oveleon.de/
 */

namespace Oveleon\ContaoMemberExtensionBundle;

use Contao\BackendTemplate;
use Contao\FrontendTemplate;
use Contao\MemberModel;
use Contao\StringUtil;
use Patchwork\Utf8;

/**
 * Class ModuleMemberList
 *
 * @author Daniele Sciannimanica <https://github.com/doishub>
 */
class ModuleMemberList extends ModuleMemberExtension
{

	/**
	 * Template
	 * @var string
	 */
	protected $strTemplate = 'mod_memberList';

	/**
	 * Template
	 * @var string
	 */
	protected $strMemberTemplate = 'member_list_default';

	/**
	 * Return a wildcard in the back end
	 *
	 * @return string
	 */
	public function generate()
	{
		if (TL_MODE == 'BE')
		{
			/** @var BackendTemplate|object $objTemplate */
			$objTemplate = new BackendTemplate('be_wildcard');

			$objTemplate->wildcard = '### ' . Utf8::strtoupper($GLOBALS['TL_LANG']['FMD']['memberList'][0]) . ' ###';
			$objTemplate->title = $this->headline;
			$objTemplate->id = $this->id;
			$objTemplate->link = $this->name;
			$objTemplate->href = 'contao/main.php?do=themes&amp;table=tl_module&amp;act=edit&amp;id=' . $this->id;

			return $objTemplate->parse();
		}

		return parent::generate();
	}

	/**
	 * Generate the module
	 */
	protected function compile()
	{
        $objGroups = MemberModel::findAll();
        $arrGroups = StringUtil::deserialize($this->groups);
        $arrMembers = null;

        if($objGroups->count())
        {
            while($objGroups->next())
            {
                $memberGroups = StringUtil::deserialize($objGroups->groups);

                if($objGroups->disable || empty($arrGroups) || !\is_array($arrGroups) || !\count(array_intersect($arrGroups, $memberGroups)))
                {
                    continue;
                }

                $arrMemberFields = StringUtil::deserialize($this->memberFields, true);

                $objTemplate = new FrontendTemplate($this->memberListTpl ?: $this->strMemberTemplate);
                $objTemplate->setData($objGroups->current()->row());

                $arrMembers[] = $this->parseMemberTemplate($objGroups->current(), $objTemplate, $arrMemberFields, $this->imgSize);
            }
        }

        if(null === $arrMembers)
        {
            $this->Template->empty = $GLOBALS['TL_LANG']['MSC']['emptyMemberList'];
        }

        $this->Template->members = $arrMembers;
	}
}
