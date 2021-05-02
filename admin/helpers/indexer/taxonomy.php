<?php
/**
 * @package     Joomla.Administrator
 * @subpackage  com_finder
 *
 * @copyright   Copyright (C) 2005 - 2018 Open Source Matters, Inc. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

defined('_JEXEC') or die;

use Joomla\CMS\Tree\NodeInterface;
use Joomla\Component\Finder\Administrator\Table\MapTable;

/**
 * Taxonomy base class for the Finder indexer package.
 *
 * @since  2.5
 */
class FinderIndexerTaxonomy
{
	/**
	 * An internal cache of taxonomy data.
	 *
	 * @var    array
	 * @since  __DEPLOY_VERSION__
	 */
	public static $taxonomies = array();

	/**
	 * An internal cache of branch data.
	 *
	 * @var    array
	 * @since  __DEPLOY_VERSION__
	 */
	public static $branches = array();

	/**
	 * An internal cache of taxonomy node data for inserting it.
	 *
	 * @var    array
	 * @since  2.5
	 */
	public static $nodes = array();

	/**
	 * Method to add a branch to the taxonomy tree.
	 *
	 * @param   string   $title   The title of the branch.
	 * @param   integer  $state   The published state of the branch. [optional]
	 * @param   integer  $access  The access state of the branch. [optional]
	 *
	 * @return  integer  The id of the branch.
	 *
	 * @since   2.5
	 * @throws  Exception on database error.
	 */
	public static function addBranch($title, $state = 1, $access = 1)
	{
		$node = new stdClass;
		$node->title = $title;
		$node->state = $state;
		$node->access = $access;
		$node->parent_id = 1;
		$node->language = '';

		return self::storeNode($node, 1);
	}

	/**
	 * Method to add a node to the taxonomy tree.
	 *
	 * @param   string   $branch    The title of the branch to store the node in.
	 * @param   string   $title     The title of the node.
	 * @param   integer  $state     The published state of the node. [optional]
	 * @param   integer  $access    The access state of the node. [optional]
	 * @param   string   $language  The language of the node. [optional]
	 *
	 * @return  integer  The id of the node.
	 *
	 * @since   2.5
	 * @throws  Exception on database error.
	 */
	public static function addNode($branch, $title, $state = 1, $access = 1, $language = '')
	{
		// Get the branch id, insert it if it does not exist.
		$branchId = static::addBranch($branch);

		$node = new stdClass;
		$node->title = $title;
		$node->state = $state;
		$node->access = $access;
		$node->parent_id = $branchId;
		$node->language = $language;

		return self::storeNode($node, $branchId);
	}

	/**
	 * Method to add a nested node to the taxonomy tree.
	 * 
	 * @param   string         $branch    The title of the branch to store the node in.
	 * @param   NodeInterface  $node      The source-node of the taxonomy node.
	 * @param   integer        $state     The published state of the node. [optional]
	 * @param   integer        $access    The access state of the node. [optional]
	 * @param   string         $language  The language of the node. [optional]
	 * @param   integer        $branchId  ID of a branch if known. [optional]
	 * 
	 * @return  integer  The id of the node.
	 * 
	 * @since   __DEPLOY_VERSION__
	 */
	public static function addNestedNode($branch, $node, $state = 1, $access = 1, $language = '', $branchId = null)
	{
		if (is_null($node) || $node->getParams()->get('com_finder_dontindex'))
		{
			return $branchId;
		}

		if (!$branchId)
		{
			// Get the branch id, insert it if it does not exist.
			$branchId = static::addBranch($branch);
		}

		$parent = $node->getParent();

		if ($parent && $parent->title !='ROOT' && !$parent->getParams()->get('com_finder_dontindex'))
		{
			$parentId = self::addNestedNode($branch, $parent, $state, $access, $language, $branchId);
		}
		else
		{
			$parentId = $branchId;
		}

		$temp = new stdClass;
		$temp->title = $node->title;
		$temp->state = $state;
		$temp->access = $access;
		$temp->parent_id = $parentId;
		$temp->language = $language;

		return self::storeNode($temp, $parentId);
	}

