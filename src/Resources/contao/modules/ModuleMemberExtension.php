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

use Contao\Config;
use Contao\Environment;
use Contao\FilesModel;
use Contao\MemberModel;
use Contao\Module;
use Contao\PageModel;
use Contao\StringUtil;
use Contao\System;

/**
 * Parent class for member modules.
 *
 * @author Daniele Sciannimanica <https://github.com/doishub>
 */
abstract class ModuleMemberExtension extends Module
{
    /**
     * Parse member template
     *
     * @param $objMember
     * @param $objTemplate
     * @param $arrMemberFields
     * @param $strImgSize
     * @return string
     */
    protected function parseMemberTemplate($objMember, $objTemplate, $arrMemberFields, $strImgSize)
    {
        $arrFields = [];

        foreach ($arrMemberFields as $field)
        {
            switch($field)
            {
                case 'avatar':
                    Member::parseMemberAvatar($objMember, $objTemplate, $strImgSize);
                    break;

                default:
                    if($varValue = $objMember->{$field})
                    {
                        if (\is_array(($arrValue = StringUtil::deserialize($varValue))))
                        {
                            $arrFields[$field] = implode(",", $arrValue);
                        }
                        else
                        {
                            $arrFields[$field] = $varValue;
                        }
                    }
            }
        }

        $objTemplate->fields = $arrFields;

        if($this->jumpTo)
        {
            $objTemplate->link = $this->generateMemberUrl($objMember);
        }

        return $objTemplate->parse();
    }

    /**
     * Generate a URL and return it as string
     *
     * @param MemberModel $objMember
     *
     * @return string
     */
    protected function generateMemberUrl($objMember)
    {
        $objPage = PageModel::findPublishedById($this->jumpTo);

        if (!$objPage instanceof PageModel)
        {
            $strLink = ampersand(Environment::get('request'));
        }
        else
        {
            $params = (Config::get('useAutoItem') ? '/' : '/items/') . ($objMember->alias ?: $objMember->id);
            $strLink = ampersand($objPage->getFrontendUrl($params));
        }

        return $strLink;
    }
}
