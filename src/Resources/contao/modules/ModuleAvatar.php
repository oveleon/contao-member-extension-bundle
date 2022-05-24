<?php

declare(strict_types=1);

/*
 * This file is part of Oveleon ContaoMemberExtension Bundle.
 *
 * @package     contao-member-extension-bundle
 * @license     MIT
 * @author      Daniele Sciannimanica   <https://github.com/doishub>
 * @author      Fabian Ekert            <https://github.com/eki89>
 * @author      Sebastian Zoglowek      <https://github.com/zoglo>
 * @copyright   Oveleon                 <https://www.oveleon.de/>
 */

namespace Oveleon\ContaoMemberExtensionBundle;

use Contao\BackendTemplate;
use Contao\Config;
use Contao\FilesModel;
use Contao\FrontendUser;
use Contao\MemberModel;
use Contao\Module;
use Contao\System;

/**
 * Class ModuleAvatar
 *
 * @author Fabian Ekert <fabian@oveleon.de>
 * @author Sebastian Zoglowek <https://github.com/zoglo>
 */
class ModuleAvatar extends ModuleMemberExtension
{
    /**
     * Template.
     *
     * @var string
     */
    protected $strTemplate = 'memberExtension_avatar';

    /**
     * Return a wildcard in the back end
     *
     * @return string
     */
    public function generate()
    {
        $request = System::getContainer()->get('request_stack')->getCurrentRequest();

        if ($request && System::getContainer()->get('contao.routing.scope_matcher')->isBackendRequest($request))
        {
            $objTemplate = new BackendTemplate('be_wildcard');
            $objTemplate->wildcard = '### ' . mb_strtoupper($GLOBALS['TL_LANG']['FMD']['avatar'][0], 'UTF-8') . ' ###';
            $objTemplate->title = $this->headline;
            $objTemplate->id = $this->id;
            $objTemplate->link = $this->name;
            $objTemplate->href = 'contao/main.php?do=themes&amp;table=tl_module&amp;act=edit&amp;id=' . $this->id;

            return $objTemplate->parse();
        }

        // Return if user is not logged in
        $tokenChecker = System::getContainer()->get('contao.security.token_checker');
        $blnFeUserLoggedIn = $tokenChecker->hasFrontendUser();

        if (!$blnFeUserLoggedIn)
        {
            return '';
        }

        $this->strTemplate = $this->memberTpl ?: 'memberExtension_avatar';

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
