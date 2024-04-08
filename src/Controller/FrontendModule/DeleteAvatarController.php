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
use Contao\CoreBundle\Controller\FrontendModule\AbstractFrontendModuleController;
use Contao\CoreBundle\DependencyInjection\Attribute\AsFrontendModule;
use Contao\CoreBundle\Exception\RedirectResponseException;
use Contao\FrontendUser;
use Contao\Input;
use Contao\MemberModel;
use Contao\ModuleModel;
use Contao\StringUtil;
use Contao\System;
use Contao\Template;
use Exception;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

#[AsFrontendModule(DeleteAvatarController::TYPE, category:'user', template:'memberExtension_deleteAvatar')]
class DeleteAvatarController extends AbstractFrontendModuleController
{
    const TYPE = 'deleteAvatar';

    private BackendTemplate|Template $template;
    private ModuleModel $model;
    private Request $request;
    private ?MemberModel $member;

    /**
     * @throws Exception
     */
    protected function getResponse(Template $template, ModuleModel $model, Request $request): ?Response
    {
        $container = System::getContainer();

        $this->model = $model;
        $this->request = $request;
        $this->template = $template;

        // Do not display template in backend
        if ($container->get('contao.routing.scope_matcher')->isBackendRequest($request))
        {
            $template = new BackendTemplate('be_wildcard');
        }

        // Return if there is no logged-in user
        if (
            !$container->get('contao.security.token_checker')->hasFrontendUser() ||
            null === ($this->member = MemberModel::findByPk(FrontendUser::getInstance()->id))
        ) {
            $template->getResponse();
        }


        $this->process();

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

        /*if ($request && $container->get('contao.routing.scope_matcher')->isBackendRequest($request))
        {
            $objTemplate = new BackendTemplate('be_wildcard');
            $objTemplate->wildcard = '### ' . $GLOBALS['TL_LANG']['FMD']['deleteAvatar'][0] . ' ###';
            $objTemplate->title = $this->headline;
            $objTemplate->id = $this->id;
            $objTemplate->link = $this->name;
            $objTemplate->href = StringUtil::specialcharsUrl(System::getContainer()->get('router')->generate('contao_backend', ['do'=>'themes', 'table'=>'tl_module', 'act'=>'edit', 'id'=>$this->id]));

            return $objTemplate->parse();
        }*/

        // Return if there is no logged-in user
        if (!$container->get('contao.security.token_checker')->hasFrontendUser())
        {
            return '';
        }

        $this->import(FrontendUser::class, 'User');
        $objMember = MemberModel::findByPk($this->User->id);

        if (null === $objMember)
        {
            return '';
        }

        // Confirmation message
        $session = System::getContainer()->get('session');
        $flashBag = $session->getFlashBag();

        // Return if there is no flashbag message or an avatar
        if (!($session->isStarted() && $flashBag->has('mod_avatar_deleted')) && !$objMember->avatar)
        {
            return '';
        }

        return parent::generate();
    }

    /**
     * Generate the module
     * @throws Exception
     */
    private function process(): void
    {
        $container = System::getContainer();

        // Confirmation message
        $session = $container->get('session');
        $flashBag = $session->getFlashBag();

        if (!($session->isStarted() && $flashBag->has('mod_avatar_deleted')) && !$this->member->avatar)
        {
            $this->template->getResponse();
        }

        $strFormId = 'deleteAvatar_' . $this->id;
        $session = System::getContainer()->get('session');
        $flashBag = $session->getFlashBag();

        // Get form submit
        if (Input::post('FORM_SUBMIT') == $strFormId)
        {
            // Delete avatar if it exists
            if (!!$this->member->avatar)
            {
                Member::deleteAvatar($this->member);
                // Unset avatar
                $this->member->avatar = null;
                $this->member->save();

                // Set message for deletion feedback
                $flashBag->set('mod_avatar_deleted', $GLOBALS['TL_LANG']['MSC']['avatarDeleted'] ?? '');

                throw new RedirectResponseException($this->request->getRequestUri());
            }
        }

        // Confirmation message
        if ($session->isStarted() && $flashBag->has('mod_avatar_deleted')) {
            $arrMessages = $flashBag->get('mod_avatar_deleted');
            $this->template->message = $arrMessages[0];
        }

        $this->template->formId = $strFormId;
        $this->template->slabel = StringUtil::specialchars($GLOBALS['TL_LANG']['MSC']['deleteAvatar'] ?? '');
    }
}
