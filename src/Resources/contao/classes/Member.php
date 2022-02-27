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

use Contao\Config;
use Contao\CoreBundle\Monolog\ContaoContext;
use Contao\Dbafs;
use Contao\File;
use Contao\FilesModel;
use Contao\FileUpload;
use Contao\Frontend;
use Contao\FrontendUser;
use Contao\MemberModel;
use Contao\StringUtil;
use Contao\System;
use Contao\Validator;
use Psr\Log\LogLevel;


/**
 * Class Member
 * 
 * @property int $avatar UUID of the avatar
 */
class Member extends Frontend
{
    /**
     * MemberAvatar file name
     *
     * @var string
     */
    protected $avatarName = 'memberAvatar';

    /**
     * Create avatar for a member | Registration
     *
     * @param int   $userId
     * @param array $arrData
     *
     * @return void
     */
    public function createAvatar(int $userId, array $arrData): void
    {
        $objMember = MemberModel::findById($userId);
        $this->processAvatar($objMember, $arrData);
    }

    /**
     * Update avatar of a member | Login
     *
     * @param FrontendUser  $objUser
     * @param array         $arrData
     *
     * @return void
     */
    public function updateAvatar(FrontendUser $objUser, $arrData): void
    {
        $objMember = MemberModel::findById($objUser->id);
        $this->processAvatar($objMember, $arrData);
    }

    /**
     * Process avatar upload for a member
     *
     * @param MemberModel   $objMember
     * @param array         $arrData
     *
     * @return void
     */
    protected function processAvatar(MemberModel $objMember, array $arrData): void
    {
        $objMember = MemberModel::findByPk($objMember->id);

        if ($objMember === null)
        {
            return;
        }

        $file = $_SESSION['FILES']['avatar'];
        $maxlength_kb = $this->getMaximumUploadSize();
        $maxlength_kb_readable = $this->getReadableSize($maxlength_kb);

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

        $objFile = new File($file['name']);
        $uploadTypes = StringUtil::trimsplit(',', \Config::get('validImageTypes'));

        // File type is not allowed
        if (!\in_array($objFile->extension, $uploadTypes))
        {
            // ToDo: add error message for not allowed file type
            unset($_SESSION['FILES']['avatar']);

            return;
        }

        if ($arrImageSize = @getimagesize($file['tmp_name']))
        {
            $intImageWidth = Config::get('imageWidth');

            // Image exceeds maximum image width
            if ($intImageWidth > 0 && $arrImageSize[0] > $intImageWidth) {
                // ToDo: add error message for exceeding width
                unset($_SESSION['FILES']['avatar']);

                return;
            }

            $intImageHeight = Config::get('imageHeight');

            // Image exceeds maximum image height
            if ($intImageHeight > 0 && $arrImageSize[1] > $intImageHeight) {
                // ToDo: add error message for exceeding height
                unset($_SESSION['FILES']['avatar']);

                return;
            }
        }

        // Upload valid file type with no width and height -> svg

        // Don't upload if no homedir is assigned
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
            throw new \Exception("Invalid upload folder ID $intUploadFolder");
        }

        $strUploadFolder = $objUploadFolder->path;

        // Store the file if the upload folder exists
        $projectDir = System::getContainer()->getParameter('kernel.project_dir');

        if (!!$strUploadFolder & is_dir($projectDir . '/' . $strUploadFolder))
        {
            // Delete existing avatar if it exists
            $this->deleteAvatar($objMember);

            $this->import('Files');

            // Rename file
            $file['name'] =  $this->avatarName . '.' . $objFile->extension;

            // Move the file to its destination
            $this->Files->move_uploaded_file($file['tmp_name'], $strUploadFolder . '/' . $file['name']);
            $this->Files->chmod($strUploadFolder . '/' . $file['name'], 0666 & ~umask());

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

            // Add a log entry
            $logger = System::getContainer()->get('monolog.logger.contao');
            $logger->log(LogLevel::INFO, 'File "' . $strUploadFolder . '/' . $file['name'] . '" has been uploaded', array('contao' => new ContaoContext(__METHOD__, TL_FILES)));
        }

        unset($_SESSION['FILES']['avatar']);
    }

    /**
     * Return the maximum upload file size in bytes
     *
     * @return string
     */
    protected function getMaximumUploadSize()
    {
        if ($this->maxlength > 0)
        {
            return $this->maxlength;
        }

        return FileUpload::getMaxUploadSize();
    }

    /**
     * @param MemberModel $objMember
     *
     * @return void
     */
    public function deleteAvatar(MemberModel $objMember): void
    {
        if(!!$objMember->avatar)
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
