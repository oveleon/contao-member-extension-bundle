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
use Contao\CoreBundle\DependencyInjection\Attribute\AsFrontendModule;
use Contao\CoreBundle\Exception\PageNotFoundException;
use Contao\Date;
use Contao\Environment;
use Contao\FrontendTemplate;
use Contao\Input;
use Contao\MemberModel;
use Contao\Model\Collection;
use Contao\ModuleModel;
use Contao\Pagination;
use Contao\StringUtil;
use Contao\System;
use Contao\Template;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

#[AsFrontendModule(MemberListController::TYPE, category: 'user', template: 'mod_memberList')]
class MemberListController extends MemberExtensionController
{
    const TYPE = 'memberList';
    private ModuleModel $model;

    protected function getResponse(Template $template, ModuleModel $model, Request $request): Response
    {
        $this->model = $model;

        $limit = null;
        $offset = 0;

        $arrGroups = StringUtil::deserialize($model->ext_groups);

        if (empty($arrGroups) || !\is_array($arrGroups))
        {
            $template->empty = $GLOBALS['TL_LANG']['MSC']['emptyMemberList'];
            $template->getResponse();
        }

        // ToDo: Add filter for fields with feFilterable

        $memberTemplate = new FrontendTemplate($model->memberListTpl ?: 'memberExtension_list_default');

        $intTotal = 0;
        $arrMembers = [];

        if (null !== ($objMembers = $this->getMembers()))
        {
            foreach ($objMembers as $objMember)
            {
                // ToDo: Add filter
                // continue;

                if (!$this->checkMemberGroups($arrGroups, $objMember))
                {
                    continue;
                }

                $intTotal += 1;

                $arrMemberFields = StringUtil::deserialize($model->memberFields, true);
                $memberTemplate->setData($objMember->row());

                $arrMembers[] = $this->parseMemberTemplate($objMember, $memberTemplate, $arrMemberFields, $model);
            }
        }

        $total = $intTotal - $offset;

        if ($model->numberOfItems > 0)
        {
            $limit = $model->numberOfItems;
        }

        if ($model->perPage > 0 && (!isset($limit) || $model->numberOfItems > $model->perPage))
        {
            if (isset($limit))
            {
                $total = min($limit, $total);
            }

            $id = 'page_n' . $model->id;
            $page = Input::get($id) ?? 1;

            if ($page < 1 || $page > max(ceil($total/$model->perPage), 1))
            {
                throw new PageNotFoundException('Page not found: ' . Environment::get('uri'));
            }

            $limit = $model->perPage;
            $offset += (max($page, 1) - 1) * $model->perPage;
            $skip = 0;

            if ($offset + $limit > $total + $skip)
            {
                $limit = $total + $skip - $offset;
            }

            $arrMembers = \array_slice($arrMembers, $offset, ((int) $limit ?: $intTotal), true);

            $objPagination = new Pagination($total, $model->perPage, Config::get('maxPaginationLinks'), $id);
            $template->pagination = $objPagination->generate("\n  ");
        }

        if (empty($arrMembers))
        {
            $template->empty = $GLOBALS['TL_LANG']['MSC']['emptyMemberList'];
        }

        $template->members = $arrMembers;

        return $template->getResponse();
    }

    private function checkMemberGroups(array $arrGroups, MemberModel $objMember): bool
    {
        if (empty($arrGroups))
        {
            return false;
        }

        $arrMemberGroups = StringUtil::deserialize($objMember->groups);

        if (!\is_array($arrMemberGroups) || !\count(array_intersect($arrGroups, $arrMemberGroups)))
        {
            return false;
        }

        return true;
    }

    private function getMembers(): Collection|MemberModel|null
    {
        $t = MemberModel::getTable();
        $time = Date::floorToMinute();

        $arrColumns = ["$t.disable='' AND ($t.start='' OR $t.start<='$time') AND ($t.stop='' OR $t.stop>'$time') "];
        $arrOptions = ['order' => ''];

        if (!!$orderField = $this->model->ext_orderField)
        {
            $arrOptions['order'] .= "$t.$orderField ";
        }

        switch ($this->model->ext_order)
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

        // Hook modify the member results
        if (isset($GLOBALS['TL_HOOKS']['getMembers']) && \is_array($GLOBALS['TL_HOOKS']['getMembers']))
        {
            foreach ($GLOBALS['TL_HOOKS']['getMembers'] as $callback)
            {
                System::importStatic($callback[0])->{$callback[1]}($arrColumns, $arrOptions, $this);
            }
        }

        return MemberModel::findBy($arrColumns, null, $arrOptions);
    }
}