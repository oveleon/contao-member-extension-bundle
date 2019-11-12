<?php

/*
 * This file is part of Oveleon ContaoMemberExtension Bundle.
 *
 * (c) https://www.oveleon.de/
 */

namespace Oveleon\ContaoMemberExtensionBundle;

use Patchwork\Utf8;

/**
 * Class ModuleAvatar
 *
 * @author Fabian Ekert <fabian@oveleon.de>
 */
class ModuleAvatar extends \Module
{

	/**
	 * Template
	 * @var string
	 */
	protected $strTemplate = 'member_avatar';

	/**
	 * Return a wildcard in the back end
	 *
	 * @return string
	 */
	public function generate()
	{
		if (TL_MODE == 'BE')
		{
			/** @var BackendTemplate|object $objTemplate */
			$objTemplate = new \BackendTemplate('be_wildcard');

			$objTemplate->wildcard = '### ' . Utf8::strtoupper($GLOBALS['TL_LANG']['FMD']['avatar'][0]) . ' ###';
			$objTemplate->title = $this->headline;
			$objTemplate->id = $this->id;
			$objTemplate->link = $this->name;
			$objTemplate->href = 'contao/main.php?do=themes&amp;table=tl_module&amp;act=edit&amp;id=' . $this->id;

			return $objTemplate->parse();
		}

		// Return if user is not logged in
		if (!FE_USER_LOGGED_IN)
		{
			return '';
		}

		if ($this->memberTpl != '')
		{
			$this->strTemplate = $this->memberTpl;
		}

		return parent::generate();
	}

	/**
	 * Generate the module
	 */
	protected function compile()
	{
        $this->size = $this->imgSize;

        $this->import('FrontendUser', 'User');

        if ($this->User->avatar == '' && \Config::get('defaultAvatar') == '')
        {
            return '';
        }

        if ($this->User->avatar == '')
        {
            $objFile = \FilesModel::findByUuid(\Config::get('defaultAvatar'));

            if ($objFile === null || !is_file(TL_ROOT . '/' . $objFile->path))
            {
                return '';
            }

            $this->singleSRC = $objFile->path;

            $this->addImageToTemplate($this->Template, $this->arrData);
            return;
        }

        $objFile = \FilesModel::findByUuid($this->User->avatar);

        if ($objFile === null || !is_file(TL_ROOT . '/' . $objFile->path))
        {
            $this->singleSRC = \FilesModel::findByUuid(\Config::get('defaultAvatar'))->path;

            $this->addImageToTemplate($this->Template, $this->arrData);
            return;
        }

        $this->singleSRC = $objFile->path;

        $this->addImageToTemplate($this->Template, $this->arrData, null, null, $objFile);
	}
}
