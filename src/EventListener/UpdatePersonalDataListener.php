<?php

namespace Oveleon\ContaoMemberExtensionBundle\EventListener;

use Contao\CoreBundle\DependencyInjection\Attribute\AsHook;
use Contao\FrontendUser;
use Contao\MemberModel;
use Contao\Module;
use Exception;
use Oveleon\ContaoMemberExtensionBundle\Member;

#[AsHook('updatePersonalData')]
class UpdatePersonalDataListener
{
    /**
     * @throws Exception
     */
    public function __invoke(FrontendUser $member, array $data, Module $module): void
    {
        // Update avatar of a member | Login
        $objMember = MemberModel::findById($member->id);
        Member::processAvatar($objMember, $data);
    }
}
