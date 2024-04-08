<?php

namespace Oveleon\ContaoMemberExtensionBundle\EventListener\DataContainer;

use Contao\Controller;
use Contao\CoreBundle\DependencyInjection\Attribute\AsCallback;
use Contao\System;

class MemberFieldsOptionsListener
{
    public function __construct() {
        Controller::loadDataContainer('tl_member');
        System::loadLanguageFile('tl_member');
    }

    #[AsCallback(table: 'tl_module', target: 'fields.ext_orderField.options')]
    public function getViewableMemberFields(): array
    {
        $fields = [];

        foreach ($GLOBALS['TL_DCA']['tl_member']['fields'] as $k => $v)
        {
            if (
                !empty($v['inputType']) &&
                $k !== 'avatar' &&
                isset($v['eval']['feViewable']) &&
                $v['eval']['feViewable'] === true
            ) {
                $fields[$k] = $GLOBALS['TL_DCA']['tl_member']['fields'][$k]['label'][0] ?? '' . ' ['.$k.']';
            }
        }

        return $fields;
    }

    #[AsCallback(table: 'tl_module', target: 'fields.memberFields.options')]
    public function getMemberProperties(): array
    {
        $properties = [];

        foreach ($GLOBALS['TL_DCA']['tl_member']['fields'] as $k => $v)
        {
            if (!empty($v['inputType']) && $v['inputType'] !== 'password')
            {
                $properties[$k] = $GLOBALS['TL_DCA']['tl_member']['fields'][$k]['label'][0] ?? '';
            }
        }

        return $properties;
    }
}
