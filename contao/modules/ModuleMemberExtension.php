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

namespace Oveleon\ContaoMemberExtensionBundle;

use Contao\Config;
use Contao\Date;
use Contao\Environment;
use Contao\MemberGroupModel;
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
    protected function parseMemberTemplate($objMember, $objTemplate, $arrMemberFields, $strImgSize): string
    {
        System::loadLanguageFile('default');
        System::loadLanguageFile('tl_member');
        System::loadLanguageFile('countries');
        System::loadLanguageFile('languages');

        $arrFields = [];

        foreach ($arrMemberFields as $field)
        {
            switch ($field)
            {
                /*case 'homeDir':
                case 'assignDir':
                    break;*/

                case 'avatar':
                    Member::parseMemberAvatar($objMember, $objTemplate, $strImgSize);
                    break;

                default:
                    if ($varValue = $objMember->{$field})
                    {
                        if (\is_array(($arrValue = StringUtil::deserialize($varValue))))
                        {
                            $arrFields[$field] = implode(",", $arrValue);
                        }
                        else
                        {
                            $arrFields[$field] = $varValue;
                        }
                        //self::parseMemberDetails($arrFields, $field, $varValue);
                    }
            }
        }

        $objTemplate->fields = $arrFields;

        if ($this->jumpTo)
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
    protected function generateMemberUrl(MemberModel $objMember): string
    {
        $objPage = PageModel::findPublishedById($this->jumpTo);

        if (!$objPage instanceof PageModel)
        {
            $strLink = StringUtil::ampersand(Environment::get('request'));
        }
        else
        {
            $params = (Config::get('useAutoItem') ? '/' : '/items/') . ($objMember->alias ?: $objMember->id);
            $strLink = StringUtil::ampersand($objPage->getFrontendUrl($params));
        }

        return $strLink;
    }

    protected function parseMemberDetails(&$arrFields, $field, $value)
    {
        $strReturn = sprintf('<span class="label">%s: </span>',$GLOBALS['TL_LANG']['tl_member'][$field][0] ?? null);

        if (!\is_array(($arrValue = StringUtil::deserialize($value))))
        {
            switch ($field) {
                case 'gender':
                    $strReturn .= $GLOBALS['TL_LANG']['MSC'][$value] ?? $value;
                    break;

                case 'email':
                    $strEmail = StringUtil::encodeEmail($value);
                    $strReturn .= '<a href="&#109;&#97;&#105;&#108;&#116;&#111;&#58;' . $strEmail . '" title="' . $strEmail . '">' . preg_replace('/\?.*$/', '', $strEmail) . '</a>';
                    break;

                case 'phone':
                case 'mobile':
                case 'fax':
                    $strTel = preg_replace('/[^a-z\d+]/i', '', (string)$value);
                    $strReturn .= '<a href="tel:' . $strTel . '" title="' . $value . '">' . $value . '</a>';
                    break;

                case 'website':
                    $strUrl = $value;

                    if (strncmp($value, 'http://', 7) !== 0 || strncmp($value, 'https://', 8) !== 0) {
                        $strUrl = 'https://' . $value;
                    }

                    $strReturn .= '<a href="' . $strUrl . '" title="' . $value . '" target="blank noopener" rel="noreferer">' . $value . '</a>';
                    break;

                case 'dateOfBirth':
                    $strReturn .= Date::parse(Config::get('dateFormat'), $value) ?? $value;
                    break;

                case 'country':
                    $strReturn .= $GLOBALS['TL_LANG']['CNT'][$value] ?? $value;
                    break;

                case 'language':
                    $strReturn .= $GLOBALS['TL_LANG']['LNG'][$value] ?? $value;
                    break;

                default:
                    $strReturn .= $value;
            }
        }
        else if ('groups' === $field)
        {
            $arrReturn = [];

            foreach ($arrValue as $value)
            {
                $arrReturn[] = MemberGroupModel::findById($value)->name;
            }

            $strReturn .= implode(", ", $arrReturn);
        }

        $arrFields[$field] = $strReturn;
    }
}
