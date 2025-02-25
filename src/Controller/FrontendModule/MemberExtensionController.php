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

namespace Oveleon\ContaoMemberExtensionBundle\Controller\FrontendModule;

use Contao\Config;
use Contao\Controller;
use Contao\CoreBundle\ContaoCoreBundle;
use Contao\CoreBundle\Controller\FrontendModule\AbstractFrontendModuleController;
use Contao\CoreBundle\EventListener\Widget\HttpUrlListener;
use Contao\Date;
use Contao\Environment;
use Contao\FrontendTemplate;
use Contao\MemberGroupModel;
use Contao\MemberModel;
use Contao\Model;
use Contao\ModuleModel;
use Contao\PageModel;
use Contao\StringUtil;
use Contao\System;
use Oveleon\ContaoMemberExtensionBundle\Member;

abstract class MemberExtensionController extends AbstractFrontendModuleController
{
    private ModuleModel $model;

    protected bool $isTable = false;

    protected array $memberFields = [];

    protected array $labels = [];

    protected function parseMemberTemplate(MemberModel|Model $objMember, FrontendTemplate $objTemplate, ModuleModel $model): string
    {
        $this->model = $model;

        $arrFields = [];

        // HOOK: modify the member details
        if (isset($GLOBALS['TL_HOOKS']['parseMemberTemplate']) && \is_array($GLOBALS['TL_HOOKS']['parseMemberTemplate']))
        {
            foreach ($GLOBALS['TL_HOOKS']['parseMemberTemplate'] as $callback)
            {
                System::importStatic($callback[0])->{$callback[1]}($objMember, $this->memberFields, $objTemplate, $model, $this);
            }
        }

        foreach ($this->memberFields as $field)
        {
            switch ($field)
            {
                /*case 'homeDir':
                case 'assignDir':
                    break;*/

                case 'avatar':
                    Member::parseMemberAvatar($objMember, $objTemplate, $model->imgSize);
                    break;

                default:
                    if ($varValue = $objMember->{$field})
                    {
                        if (\is_array(($arrValue = StringUtil::deserialize($varValue))))
                        {
                            $arrFields[$field] = implode(", ", $arrValue);
                        }
                        else
                        {
                            $arrFields[$field] = $varValue;
                        }

                        if ($model->ext_parseDetails)
                        {
                            self::parseMemberDetails($arrFields, $field, $varValue);
                        }
                    }
            }
        }

        $returnFields = [];

        $skipEmptyValues = System::getContainer()->getParameter('contao_member_extension.skip_empty_values');

        foreach ($this->memberFields as $value)
        {
            $val = $arrFields[$value] ?? '';

            if ($skipEmptyValues && !$val)
            {
                continue;
            }

            $returnFields[$value] = $val;
        }

        $labels = array_keys($returnFields);

        $this->parsedLabels = true;
        $this->labels = array_map(fn($field) => $GLOBALS['TL_LANG']['tl_member'][$field][0] ?? $field, $labels);;

        $objTemplate->fields = $returnFields;

        if ($model->jumpTo)
        {
            $objTemplate->link = $this->generateMemberUrl($objMember);
        }

        return $objTemplate->parse();
    }

    protected function generateMemberUrl(MemberModel $objMember): string
    {
        $objPage = PageModel::findPublishedById($this->model->jumpTo);

        if (!$objPage instanceof PageModel)
        {
            $strLink = StringUtil::ampersand(Environment::get('request'));
        }
        else
        {
            $params = ($this->useAutoItem() ? '/' : '/items/') . ($this->model->ext_memberAlias ? ($objMember->alias ?: $objMember->id) : $objMember->id);
            $strLink = StringUtil::ampersand($objPage->getFrontendUrl($params));
        }

        return $strLink;
    }

    protected function parseMemberDetails(&$arrFields, $field, $value): void
    {
        $strReturn = !$this->isTable ? sprintf('<span class="label">%s: </span>',$GLOBALS['TL_LANG']['tl_member'][$field][0] ?? null) : '';

        if (!\is_array(($arrValue = StringUtil::deserialize($value))))
        {
            Controller::loadDataContainer('tl_member');

            if (!empty($rgxp = $GLOBALS['TL_DCA']['tl_member']['fields'][$field]['eval']['rgxp'] ?? []))
            {
                switch ($rgxp) {
                    case HttpUrlListener::RGXP_NAME:
                        $strReturn .= '<a href="' . $value . '" title="' . $value . '" target="blank noopener" rel="noreferer">' . preg_replace('/https?:\/\/|www.|\/$/', '', $value) . '</a>';
                        break;

                    case 'phone':
                        $strTel = preg_replace('/[^a-z\d+]/i', '', (string)$value);
                        $strReturn .= '<a href="tel:' . $strTel . '" title="' . $value . '">' . $value . '</a>';
                        break;

                    case 'email':
                        $strEmail = StringUtil::encodeEmail($value);
                        $strReturn .= '<a href="&#109;&#97;&#105;&#108;&#116;&#111;&#58;' . $strEmail . '" title="' . $strEmail . '">' . preg_replace('/\?.*$/', '', $strEmail) . '</a>';
                        break;

                    case 'date':
                        $strReturn .= Date::parse(Config::get('dateFormat'), $value) ?? $value;
                        break;

                    default:
                        $strReturn .= $value;
                }
            }
            else {
                $strReturn .= match ($field) {
                    'gender' => $GLOBALS['TL_LANG']['MSC'][$value] ?? $value,
                    'country' => $GLOBALS['TL_LANG']['CNT'][$value] ?? $value,
                    'language' => $GLOBALS['TL_LANG']['LNG'][$value] ?? $value,
                    default => $value
                };
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

    /**
     * Checks weather auto_item should be used to provide BC
     *
     * @deprecated - To be removed when contao 4.13 support ends
     * @internal
     */
    protected function useAutoItem(): bool
    {
        return version_compare(ContaoCoreBundle::getVersion(), '5', '<') ? Config::get('useAutoItem') : true;
    }
}
