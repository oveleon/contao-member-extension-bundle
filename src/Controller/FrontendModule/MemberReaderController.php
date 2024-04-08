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
use Contao\Config;
use Contao\CoreBundle\DependencyInjection\Attribute\AsFrontendModule;
use Contao\CoreBundle\Exception\PageNotFoundException;
use Contao\Environment;
use Contao\FrontendTemplate;
use Contao\Input;
use Contao\MemberModel;
use Contao\ModuleModel;
use Contao\StringUtil;
use Contao\System;
use Contao\Template;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Class ModuleMemberList
 *
 * @property string $ext_groups considered member groups
 * @property string $memberFields Fields to be displayed
 * @property string $memberReaderTpl Frontend reader template
 */
#[AsFrontendModule(MemberReaderController::TYPE, category: 'user', template: 'mod_memberReader')]
class MemberReaderController extends MemberExtensionController
{
    const TYPE = 'memberReader';

    protected string $strMemberTemplate = 'memberExtension_reader_full';

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
            $objTemplate->wildcard = '### ' . $GLOBALS['TL_LANG']['FMD']['memberList'][0] . ' ###';
            $objTemplate->title = $this->headline;
            $objTemplate->id = $this->id;
            $objTemplate->link = $this->name;
            $objTemplate->href = StringUtil::specialcharsUrl(System::getContainer()->get('router')->generate('contao_backend', ['do'=>'themes', 'table'=>'tl_module', 'act'=>'edit', 'id'=>$this->id]));

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

        // The member does not exist and is not deactivated
        if ($objMember === null || $objMember->disable)
        {
            throw new PageNotFoundException('Page not found: ' . Environment::get('uri'));
        }

        // Check for group intersection
        $arrGroups = StringUtil::deserialize($this->ext_groups);
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
