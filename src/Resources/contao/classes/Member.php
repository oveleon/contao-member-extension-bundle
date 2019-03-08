<?php

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Oveleon\ContaoMemberExtensionBundle;

/**
 * Class Member
 *
 * @author Fabian Ekert <fabian@oveleon.de>
 */
class Member extends \Frontend
{
    /**
     * Update avatar of member
     *
     * @param \FrontendUser $objUser
     * @param array         $arrData
     */
    public function updateAvatar($objUser, $arrData)
    {
        $objMember = \MemberModel::findByPk($objUser->id);

        if ($objMember === null)
        {
            return;
        }

        $file = $_SESSION['FILES']['avatar'];
        $maxlength_kb = $this->getMaximumUploadSize();

        // Sanitize the filename
        try
        {
            $file['name'] = \StringUtil::sanitizeFileName($file['name']);
        }
        catch (\InvalidArgumentException $e)
        {
            // ToDo: Fehler: Dateiname beinhaltet unzulässige Zeichen

            return;
        }

        // Invalid file name
        if (!\Validator::isValidFileName($file['name']))
        {
            // ToDo: Fehler: Dateiname beinhaltet unzulässige Zeichen

            return;
        }

        // File was not uploaded
        // ToDo

        // File is too big
        if ($file['size'] > $maxlength_kb)
        {
            // ToDo: Fehler: Datei zu groß
            unset($_SESSION['FILES']['avatar']);

            return;
        }

        $objFile = new \File($file['name']);
        $uploadTypes = \StringUtil::trimsplit(',', \Config::get('validImageTypes'));

        // File type is not allowed
        if (!\in_array($objFile->extension, $uploadTypes))
        {
            // ToDo: Fehler: Dateityp nicht erlaubt
            unset($_SESSION['FILES']['avatar']);

            return;
        }

        if ($arrImageSize = @getimagesize($file['tmp_name']))
        {
            $intImageWidth = \Config::get('imageWidth');

            // Image exceeds maximum image width
            if ($intImageWidth > 0 && $arrImageSize[0] > $intImageWidth)
            {
                // ToDo: Fehler: Bild ist zu groß in der breite
                unset($_SESSION['FILES']['avatar']);

                return;
            }

            $intImageHeight = \Config::get('imageHeight');

            // Image exceeds maximum image height
            if ($intImageHeight > 0 && $arrImageSize[1] > $intImageHeight)
            {
                // ToDo: Fehler: Bild ist zu groß in der höhe
                unset($_SESSION['FILES']['avatar']);

                return;
            }

            $_SESSION['FILES']['avatar'] = $_SESSION['FILES']['avatar'];

            // Overwrite the upload folder with user's home directory
            if ($objMember->assignDir && $objMember->homeDir)
            {
                $intUploadFolder = $objMember->homeDir;
            }

            $objUploadFolder = \FilesModel::findByUuid($intUploadFolder);

            // The upload folder could not be found
            if ($objUploadFolder === null)
            {
                throw new \Exception("Invalid upload folder ID $intUploadFolder");
            }

            $strUploadFolder = $objUploadFolder->path;

            // Store the file if the upload folder exists
            if ($strUploadFolder != '' && is_dir(TL_ROOT . '/' . $strUploadFolder))
            {
                $this->import('Files');

                // Move the file to its destination
                $this->Files->move_uploaded_file($file['tmp_name'], $strUploadFolder . '/' . $file['name']);
                $this->Files->chmod($strUploadFolder . '/' . $file['name'], \Config::get('defaultFileChmod'));

                $strUuid = null;
                $strFile = $strUploadFolder . '/' . $file['name'];

                // Generate the DB entries
                if (\Dbafs::shouldBeSynchronized($strFile))
                {
                    $objModel = \FilesModel::findByPath($strFile);

                    if ($objModel === null)
                    {
                        $objModel = \Dbafs::addResource($strFile);
                    }

                    $strUuid = \StringUtil::binToUuid($objModel->uuid);

                    // Update the hash of the target folder
                    \Dbafs::updateFolderHashes($strUploadFolder);

                    // Update member avatar
                    $objMember->avatar = $objModel->uuid;
                    $objMember->save();
                }

                // Add the session entry (see #6986)
                $_SESSION['FILES']['avatar'] = array
                (
                    'name'     => $file['name'],
                    'type'     => $file['type'],
                    'tmp_name' => TL_ROOT . '/' . $strFile,
                    'error'    => $file['error'],
                    'size'     => $file['size'],
                    'uploaded' => true,
                    'uuid'     => $strUuid
                );

                // Add a log entry
                $this->log('File "' . $strUploadFolder . '/' . $file['name'] . '" has been uploaded', __METHOD__, TL_FILES);
            }
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

        return min($upload_max_filesize, \Config::get('maxFileSize'));
    }
}