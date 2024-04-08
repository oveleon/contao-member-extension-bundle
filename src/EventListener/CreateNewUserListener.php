<?php

namespace Oveleon\ContaoMemberExtensionBundle\EventListener;

use Contao\CoreBundle\DependencyInjection\Attribute\AsHook;
use Contao\MemberModel;
use Contao\Module;
use Exception;
use Oveleon\ContaoMemberExtensionBundle\Member;

#[AsHook('createNewUser')]
class CreateNewUserListener
{
    /**
     * @throws Exception
     */
    public function __invoke(int $userId, array $userData, Module $module): void
    {
        // Create avatar
        $objMember = MemberModel::findById($userId);
        Member::processAvatar($objMember, $userData);
    }
}
