<?php
/**
 * @package     Joomla.Administrator
 * @subpackage  com_finder
 *
 * @copyright   Copyright (C) 2005 - 2018 Open Source Matters, Inc. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

defined('_JEXEC') or die;

JHtml::_('behavior.keepalive');
JHtml::_('behavior.core');
JHtml::_('jquery.framework');
JHtml::_('script', 'plugins/system/jlfinder/admin/assets/js/indexer.js', array('version' => 'auto'));
JFactory::getDocument()->addScriptDeclaration('var msg = "' . JText::_('COM_FINDER_INDEXER_MESSAGE_COMPLETE') . '";');
?>

<div id="finder-indexer-container">
	<br /><br />
	<h1 id="finder-progress-header"><?php echo JText::_('COM_FINDER_INDEXER_HEADER_INIT'); ?></h1>

	<p id="finder-progress-message"><?php echo JText::_('COM_FINDER_INDEXER_MESSAGE_INIT'); ?></p>

	<div id="progress" class="progress progress-striped active">
		<div id="progress-bar" class="bar bar-success" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100"></div>
	</div>

	<?php if (JDEBUG) : ?>
	<dl id="finder-debug-data">
	</dl>
	<?php endif; ?>
	<input id="finder-indexer-token" type="hidden" name="<?php echo JFactory::getSession()->getFormToken(); ?>" value="1" />
</div>
