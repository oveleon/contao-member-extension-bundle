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

use Contao\Config;
use Contao\Dbafs;
use Contao\File;
use Contao\Files;
use Contao\FilesModel;
use Contao\FileUpload;
use Contao\MemberModel;
use Contao\StringUtil;
use Contao\System;
use Contao\Validator;
use Exception;

/**
 * Class Member
 *
 * @property int $avatar UUID of the avatar
 */
class Member
{
    const DEFAULT_PICTURE = 'bundles/contaomemberextension/avatar.png';
    const AVATAR_NAME = 'memberAvatar';

    /**
     * Process avatar upload for a member
     * @throws Exception
     */
    public static function processAvatar(MemberModel $objMember, ?array $arrData): void
    {
        $objMember = MemberModel::findByPk($objMember->id);

        if (
            $objMember === null ||
            !array_key_exists('FILES', $_SESSION) ||
            !isset($_SESSION['FILES']['avatar'])
        ) {
            return;
        }

        // ToDo: remove $_SESSION when contao 4.13 support ends (Contao ^5.* is not possible with Contao 4.* support)
        $file = $_SESSION['FILES']['avatar'];
        $maxlength_kb = FileUpload::getMaxUploadSize();
        //$maxlength_kb_readable = System::getReadableSize($maxlength_kb);

        // Sanitize the filename
        try
        {
            $file['name'] = StringUtil::sanitizeFileName($file['name']);
        }
        catch (\InvalidArgumentException $e)
        {
            // ToDo: add error message for invalid characters
            return;
        }

        // Invalid file name
        if (!Validator::isValidFileName($file['name']))
        {
            // ToDo: add error message for invalid characters
            return;
        }

        // File was not uploaded
        if (!is_uploaded_file($file['tmp_name']))
        {
            // ToDo: Add error messages
            /*if ($file['error'] == 1 || $file['error'] == 2) { // Add error message for maximum file size }
            elseif ($file['error'] == 3) { // Add error message for partial upload }
            elseif ($file['error'] > 0) { // Add error message for failed upload }*/

            unset($_SESSION['FILES']['avatar']);

            return;
        }

        // File is too big
        if ($file['size'] > $maxlength_kb)
        {
            // ToDo: add error message for maximum file size
            unset($_SESSION['FILES']['avatar']);

            return;
        }

        $container = System::getContainer();

        $objFile = new File($file['name']);
        $uploadTypes = StringUtil::trimsplit(',', $container->getParameter('contao.image.valid_extensions'));

        // File type is not allowed
        if (!\in_array($objFile->extension, $uploadTypes))
        {
            // ToDo: add error message for not allowed file type
            unset($_SESSION['FILES']['avatar']);

            return;
        }

        if (
            ($arrImageSize = getimagesize($file['tmp_name'])) &&
            ($arrImageSize[0] > Config::get('imageWidth') || $arrImageSize[1] > Config::get('imageHeight'))
        ) {
            unset($_SESSION['FILES']['avatar']);
            return;
        }

        // Upload valid file type with no width and height -> svg

        // Don't upload if no homedir is assigned
        // ToDo: Create homedir?
        if (!$objMember->assignDir || !$objMember->homeDir)
        {
            // ToDo: add error message for no homedir
            return;
        }

        $intUploadFolder = $objMember->homeDir;

        $objUploadFolder = FilesModel::findByUuid($intUploadFolder);

        // The upload folder could not be found
        if ($objUploadFolder === null)
        {
            throw new Exception("Invalid upload folder ID $intUploadFolder");
        }

        $strUploadFolder = $objUploadFolder->path;

        // Store the file if the upload folder exists
        $projectDir = $container->getParameter('kernel.project_dir');

        if (!!$strUploadFolder & is_dir($projectDir . '/' . $strUploadFolder))
        {
            // Delete existing avatar if it exists
            static::deleteAvatar($objMember);

            // Rename file
            $file['name'] =  self::AVATAR_NAME . '.' . $objFile->extension;

            // Move the file to its destination
            $filesObj = Files::getInstance();
            $filesObj->move_uploaded_file($file['tmp_name'], $strUploadFolder . '/' . $file['name']);
            $filesObj->chmod($strUploadFolder . '/' . $file['name'], 0666 & ~umask());

            $strUuid = null;
            $strFile = $strUploadFolder . '/' . $file['name'];


            // Generate the DB entries
            if (Dbafs::shouldBeSynchronized($strFile))
            {
                $objModel = FilesModel::findByPath($strFile);

                if ($objModel === null)
                {
                    $objModel = Dbafs::addResource($strFile);
                }

                $strUuid = StringUtil::binToUuid($objModel->uuid);

                // Update the hash of the target folder
                Dbafs::updateFolderHashes($strUploadFolder);

                // Update member avatar
                $objMember->avatar = $objModel->uuid;
                $objMember->save();
            }

            // Add the session entry
            $_SESSION['FILES']['avatar'] = array
            (
                'name'     => $file['name'],
                'type'     => $file['type'],
                'tmp_name' => $projectDir . '/' . $strFile,
                'error'    => $file['error'],
                'size'     => $file['size'],
                'uploaded' => true,
                'uuid'     => $strUuid
            );

            System::getContainer()->get('monolog.logger.contao.files')->info('File "' . $strUploadFolder . '/' . $file['name'] . '" has been uploaded');
        }

        unset($_SESSION['FILES']['avatar']);
    }

