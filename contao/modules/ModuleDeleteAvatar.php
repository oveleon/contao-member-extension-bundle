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

namespace Oveleon\ContaoMemberExtensionBundle;

use Contao\BackendTemplate;
use Contao\Config;
use Contao\FrontendUser;
use Contao\Input;
use Contao\MemberModel;
use Contao\Module;
use Contao\StringUtil;
use Contao\System;

/**
 * Class ModuleDeleteAvatar
 *
 * @author Sebastian Zoglowek <https://github.com/zoglo>
 */
class ModuleDeleteAvatar extends Module
{
    /**
     * Template.
     *
     * @var string
     */
    protected $strTemplate = 'memberExtension_deleteAvatar';

    /**
     * Return a wildcard in the back end
     *
     * @return string
     */
    public function generate()
    {
        $container = System::getContainer();

        $request = System::getContainer()->get('request_stack')->getCurrentRequest();

        if ($request && System::getContainer()->get('contao.routing.scope_matcher')->isBackendRequest($request))
        {
            $objTemplate = new BackendTemplate('be_wildcard');
            $objTemplate->wildcard = '### ' . mb_strtoupper($GLOBALS['TL_LANG']['FMD']['deleteAvatar'][0] ?? '', 'UTF-8') . ' ###';
            $objTemplate->title = $this->headline;
            $objTemplate->id = $this->id;
            $objTemplate->link = $this->name;
            $objTemplate->href = 'contao/main.php?do=themes&amp;table=tl_module&amp;act=edit&amp;id=' . $this->id;

            return $objTemplate->parse();
        }

        // Set the item from the auto_item parameter
        if (!isset($_GET['items']) && isset($_GET['auto_item']) && Config::get('useAutoItem'))
        {
            Input::setGet('items', Input::get('auto_item'));
        }

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
     */
    protected function compile()
    {
        $strFormId = 'deleteAvatar_' . $this->id;
        $session = System::getContainer()->get('session');
        $flashBag = $session->getFlashBag();

        // Get form submit
        if (Input::post('FORM_SUBMIT') == $strFormId)
        {
            $this->import(FrontendUser::class, 'User');
            $objMember = MemberModel::findByPk($this->User->id);

            // Delete avatar if it exists
            if (!!$objMember->avatar)
            {
                Member::deleteAvatar($objMember);
                // Unset avatar
                $objMember->avatar = null;
                $objMember->save();

                // Set message for deletion feedback
                $flashBag->set('mod_avatar_deleted', $GLOBALS['TL_LANG']['MSC']['avatarDeleted']);
                $this->reload();
            }
        }

        // Confirmation message
        if ($session->isStarted() && $flashBag->has('mod_avatar_deleted')) {
            $arrMessages = $flashBag->get('mod_avatar_deleted');
            $this->Template->message = $arrMessages[0];
        }

        $this->Template->formId = $strFormId;
        $this->Template->slabel = StringUtil::specialchars($GLOBALS['TL_LANG']['MSC']['deleteAvatar']);
    }
}
