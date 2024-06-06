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

use Contao\CoreBundle\ContaoCoreBundle;
use Contao\CoreBundle\DependencyInjection\Attribute\AsFrontendModule;
use Contao\CoreBundle\Exception\PageNotFoundException;
use Contao\Environment;
use Contao\FrontendTemplate;
use Contao\Input;
use Contao\MemberModel;
use Contao\ModuleModel;
use Contao\PageModel;
use Contao\StringUtil;
use Contao\System;
use Contao\Template;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

#[AsFrontendModule(MemberReaderController::TYPE, category: 'user', template: 'mod_memberReader')]
class MemberReaderController extends MemberExtensionController
{
    const TYPE = 'memberReader';

    protected function getResponse(Template $template, ModuleModel $model, Request $request): Response
    {
        $auto_item = Input::get('auto_item');

        if (
            version_compare(ContaoCoreBundle::getVersion(), '5', '<') &&
            !isset($_GET['items']) &&
            isset($_GET['auto_item']) &&
            $this->useAutoItem()
        ) {
            Input::setGet('member', Input::get('auto_item'));
            $auto_item = Input::get('member');

        }

        if (null === $auto_item)
        {
            return new Response();
        }

        $member = MemberModel::findByIdOrAlias($auto_item);

        // The member does not exist and is not deactivated
        if (null === $member || $member->disable)
        {
            throw new PageNotFoundException('Page not found: ' . Environment::get('uri'));
        }

        // Check for group intersection
        $arrGroups = StringUtil::deserialize($model->ext_groups);
        $memberGroups = StringUtil::deserialize($member->groups);

        if (empty($arrGroups) || !\is_array($arrGroups) || !\count(array_intersect($arrGroups, $memberGroups)))
        {
            throw new PageNotFoundException('Page not found: ' . Environment::get('uri'));
        }

        // Hook modify the member detail page
        if (isset($GLOBALS['TL_HOOKS']['parseMemberReader']) && \is_array($GLOBALS['TL_HOOKS']['parseMemberReader']))
        {
            foreach ($GLOBALS['TL_HOOKS']['parseMemberReader'] as $callback)
            {
                System::importStatic($callback[0])->{$callback[1]}($member, $template, $model, $this);
            }
        }

        $this->memberFields = StringUtil::deserialize($model->memberFields, true);

        $memberTemplate = new FrontendTemplate($model->memberReaderTpl ?: 'memberExtension_reader_full');
        $memberTemplate->setData($member->row());

        if ($model->overviewPage)
        {
            $template->referer = PageModel::findById($model->overviewPage)->getFrontendUrl();
            $template->back = $model->customLabel ?: $GLOBALS['TL_LANG']['MSC']['goBack'];
        }
        else
        {
            $template->referer = 'javascript:history.go(-1)';
            $template->back = $GLOBALS['TL_LANG']['MSC']['goBack'];
        }

        $template->member = $this->parseMemberTemplate($member, $memberTemplate, $model);

        return $template->getResponse();
    }
}
