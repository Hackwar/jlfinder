<?php
/**
 * @package     Joomla.Administrator
 * @subpackage  com_finder
 *
 * @copyright   Copyright (C) 2005 - 2018 Open Source Matters, Inc. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

defined('_JEXEC') or die;

/**
 * Indexer view class for Finder.
 *
 * @since  2.5
 */
class FinderViewIndexer extends JViewLegacy
{
	public function display($tpl = null)
	{
		$this->addTemplatePath(__DIR__ . '/tmpl/');

		return parent::display($tpl);
	}
}
