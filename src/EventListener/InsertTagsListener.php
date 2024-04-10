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

use Contao\CoreBundle\DependencyInjection\Attribute\AsHook;
use Contao\CoreBundle\Security\Authentication\Token\TokenChecker;
use Contao\FrontendTemplate;
use Contao\FrontendUser;
use Contao\Image\ResizeConfiguration;
use Contao\MemberModel;
use Oveleon\ContaoMemberExtensionBundle\Member;

#[AsHook('replaceInsertTags')]
class InsertTagsListener
{
    private const SUPPORTED_TAGS = [
        'avatar',
        'avatar_url'
    ];

    public function __construct(private readonly TokenChecker $tokenChecker)
    {}

    public function __invoke(string $tag, bool $useCache, $cacheValue): string|false
    {
        $elements = explode('::', $tag);
        $key = strtolower($elements[0]);

        if (in_array($key, self::SUPPORTED_TAGS, true))
        {
            return $this->replaceMemberInsertTag($key, $elements);
        }

        return false;
    }

    private function replaceMemberInsertTag(string $insertTag, array $elements): string
    {
        $memberID = match ($elements[2]) {
            'current' => $this->tokenChecker->hasFrontendUser() ? FrontendUser::getInstance()->id : '',
            default => is_numeric($elements[2]) ? $elements[2] : '',
        };

        if (!\is_numeric($memberID))
        {
            return '';
        }

        $member = MemberModel::findByPk($memberID);

        switch ($insertTag)
        {
            case 'avatar':
            {
                if (isset($elements[3]))
                {
                    $size = $this->convertImgSize($elements[3]);
                }

                $memberTemplate = new FrontendTemplate('memberExtension_image');

                Member::parseMemberAvatar($member, $memberTemplate, $size ?? null);

                return $memberTemplate->parse();
            }

            case 'avatar_url':
            {
                return Member::getMemberAvatarURL($member);
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
            ResizeConfiguration::MODE_PROPORTIONAL, // To be removed when simultaneous C4/5 support ends
            ResizeConfiguration::MODE_CROP,
        ];

        if (!!$mode && in_array($mode, $arrValidModes, true))
        {
            $arrSizes[] = $mode;
        }

        return serialize($arrSizes);
    }
}
