<?php
/*
 * This file is part of Oveleon ContaoMemberExtension Bundle.
 *
 * (c) https://www.oveleon.de/
 */

namespace Oveleon\ContaoMemberExtensionBundle;

use Contao\Config;
use Contao\Environment;
use Contao\FilesModel;
use Contao\MemberModel;
use Contao\Module;
use Contao\PageModel;
use Contao\StringUtil;

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
     * @param $varImgSize
     *
     * @return string
     */
    protected function parseMemberTemplate($objMember, $objTemplate, $arrMemberFields, $varImgSize)
    {
        $arrFields = [];

        foreach ($arrMemberFields as $field)
        {
            switch($field)
            {
                case 'avatar':
                    $this->addAvatarToTemplate($objMember, $objTemplate, $varImgSize);
                    break;

                default:
                    if($varValue = $objMember->{$field})
                    {
                        if (\is_array(($arrValue = StringUtil::deserialize($varValue))))
                        {
                            $arrFields[] = implode(",", $arrValue);
                        }
                        else
                        {
                            $arrFields[] = $varValue;
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
     * Add avatar to template
     *
     * @param $objMember
     * @param $objTemplate
     * @param $varImageSize
     */
    protected function addAvatarToTemplate($objMember, $objTemplate, $varImgSize)
    {
        $objTemplate->addImage = false;

        $arrData = array(
            'size' => $varImgSize
        );

        if ($objMember->avatar == '' && Config::get('defaultAvatar') == '')
        {
            return;
        }

        if ($objMember->avatar == '')
        {
            $objFile = FilesModel::findByUuid( Config::get('defaultAvatar') );

            if ($objFile === null || !is_file(TL_ROOT . '/' . $objFile->path))
            {
                return;
            }

            $arrData['singleSRC'] = $objFile->path;
            $objTemplate->addImage = true;
            $this->addImageToTemplate($objTemplate, $arrData);
        }

        $objFile = FilesModel::findByUuid($objMember->avatar);

        if ($objFile === null || !is_file(TL_ROOT . '/' . $objFile->path))
        {
            $arrData['singleSRC'] = FilesModel::findByUuid(Config::get('defaultAvatar'))->path;
            $objTemplate->addImage = true;
            $this->addImageToTemplate($objTemplate, $arrData);
        }

        $arrData['singleSRC'] = $objFile->path;
        $objTemplate->addImage = true;
        $this->addImageToTemplate($objTemplate, $arrData, null, null, $objFile);
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
