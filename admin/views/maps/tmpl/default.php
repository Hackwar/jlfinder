<?php
/**
 * @package     Joomla.Administrator
 * @subpackage  com_finder
 *
 * @copyright   Copyright (C) 2005 - 2018 Open Source Matters, Inc. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

defined('_JEXEC') or die;

use Joomla\CMS\Language\Multilanguage;

JHtml::_('formbehavior.chosen', 'select');
JHtml::_('bootstrap.tooltip');

$listOrder     = $this->escape($this->state->get('list.ordering'));
$listDirn      = $this->escape($this->state->get('list.direction'));
$lang          = JFactory::getLanguage();
$branchFilter  = $this->escape($this->state->get('filter.branch'));
$colSpan       = $branchFilter ? 5 : 6;
JText::script('COM_FINDER_MAPS_CONFIRM_DELETE_PROMPT');

JFactory::getDocument()->addScriptDeclaration('
	Joomla.submitbutton = function(pressbutton)
	{
		if (pressbutton == "map.delete")
		{
			if (confirm(Joomla.JText._("COM_FINDER_MAPS_CONFIRM_DELETE_PROMPT")))
			{
				Joomla.submitform(pressbutton);
			}
			else
			{
				return false;
			}
		}
		Joomla.submitform(pressbutton);
	};
');
?>
<form action="<?php echo JRoute::_('index.php?option=com_finder&view=maps'); ?>" method="post" name="adminForm" id="adminForm">
<?php if (!empty( $this->sidebar)) : ?>
	<div id="j-sidebar-container" class="span2">
		<?php echo $this->sidebar; ?>
	</div>
	<div id="j-main-container" class="span10">
<?php else : ?>
	<div id="j-main-container">
<?php endif; ?>
		<?php echo JLayoutHelper::render('joomla.searchtools.default', array('view' => $this)); ?>
		<div class="clearfix"> </div>
		<?php if (empty($this->items)) : ?>
		<div class="alert alert-no-items">
			<?php echo JText::_('COM_FINDER_MAPS_NO_CONTENT'); ?>
		</div>
		<?php else : ?>
		<table class="table table-striped">
			<thead>
				<tr>
					<th width="1%" class="center nowrap">
						<?php echo JHtml::_('grid.checkall'); ?>
					</th>
					<th width="1%" class="center nowrap">
						<?php echo JHtml::_('searchtools.sort', 'JSTATUS', 'a.state', $listDirn, $listOrder); ?>
					</th>
					<th class="nowrap">
						<?php echo JHtml::_('searchtools.sort', 'JGLOBAL_TITLE', 'd.branch_title', $listDirn, $listOrder); ?>
					</th>
					<?php if (!$branchFilter) : ?>
					<th width="1%" class="nowrap center">
						<?php echo JText::_('COM_FINDER_HEADING_CHILDREN'); ?>
					</th>
					<?php endif; ?>
					<th width="1%" class="nowrap center">
						<span class="icon-publish" aria-hidden="true"></span>
						<span class="hidden-phone"><?php echo JText::_('COM_FINDER_MAPS_COUNT_PUBLISHED_ITEMS'); ?></span>
					</th>
					<th width="1%" class="nowrap center">
						<span class="icon-unpublish" aria-hidden="true"></span>
						<span class="hidden-phone"><?php echo JText::_('COM_FINDER_MAPS_COUNT_UNPUBLISHED_ITEMS'); ?></span>
					</th>
						<?php if (Multilanguage::isEnabled()) : ?>
							<th scope="col" style="width:10%" class="nowrap d-none d-md-table-cell text-center">
								<?php echo HTMLHelper::_('searchtools.sort', 'JGRID_HEADING_LANGUAGE', 'language', $listDirn, $listOrder); ?>
							</th>
						<?php endif; ?>
				</tr>
			</thead>
			<tfoot>
				<tr>
					<td colspan="<?php echo $colSpan; ?>">
						<?php echo $this->pagination->getListFooter(); ?>
					</td>
				</tr>
			</tfoot>
			<tbody>
				<?php $canChange = JFactory::getUser()->authorise('core.manage', 'com_finder'); ?>
				<?php foreach ($this->items as $i => $item) : ?>
				<tr class="row<?php echo $i % 2; ?>">
					<td class="center">
						<?php echo JHtml::_('grid.id', $i, $item->id); ?>
					</td>
					<td class="center nowrap">
						<?php echo JHtml::_('jgrid.published', $item->state, $i, 'maps.', $canChange, 'cb'); ?>
					</td>
					<td>
					<?php
					/**if (trim($item->parent_title, '**') === 'Language')
					{
						$title = FinderHelperLanguage::branchLanguageTitle($item->title);
					}
					else
					{**/
						$key = FinderHelperLanguage::branchSingular($item->title);
						$title = $lang->hasKey($key) ? JText::_($key) : $item->title;
					//}
					?>
					<?php echo str_repeat('<span class="gi">&mdash;</span>', $item->level - 1); ?>
					<label for="cb<?php echo $i; ?>" style="display:inline-block;">
						<?php echo $this->escape($title); ?>
					</label>
					<?php if ($this->escape(trim($title, '**')) === 'Language' && JLanguageMultilang::isEnabled()) : ?>
						<strong><?php echo JText::_('COM_FINDER_MAPS_MULTILANG'); ?></strong>
					<?php endif; ?>
					</td>
					<?php if (!$branchFilter) : ?>
					<td class="center btns">
					<?php if ($item->rgt - $item->lft > 1) : ?>
						<a href="<?php echo JRoute::_('index.php?option=com_finder&view=maps&filter[branch]=' . $item->id); ?>">
							<span class="badge badge-info"><?php echo floor(($item->rgt - $item->lft) / 2); ?></span></a>
					<?php else : ?>
						-
					<?php endif; ?>
					</td>
					<?php endif; ?>
					<td class="center btns">
					<?php if ($item->level > 1) : ?>
						<a class="badge <?php if ((int) $item->count_published > 0) echo 'badge-success'; ?>" title="<?php echo JText::_('COM_FINDER_MAPS_COUNT_PUBLISHED_ITEMS'); ?>" href="<?php echo JRoute::_('index.php?option=com_finder&view=index&filter[state]=1&filter[content_map]=' . $item->id); ?>">
						<?php echo (int) $item->count_published; ?></a>
					<?php else : ?>
						-
					<?php endif; ?>
					</td>
					<td class="center btns">
					<?php if ($item->level > 1) : ?>
						<a class="badge <?php if ((int) $item->count_unpublished > 0) echo 'badge-important'; ?>" title="<?php echo JText::_('COM_FINDER_MAPS_COUNT_UNPUBLISHED_ITEMS'); ?>" href="<?php echo JRoute::_('index.php?option=com_finder&view=index&filter[state]=0&filter[content_map]=' . $item->id); ?>">
						<?php echo (int) $item->count_unpublished; ?></a>
					<?php else : ?>
						-
					<?php endif; ?>
					</td>
					<?php if (Multilanguage::isEnabled()) : ?>
						<td class="small d-none d-md-table-cell text-center">
							<?php echo $item->language; ?>
						</td>
					<?php endif; ?>
				</tr>
				<?php endforeach; ?>
			</tbody>
		</table>
		<?php endif; ?>
	</div>

	<input type="hidden" name="task" value="display" />
	<input type="hidden" name="boxchecked" value="0" />
	<?php echo JHtml::_('form.token'); ?>
</form>
