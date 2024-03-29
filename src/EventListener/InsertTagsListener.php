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

namespace Oveleon\ContaoMemberExtensionBundle\EventListener;

use Contao\FrontendTemplate;
use Contao\FrontendUser;
use Contao\Image\ResizeConfiguration;
use Contao\MemberModel;
use Contao\System;
use Contao\CoreBundle\Framework\ContaoFramework;
use Oveleon\ContaoMemberExtensionBundle\Member;

class InsertTagsListener
{
    private const SUPPORTED_TAGS = [
        'avatar',
        'avatar_url'
    ];

    /**
     * @var ContaoFramework
     */
    private $framework;

    public function __construct(ContaoFramework $framework)
    {
        $this->framework = $framework;
    }

    /**
     * @return string|false
     */
    public function __invoke(string $tag, bool $useCache, $cacheValue, array $flags)
    {
        $elements = explode('::', $tag);
        $key = strtolower($elements[0]);

        if (\in_array($key, self::SUPPORTED_TAGS, true)) {
            return $this->replaceMemberInsertTag($key, $elements, $flags);
        }

        return false;
    }

    private function replaceMemberInsertTag(string $insertTag, array $elements, array $flags): string
    {
        $this->framework->initialize();
        $tokenChecker = System::getContainer()->get('contao.security.token_checker');

        if ($elements[1] !== 'member')
        {
            return '';
        }

        switch ($elements[2])
        {

            case 'current':
                if (!$tokenChecker->hasFrontendUser())
                {
                    return '';
                }
                $memberID = FrontendUser::getInstance()->id;
                break;

            default:
                if (!\is_numeric($elements[2]))
                {
                    return '';
                }
                $memberID = $elements[2];
                break;
        }

        $objMember = MemberModel::findByPk($memberID);

        switch ($insertTag)
        {
            case 'avatar':
            {
                if (isset($elements[3]))
                {
                    $strImgSize = $this->convertImgSize($elements[3]);
                }

                $objTemplate = new FrontendTemplate('memberExtension_image');

                Member::parseMemberAvatar($objMember, $objTemplate, $strImgSize ?? null);

                return $objTemplate->parse();
            }

            case 'avatar_url':
            {
                return Member::getMemberAvatarURL($objMember);
            }
        }

        return '';
    }

    private function convertImgSize($strSize): ?string
    {
        if (!$strSize)
        {
            return null;
        }

        list($intWidth, $intHeight, $mode) = explode('x', $strSize);

        $arrSizes = [$intWidth, $intHeight];

        $arrValidModes = [
            ResizeConfiguration::MODE_BOX,
            ResizeConfiguration::MODE_PROPORTIONAL,
            ResizeConfiguration::MODE_CROP,
        ];

        if (!!$mode && in_array($mode, $arrValidModes, true))
        {
            $arrSizes[] = $mode;
        }

        return serialize($arrSizes);
    }
}
