<?php
/**
 * @package     Joomla.Plugin
 * @subpackage  System.JLFinder
 *
 * @copyright   Copyright (C) 2018 Hannes Papenberg. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

defined('_JEXEC') or die;

/**
 * Joomlager Finder Plugin.
 *
 * @since  1.0
 */
class PlgSystemJLFinder extends JPlugin
{
	public function onAfterInitialise()
	{
		JLoader::register('FinderControllerIndexer', __DIR__ . '/admin/controllers/indexer.json.php');
		JLoader::register('FinderModelSearch', __DIR__ . '/site/models/search.php');
		JLoader::register('FinderIndexer', __DIR__ . '/admin/helpers/indexer/indexer.php');
		JLoader::register('FinderIndexerQuery', __DIR__ . '/admin/helpers/indexer/query.php');
		JLoader::register('FinderModelIndex', __DIR__ . '/admin/models/index.php');
		JLoader::register('FinderModelMaps', __DIR__ . '/admin/models/maps.php');
		JLoader::register('FinderViewSearch', __DIR__ . '/site/views/search/view.html.php');
		JLoader::register('FinderViewIndexer', __DIR__ . '/admin/views/indexer/view.html.php');
		JLoader::register('FinderViewMaps', __DIR__ . '/admin/views/maps/view.html.php');
		JLoader::register('FinderIndexerAdapter', __DIR__ . '/admin/helpers/indexer/adapter.php');
		require_once __DIR__ . '/admin/helpers/html/finder.php';
		JLoader::register('Joomla\\Component\\Finder\\Administrator\\Table\\MapTable', __DIR__ . '/admin/tables/MapTable.php');

		JLoader::registerNamespace('Wamania\\Snowball', __DIR__ . '/libraries/php-stemmer/src', false, false, 'psr4');
		include_once JPATH_PLUGINS . '/system/jlfinder/site/helpers/html/filter.php';

		//For unknown reasons, the FinderIndexerAdapter class is still loaded from the original component folder. Thus this ugly hack...
		//JLoader::register('FinderIndexerAdapter', dirname(__FILE__) . '/admin/helpers/indexer/adapter.php');
		require_once __DIR__ . '/admin/helpers/indexer/indexer.php';
		require_once __DIR__ . '/admin/helpers/indexer/helper.php';
		//require_once __DIR__ . '/admin/helpers/indexer/taxonomy.php';
		require_once __DIR__ . '/admin/helpers/indexer/adapter.php';
	}

	public function onContentPrepareForm(JForm $form, $data)
	{
		if ($form->getName() == 'com_config.component')
		{
			return $this->configForm($form, $data);
		}

		if (strpos($form->getName(), 'com_categories.category') !== false)
		{
			return $this->categoriesForm($form, $data);
		}

		if (strpos($form->getName(), 'com_content.article') !== false)
		{
			return $this->contentForm($form, $data);
		}
	}

	public function onJCalAfterSave($context, $article, $isNew)
	{
		$dispatcher = JEventDispatcher::getInstance();
		JPluginHelper::importPlugin('finder');

		// Trigger the onFinderAfterSave event.
		$dispatcher->trigger('onFinderAfterSave', array($context, $article, $isNew));
	}

	public function onJCalBeforeSave($context, $article, $isNew)
	{
		$dispatcher = JEventDispatcher::getInstance();
		JPluginHelper::importPlugin('finder');

		// Trigger the onFinderBeforeSave event.
		$dispatcher->trigger('onFinderBeforeSave', array($context, $article, $isNew));
	}

	public function onJCalAfterDelete($context, $article)
	{
		$dispatcher = JEventDispatcher::getInstance();
		JPluginHelper::importPlugin('finder');

		// Trigger the onFinderAfterDelete event.
		$dispatcher->trigger('onFinderAfterDelete', array($context, $article));
	}

	public function onJCalChangeState($context, $pks, $value)
	{
		$dispatcher = JEventDispatcher::getInstance();
		JPluginHelper::importPlugin('finder');

		// Trigger the onFinderChangeState event.
		$dispatcher->trigger('onFinderChangeState', array($context, $pks, $value));
	}

	public function onCategoryChangeState($extension, $pks, $value)
	{
		$dispatcher = JEventDispatcher::getInstance();
		JPluginHelper::importPlugin('finder');

		// Trigger the onFinderCategoryChangeState event.
		$dispatcher->trigger('onFinderCategoryChangeState', array($extension, $pks, $value));
	}

	protected function configForm(JForm $form, $data)
	{
		$app = JFactory::getApplication();

		if ($app->input->get('component') != 'com_finder')
		{
			return;
		}

		$form->reset(true);
		$form->loadFile(__DIR__ . '/admin/config.xml', true, '/config');
		$form->bind($data);
	}

	protected function categoriesForm(JForm $form, $data)
	{
		$form->load('<form><fields name="params"><fieldset name="basic"><field
			name="com_finder_dontindex"
			type="radio"
			label="Don\'t add to Smart Search index"
			class="btn-group btn-group-yesno"
			default="0"
			filter="integer"
			>
			<option value="1">JYES</option>
			<option value="0">JNO</option>
		</field></fieldset></fields></form>');
	}

	protected function contentForm(JForm $form, $data)
	{
		$form->load('<form>
			<fields name="attribs">
				<fieldset name="basic">
					<field
						name="finder_dontindex"
						type="radio"
						label="Don\'t add to Smart Search index"
						class="btn-group btn-group-yesno"
						default="0"
						filter="integer"
						>
						<option value="1">JYES</option>
						<option value="0">JNO</option>
					</field>
				</fieldset>
			</fields>
		</form>');
	}
}
