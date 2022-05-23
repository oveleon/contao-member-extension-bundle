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

namespace Oveleon\ContaoMemberExtensionBundle;

use Contao\BackendTemplate;
use Contao\FrontendTemplate;
use Contao\MemberModel;
use Contao\Model\Collection;
use Contao\StringUtil;
use Contao\System;

/**
 * Class ModuleMemberList
 *
 * @property string $ext_order order of list items
 * @property string ext_orderField order field for list items
 * @property string $ext_groups considered member groups
 * @property string $memberFields Fields to be displayed
 * @property string $memberListTpl Frontend list template
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
	protected $strMemberTemplate = 'memberExtension_list_default';

	/**
	 * Return a wildcard in the back end
	 *
	 * @return string
	 */
	public function generate()
	{
        $request = System::getContainer()->get('request_stack')->getCurrentRequest();

        if ($request && System::getContainer()->get('contao.routing.scope_matcher')->isBackendRequest($request))
        {
            $objTemplate = new BackendTemplate('be_wildcard');
			$objTemplate->wildcard = '### ' . mb_strtoupper($GLOBALS['TL_LANG']['FMD']['memberList'][0], 'UTF-8') . ' ###';
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
        $arrGroups = StringUtil::deserialize($this->ext_groups);

        if(empty($arrGroups) || !\is_array($arrGroups))
        {
            $this->Template->empty = $GLOBALS['TL_LANG']['MSC']['emptyMemberList'];
            return;
        }

        $objTemplate = new FrontendTemplate($this->memberListTpl ?: $this->strMemberTemplate);

        $objMembers = $this->getMembers();
        $arrMembers = [];

        if(null !== $objMembers)
        {
            while($objMembers->next())
            {
                $objMember = $objMembers->current();

                if(!$this->checkMemberGroups($arrGroups, $objMember))
                {
                    continue;
                }

                $arrMemberFields = StringUtil::deserialize($this->memberFields, true);
                $objTemplate->setData($objMember->row());

                $arrMembers[] = $this->parseMemberTemplate($objMember, $objTemplate, $arrMemberFields, $this->imgSize);
            }
        }

        if(empty($arrMembers))
        {
            $this->Template->empty = $GLOBALS['TL_LANG']['MSC']['emptyMemberList'];
        }

        $this->Template->members = $arrMembers;
	}

    /**
     * Checks whether a member is in any given group
     *
     * @param array $arrGroups
     * @param MemberModel $objMember
     * @return bool
     */
    private function checkMemberGroups(array $arrGroups, MemberModel $objMember): bool
    {
        if(empty($arrGroups))
        {
            return false;
        }

        $arrMemberGroups = StringUtil::deserialize($objMember->groups);

        if(!\is_array($arrMemberGroups) || !\count(array_intersect($arrGroups, $arrMemberGroups)))
        {
            return false;
        }

        return true;
    }

    /**
     * Get members
     *
     * @return Collection|MemberModel|null
     */
    private function getMembers()
    {
        $arrOptions = [];
        $t = MemberModel::getTable();

        if (!!$this->ext_orderField)
        {
            $arrOptions['order'] .= "$t.$this->ext_orderField ";
        }

        switch ($this->ext_order)
        {
            case 'order_random':
                $arrOptions['order'] = "RAND()";
                break;

            case 'order_desc':
                $arrOptions['order'] .= "DESC";
                break;

            case 'order_asc':
            default:
                break;
        }

        return MemberModel::findBy(["$t.disable=''"], null, $arrOptions);
    }
}
