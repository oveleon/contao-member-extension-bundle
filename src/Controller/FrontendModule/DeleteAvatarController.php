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
use Oveleon\ContaoMemberExtensionBundle\Member;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

#[AsFrontendModule(DeleteAvatarController::TYPE, category:'user', template:'memberExtension_deleteAvatar')]
class DeleteAvatarController extends AbstractFrontendModuleController
{
    const TYPE = 'deleteAvatar';

    /**
     * @throws Exception
     */
    protected function getResponse(Template $template, ModuleModel $model, Request $request): Response
    {
        $container = System::getContainer();

        // Return if there is no logged-in user
        if (
            !$container->get('contao.security.token_checker')->hasFrontendUser() ||
            null === ($member = MemberModel::findByPk(FrontendUser::getInstance()->id))
        ) {
            return new Response();
        }

        // Confirmation message
        $session = $container->get('request_stack')->getSession();
        $flashBag = $session->getFlashBag();

        if (!($session->isStarted() && $flashBag->has('mod_avatar_deleted')) && !$member->avatar)
        {
            return new Response();
        }

        $strFormId = 'deleteAvatar_' . $model->id;

        // Get form submit
        if (Input::post('FORM_SUBMIT') == $strFormId)
        {
            // Delete avatar if it exists
            if (!!$member->avatar)
            {
                Member::deleteAvatar($member);
                // Unset avatar
                $member->avatar = null;
                $member->save();

                // Set message for deletion feedback
                $flashBag->set('mod_avatar_deleted', $GLOBALS['TL_LANG']['MSC']['avatarDeleted'] ?? '');

                throw new RedirectResponseException($request->getRequestUri());
            }
        }

        // Confirmation message
        if ($session->isStarted() && $flashBag->has('mod_avatar_deleted')) {
            $arrMessages = $flashBag->get('mod_avatar_deleted');
            $template->message = $arrMessages[0];
        }

        $template->formId = $strFormId;
        $template->slabel = StringUtil::specialchars($GLOBALS['TL_LANG']['MSC']['deleteAvatar'] ?? '');
        $template->requestToken = System::getContainer()->get('contao.csrf.token_manager')->getDefaultTokenValue();

        return $template->getResponse();
    }
}
