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

use Contao\BackendTemplate;
use Contao\CoreBundle\DependencyInjection\Attribute\AsFrontendModule;
use Contao\FrontendUser;
use Contao\MemberModel;
use Contao\ModuleModel;
use Contao\StringUtil;
use Contao\System;
use Contao\Template;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

#[AsFrontendModule(category: 'user', template: 'memberExtension_avatar')]
class AvatarController extends MemberExtensionController
{
    protected function getResponse(Template $template, ModuleModel $model, Request $request): ?Response
    {
        $container = System::getContainer();

        // Do not display template in backend
        /*if ($container->get('contao.routing.scope_matcher')->isBackendRequest($request))
        {
            $template = new BackendTemplate('be_wildcard');
        }*/

        return $template->getResponse();
    }

    /**
     * Display a wildcard in the back end
     *
     * @return string
     */
    public function generate()
    {
        $container = System::getContainer();
        $request = System::getContainer()->get('request_stack')->getCurrentRequest();

        if ($request && $container->get('contao.routing.scope_matcher')->isBackendRequest($request))
        {
            $objTemplate = new BackendTemplate('be_wildcard');
            $objTemplate->wildcard = '### ' . $GLOBALS['TL_LANG']['FMD']['avatar'][0] . ' ###';
            $objTemplate->title = $this->headline;
            $objTemplate->id = $this->id;
            $objTemplate->link = $this->name;
            $objTemplate->href = StringUtil::specialcharsUrl(System::getContainer()->get('router')->generate('contao_backend', ['do'=>'themes', 'table'=>'tl_module', 'act'=>'edit', 'id'=>$this->id]));

            return $objTemplate->parse();
        }

        // Return if user is not logged in
        $tokenChecker = System::getContainer()->get('contao.security.token_checker');
        $blnFeUserLoggedIn = $tokenChecker->hasFrontendUser();

        if (!$blnFeUserLoggedIn)
        {
            return '';
        }

        $this->strTemplate = $this->customTpl ?: 'memberExtension_avatar';

        return parent::generate();
    }

    /**
     * Generate the module
     */
    protected function compile()
    {
        $objTemplate = $this->Template;

        $this->import(FrontendUser::class, 'User');
        $objMember = MemberModel::findByPk($this->User->id);

        Member::parseMemberAvatar($objMember, $objTemplate, $this->imgSize);
    }
}
