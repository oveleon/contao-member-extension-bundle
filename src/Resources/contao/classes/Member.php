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
use Contao\Frontend;
use Contao\MemberModel;
use Contao\StringUtil;
use Contao\System;
use Contao\Validator;
use Psr\Log\LogLevel;

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
     * @param int          $userId
     * @param array        $arrData
     */
    public function createAvatar($userId, $arrData)
    {
        $objMember = MemberModel::findById($userId);
        $this->updateAvatar($objMember, $arrData);
    }

    /**
     * Update avatar of member
     *
     * @param MemberModel  $objMember
     * @param array        $arrData
     */
    public function updateAvatar($objUser, $arrData)
    {
        $objMember = MemberModel::findByPk($objUser->id);

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
            // ToDo: Fehler: Dateiname beinhaltet unzulässige Zeichen
            $this->addError($GLOBALS['TL_LANG']['ERR']['filename']);

            return;
        }

        // Invalid file name
        if (!Validator::isValidFileName($file['name']))
        {
            // ToDo: Fehler: Dateiname beinhaltet unzulässige Zeichen
            $this->addError($GLOBALS['TL_LANG']['ERR']['filename']);
            return;
        }

        // File was not uploaded
        // ToDo: File was not uploaded
        if (!is_uploaded_file($file['tmp_name']))
        {
            if ($file['error'] == 1 || $file['error'] == 2)
            {
                $this->addError(sprintf($GLOBALS['TL_LANG']['ERR']['filesize'], $maxlength_kb_readable));
            }
            elseif ($file['error'] == 3)
            {
                $this->addError(sprintf($GLOBALS['TL_LANG']['ERR']['filepartial'], $file['name']));
            }
            elseif ($file['error'] > 0)
            {
                $this->addError(sprintf($GLOBALS['TL_LANG']['ERR']['fileerror'], $file['error'], $file['name']));
            }

            unset($_FILES[$this->strName]);

            return;
        }

        // File is too big
        if ($file['size'] > $maxlength_kb)
        {
            // ToDo: Fehler: Datei zu groß
            $this->addError(sprintf($GLOBALS['TL_LANG']['ERR']['filesize'], $maxlength_kb_readable));
            unset($_SESSION['FILES']['avatar']);

            return;
        }

        $objFile = new File($file['name']);
        $uploadTypes = StringUtil::trimsplit(',', \Config::get('validImageTypes'));

        // File type is not allowed
        if (!\in_array($objFile->extension, $uploadTypes))
        {
            // ToDo: Fehler: Dateityp nicht erlaubt
            $this->addError(sprintf($GLOBALS['TL_LANG']['ERR']['filetype'], $objFile->extension));
            unset($_SESSION['FILES']['avatar']);

            return;
        }

        if ($arrImageSize = @getimagesize($file['tmp_name']))
        {
            $intImageWidth = Config::get('imageWidth');

            // Image exceeds maximum image width
            if ($intImageWidth > 0 && $arrImageSize[0] > $intImageWidth) {
                $this->addError(sprintf($GLOBALS['TL_LANG']['ERR']['filewidth'], $file['name'], $intImageWidth));
                unset($_FILES[$this->strName]);

                return;
            }

            $intImageHeight = Config::get('imageHeight');

            // Image exceeds maximum image height
            if ($intImageHeight > 0 && $arrImageSize[1] > $intImageHeight) {
                $this->addError(sprintf($GLOBALS['TL_LANG']['ERR']['fileheight'], $file['name'], $intImageHeight));
                unset($_FILES[$this->strName]);

                return;
            }
        }

        // Upload valid file type with no width and height -> svg

        // Don't upload if no homedir is assigned
        // ToDo: Add error
        if (!$objMember->assignDir || !$objMember->homeDir)
        {
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
        // Get the upload_max_filesize from the php.ini
        $upload_max_filesize = ini_get('upload_max_filesize');

        // Convert the value to bytes
        if (stripos($upload_max_filesize, 'K') !== false)
        {
            $upload_max_filesize = round($upload_max_filesize * 1024);
        }
        elseif (stripos($upload_max_filesize, 'M') !== false)
        {
            $upload_max_filesize = round($upload_max_filesize * 1024 * 1024);
        }
        elseif (stripos($upload_max_filesize, 'G') !== false)
        {
            $upload_max_filesize = round($upload_max_filesize * 1024 * 1024 * 1024);
        }

        return min($upload_max_filesize, Config::get('maxFileSize'));
    }

    /**
     * Add an error message
     *
     * @param string $strError The error message
     */
    public function addError($strError)
    {
        $this->class = 'error';
        $this->arrErrors[] = $strError;
    }

    public function deleteAvatar($objMember)
    {
        if(!!$objMember->avatar)
        {
            $objFile = FilesModel::findByUuid($objMember->avatar) ?? '';

            // Only delete existing file
            if (!!$objFile && file_exists($objFile->path))
            {
                $file = new File($objFile->path);
                $file->delete();
            }
        }
    }
}
