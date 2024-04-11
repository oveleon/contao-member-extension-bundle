<?php

namespace Oveleon\ContaoMemberExtensionBundle\EventListener\DataContainer;

use Contao\CoreBundle\DependencyInjection\Attribute\AsCallback;
use Contao\Database;
use Contao\DataContainer;
use Contao\MemberModel;
use Contao\System;
use Exception;

class MemberFieldsListener
{
    /**
     * @throws Exception
     */
    #[AsCallback(table: 'tl_member', target: 'fields.alias.save')]
    public function generateAlias($varValue, DataContainer $dc): string
    {
        $aliasExists = static function (string $alias) use ($dc): bool {
            $result = Database::getInstance()
                ->prepare("SELECT id FROM tl_member WHERE alias=? AND id!=?")
                ->execute($alias, $dc->id);

            return $result->numRows > 0;
        };

        if (!$varValue)
        {
            $varValue = $dc->activeRecord->firstname . '_' . $dc->activeRecord->lastname . ($aliasExists ? '_' . $dc->activeRecord->id : '');
        }
        if (preg_match('/^[1-9]\d*$/', $varValue))
        {
            throw new Exception(sprintf($GLOBALS['TL_LANG']['ERR']['aliasNumeric'], $varValue));
        }
        elseif ($aliasExists($varValue))
        {
            throw new Exception(sprintf($GLOBALS['TL_LANG']['ERR']['aliasExists'], $varValue));
        }

        return $varValue;
    }
}
