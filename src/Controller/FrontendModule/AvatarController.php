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

use Contao\CoreBundle\DependencyInjection\Attribute\AsFrontendModule;
use Contao\FrontendUser;
use Contao\MemberModel;
use Contao\ModuleModel;
use Contao\System;
use Contao\Template;
use Oveleon\ContaoMemberExtensionBundle\Member;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

#[AsFrontendModule(category: 'user', template: 'memberExtension_avatar')]
class AvatarController extends MemberExtensionController
{
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

        Member::parseMemberAvatar($member, $template, $model->imgSize);

        return $template->getResponse();
    }
}