	/**
	 * A helper method to store a node in the taxonomy
	 * 
	 * @param   object   $node       The node data to include
	 * @param   integer  $parent_id  The parent id of the node to add.
	 * 
	 * @return  integer  The id of the inserted node.
	 * 
	 * @since   __DEPLOY_VERSION__
	 */
	protected static function storeNode($node, $parent_id)
	{
		// Check to see if the node is in the cache.
		if (isset(static::$nodes[$parent_id . ':' . $node->title]))
		{
			return static::$nodes[$parent_id . ':' . $node->title]->id;
		}

		// Check to see if the node is in the table.
		$db = JFactory::getDbo();
		$query = $db->getQuery(true)
			->select('*')
			->from($db->quoteName('#__jlfinder_taxonomy'))
			->where($db->quoteName('parent_id') . ' = ' . $db->quote($parent_id))
			->where($db->quoteName('title') . ' = ' . $db->quote($node->title))
			->where($db->quoteName('language') . ' = ' . $db->quote($node->language));

		$db->setQuery($query);

		// Get the result.
		$result = $db->loadObject();

		// Check if the database matches the input data.
		if (!empty($result) && $result->state == $node->state && $result->access == $node->access)
		{
			// The data matches, add the item to the cache.
			static::$nodes[$parent_id . ':' . $node->title] = $result;

			return static::$nodes[$parent_id . ':' . $node->title]->id;
		}

		/*
		 * The database did not match the input. This could be because the
		 * state has changed or because the node does not exist. Let's figure
		 * out which case is true and deal with it.
		 */
		/** TODO: use factory? **/
		$nodeTable = new MapTable($db);

		if (empty($result))
		{
			// Prepare the node object.
			$nodeTable->title = $node->title;
			$nodeTable->state = (int) $node->state;
			$nodeTable->access = (int) $node->access;
			$nodeTable->language = $node->language;
			$nodeTable->setLocation((int) $parent_id, 'last-child');
		}
		else
		{
			// Prepare the node object.
			$nodeTable->id = (int) $result->id;
			$nodeTable->title = $result->title;
			$nodeTable->state = (int) $result->title;
			$nodeTable->access = (int) $result->access;
			$nodeTable->language = $node->language;
			$nodeTable->setLocation($result->parent_id, 'last-child');
		}

		// Store the branch.
		$nodeTable->check();
		$nodeTable->store();
		$nodeTable->rebuildPath($nodeTable->id);

		// Add the node to the cache.
		static::$nodes[$parent_id . ':' . $nodeTable->title] = (object) $nodeTable->getProperties();

		return static::$nodes[$parent_id . ':' . $nodeTable->title]->id;
	}

	/**
	 * Method to add a map entry between a link and a taxonomy node.
	 *
	 * @param   integer  $linkId  The link to map to.
	 * @param   integer  $nodeId  The node to map to.
	 *
	 * @return  boolean  True on success.
	 *
	 * @since   2.5
	 * @throws  Exception on database error.
	 */
	public static function addMap($linkId, $nodeId)
	{
		if (is_null($nodeId))
		{
			return true;
		}

		// Insert the map.
		$db = JFactory::getDbo();

		$query = $db->getQuery(true)
			->select($db->quoteName('link_id'))
			->from($db->quoteName('#__finder_taxonomy_map'))
			->where($db->quoteName('link_id') . ' = ' . (int) $linkId)
			->where($db->quoteName('node_id') . ' = ' . (int) $nodeId);
		$db->setQuery($query);
		$db->execute();
		$id = (int) $db->loadResult();

		if (!$id)
		{
			$map = new stdClass;
			$map->link_id = (int) $linkId;
			$map->node_id = (int) $nodeId;
			$db->insertObject('#__finder_taxonomy_map', $map);
		}

		return true;
	}

	/**
	 * Method to get the title of all taxonomy branches.
	 *
	 * @return  array  An array of branch titles.
	 *
	 * @since   2.5
	 * @throws  Exception on database error.
	 */
	public static function getBranchTitles()
	{
		$db = JFactory::getDbo();

		// Set user variables
		$groups = implode(',', JFactory::getUser()->getAuthorisedViewLevels());

		// Create a query to get the taxonomy branch titles.
		$query = $db->getQuery(true)
			->select($db->quoteName('title'))
			->from($db->quoteName('#__jlfinder_taxonomy'))
			->where($db->quoteName('parent_id') . ' = 1')
			->where($db->quoteName('state') . ' = 1')
			->where($db->quoteName('access') . ' IN (' . $groups . ')');

		// Get the branch titles.
		$db->setQuery($query);

		return $db->loadColumn();
	}

