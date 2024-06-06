<?php

namespace Oveleon\ContaoMemberExtensionBundle\EventListener\DataContainer;

use Contao\CoreBundle\DependencyInjection\Attribute\AsCallback;
use Contao\CoreBundle\Security\ContaoCorePermissions;
use Contao\DataContainer;
use Contao\Input;
use Contao\Message;
use Contao\ModuleModel;
use Contao\System;

class ModuleFieldsListener
{
    #[AsCallback(table: 'tl_module', target: 'config.onload')]
    public function showJsLibraryHint(DataContainer $dc): void
    {
        if ($_POST || Input::get('act') != 'edit')
        {
            return;
        }

        $security = System::getContainer()->get('security.helper');

        if (
            !$security->isGranted(ContaoCorePermissions::USER_CAN_ACCESS_MODULE, 'themes') ||
            !$security->isGranted(ContaoCorePermissions::USER_CAN_ACCESS_LAYOUTS)
        ) {
            return;
        }

        $objModule = ModuleModel::findByPk($dc->id);

        if (null !== $objModule && 'memberList' === $objModule->type && str_starts_with($objModule->customTpl, 'mod_memberList_table'))
        {
            Message::addInfo(sprintf(($GLOBALS['TL_LANG']['tl_module']['includeMemberListTable'] ?? null), 'memberExtension_list_row', 'j_datatables'));
        }
    }
}
