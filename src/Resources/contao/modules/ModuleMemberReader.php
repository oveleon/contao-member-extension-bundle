<?php

/*
 * This file is part of Oveleon ContaoMemberExtension Bundle.
 *
 * (c) https://www.oveleon.de/
 */

namespace Oveleon\ContaoMemberExtensionBundle;

use Contao\BackendTemplate;
use Contao\Config;
use Contao\CoreBundle\Exception\PageNotFoundException;
use Contao\Environment;
use Contao\FrontendTemplate;
use Contao\Input;
use Contao\MemberModel;
use Contao\StringUtil;
use Patchwork\Utf8;

/**
 * Class ModuleMemberList
 *
 * @author Daniele Sciannimanica <https://github.com/doishub>
 */
class ModuleMemberReader extends ModuleMemberExtension
{

	/**
	 * Template
	 * @var string
	 */
	protected $strTemplate = 'mod_memberReader';

	/**
	 * Template
	 * @var string
	 */
	protected $strMemberTemplate = 'member_reader_full';

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

        // Set the item from the auto_item parameter
        if (!isset($_GET['items']) && isset($_GET['auto_item']) && Config::get('useAutoItem'))
        {
            Input::setGet('items', Input::get('auto_item'));
        }

		return parent::generate();
	}

	/**
	 * Generate the module
	 */
	protected function compile()
	{
        $this->Template->referer = 'javascript:history.go(-1)';
        $this->Template->back = $GLOBALS['TL_LANG']['MSC']['goBack'];

        // Get the member
        $objMember = MemberModel::findByIdOrAlias(Input::get('items'));

        // The member does not exist
        if ($objMember === null || $objMember->disable)
        {
            throw new PageNotFoundException('Page not found: ' . Environment::get('uri'));
        }

        // Check groups
        $arrGroups = StringUtil::deserialize($this->groups);
        $memberGroups = StringUtil::deserialize($objMember->groups);

        if (empty($arrGroups) || !\is_array($arrGroups) || !\count(array_intersect($arrGroups, $memberGroups)))
        {
            throw new PageNotFoundException('Page not found: ' . Environment::get('uri'));
        }

        $arrMemberFields = StringUtil::deserialize($this->memberFields, true);

        $objTemplate = new FrontendTemplate($this->memberReaderTpl ?: $this->strMemberTemplate);
        $objTemplate->setData($objMember->row());

        $this->Template->member = $this->parseMemberTemplate($objMember, $objTemplate, $arrMemberFields, $this->imgSize);
	}
}