	/**
	 * Method to find a taxonomy node in a branch.
	 *
	 * @param   string  $branch  The branch to search.
	 * @param   string  $title   The title of the node.
	 *
	 * @return  mixed  Integer id on success, null on no match.
	 *
	 * @since   2.5
	 * @throws  Exception on database error.
	 */
	public static function getNodeByTitle($branch, $title)
	{
		$db = JFactory::getDbo();

		// Set user variables
		$groups = implode(',', JFactory::getUser()->getAuthorisedViewLevels());

		// Create a query to get the node.
		$query = $db->getQuery(true)
			->select('t1.*')
			->from($db->quoteName('#__jlfinder_taxonomy') . ' AS t1')
			->join('INNER', $db->quoteName('#__jlfinder_taxonomy') . ' AS t2 ON t2.id = t1.parent_id')
			->where('t1.access IN (' . $groups . ')')
			->where('t1.state = 1')
			->where('t1.title LIKE ' . $db->quote($db->escape($title) . '%'))
			->where('t2.access IN (' . $groups . ')')
			->where('t2.state = 1')
			->where('t2.title = ' . $db->quote($branch));

		// Get the node.
		$db->setQuery($query, 0, 1);

		return $db->loadObject();
	}

	/**
	 * Method to remove map entries for a link.
	 *
	 * @param   integer  $linkId  The link to remove.
	 *
	 * @return  boolean  True on success.
	 *
	 * @since   2.5
	 * @throws  Exception on database error.
	 */
	public static function removeMaps($linkId)
	{
		// Delete the maps.
		$db = JFactory::getDbo();
		$query = $db->getQuery(true)
			->delete($db->quoteName('#__finder_taxonomy_map'))
			->where($db->quoteName('link_id') . ' = ' . (int) $linkId);
		$db->setQuery($query);
		$db->execute();

		return true;
	}

	/**
	 * Method to remove orphaned taxonomy nodes and branches.
	 *
	 * @return  integer  The number of deleted rows.
	 *
	 * @since   2.5
	 * @throws  Exception on database error.
	 */
	public static function removeOrphanNodes()
	{
		// Delete all orphaned nodes.
		$affectedRows = 0;
		$nodes = 0;
		$db = JFactory::getDbo();
		$nodeTable = new MapTable($db);
		$query     = $db->getQuery(true);

		$query->select($db->quoteName('t.id'))
			->from($db->quoteName('#__jlfinder_taxonomy', 't'))
			->join('LEFT', $db->quoteName('#__finder_taxonomy_map', 'm') . ' ON ' . $db->quoteName('m.node_id') . '=' . $db->quoteName('t.id'))
			->where($db->quoteName('t.parent_id') . ' > 1 ')
			->where('t.lft + 1 = t.rgt')
			->where($db->quoteName('m.link_id') . ' IS NULL');

		$db->setQuery($query);

		do
		{
			$nodes = $db->loadColumn();

			foreach ($nodes as $node)
			{
				$nodeTable->delete($node);
				$affectedRows++;
			}
		}
		while ($nodes);

		return $affectedRows;
	}

	/**
	 * Get a taxonomy based on its id or all taxonomies
	 * 
	 * @param   integer  $id  Id of the taxonomy
	 * 
	 * @return  object|array  A taxonomy object or an array of all taxonomies
	 * 
	 * @since   __DEPLOY_VERSION__
	 */
	public static function getTaxonomy($id = 0)
	{
		if (!count(self::$taxonomies))
		{
			$db    = JFactory::getDbo();
			$query = $db->getQuery(true);

			$query->select(array('id','parent_id','lft','rgt','level','path','title','alias','state','access','language'))
				->from($db->quoteName('#__jlfinder_taxonomy'))
				->order($db->quoteName('lft'));

			$db->setQuery($query);
			self::$taxonomies = $db->loadObjectList('id');
		}

		if ($id == 0)
		{
			return self::$taxonomies;
		}

		if (isset(self::$taxonomies[$id]))
		{
			return self::$taxonomies[$id];
		}

		return false;
	}

	/**
	 * Get a taxonomy branch object based on its title or all branches
	 * 
	 * @param   string  $title  Title of the branch
	 * 
	 * @return  object|array  The object with the branch data or an array of all branches
	 * 
	 * @since   __DEPLOY_VERSION__
	 */
	public static function getBranch($title = '')
	{
		if (!count(self::$branches))
		{
			$taxonomies = self::getTaxonomy();

			foreach ($taxonomies as $t)
			{
				if ($t->level == 1)
				{
					self::$branches[$t->title] = $t;
				}
			}
		}

		if ($title == '')
		{
			return self::$branches;
		}

		if (isset(self::$branches[$title]))
		{
			return self::$branches[$title];
		}

		return false;
	}
}