    /**
     * Parses an avatar to the template
     */
    public static function parseMemberAvatar(?MemberModel $objMember, &$objTemplate, ?string $imgSize): void
    {
        $container = System::getContainer();

        $objTemplate->addImage= true;

        $objTemplate->singleSRC = self::DEFAULT_PICTURE;
        $objTemplate->addFallbackImage = true;

        $projectDir = $container->getParameter('kernel.project_dir');

        // Check if member avatar exists
        if (null === $objMember || null === $objMember->avatar || null === ($objFile = FilesModel::findByUuid($objMember->avatar)) || !\is_file($projectDir.'/'. $objFile->path))
        {
            $objFile = !!($uuidDefault = Config::get('defaultAvatar')) ? FilesModel::findByUuid($uuidDefault) : null;
        }

        // Check if config avatar exists
        if (null === $objFile || !\is_file($projectDir . '/' . $objFile->path))
        {
            return;
        }

        $objTemplate->addFallbackImage = false;
        $imgSize = $imgSize ?? null;

        $figureBuilder = $container
            ->get('contao.image.studio')
            ->createFigureBuilder()
            ->from($objFile->path)
            ->setSize($imgSize)
        ;

        if (null !== ($figure = $figureBuilder->buildIfResourceExists()))
        {
            $figure->applyLegacyTemplateData($objTemplate);
        }
    }

    /**
     * Gets the url for a member avatar
     */
    public static function getMemberAvatarURL(?MemberModel $objMember): string
    {
        // ToDo: Merge logic with parseMemberAvatar
        $projectDir = System::getContainer()->getParameter('kernel.project_dir');

        if (null === $objMember || null === $objMember->avatar || null === ($objFile = FilesModel::findByUuid($objMember->avatar)) || !\is_file($projectDir.'/'. $objFile->path))
        {
            $objFile = !!($uuidDefault = Config::get('defaultAvatar')) ? FilesModel::findByUuid($uuidDefault) : null;
        }

        // Check if config avatar exists
        if (null === $objFile || !\is_file($projectDir . '/' . $objFile->path))
        {
            return self::DEFAULT_PICTURE;
        }

        return $objFile->path;
    }

    /**
     * Deletes an avatar
     * @throws Exception
     */
    public static function deleteAvatar(MemberModel $objMember): void
    {
        if (!!$objMember->avatar)
        {
            $objFile = FilesModel::findByUuid($objMember->avatar) ?: '';
            $projectDir = System::getContainer()->getParameter('kernel.project_dir');

            // Only delete if file exists
            if (!!$objFile && file_exists($projectDir . '/' . $objFile->path))
            {
                $file = new File($objFile->path);
                $file->delete();
            }
        }
    }
}
