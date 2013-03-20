<?php
/**
 * @package     Joomla.Libraries
 * @subpackage  Tags
 *
 * @copyright   Copyright (C) 2005 - 2013 Open Source Matters, Inc. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE
 */

defined('JPATH_PLATFORM') or die;

/**
 * Tags helper class, provides methods to perform various tasks relevant
 * tagging of content.
 *
 * @package     Joomla.Libraries
 * @subpackage  Tags
 * @since       3.1
 */
class JTags
{
	/**
	 * Method to add or update tags associated with an item. Generally used as a postSaveHook.
	 *
	 * @param   integer          $id        The id (primary key) of the item to be tagged.
	 * @param   string           $prefix    Dot separated string with the option and view for a url.
	 * @param   array            $isNew     Flag indicating this item is new.
	 * @param   JControllerForm  $item      A JControllerForm object usually from a Post Save Hook
	 * @param   array            $tags      Array of tags to be applied.
	 * @param   array            $fieldMap  Associative array of values to core_content field.
	 *
	 * @return  void
	 *
	 * @since   3.1
	 */
	public function tagItem($id, $prefix, $isNew, $item, $tags = array(), $fieldMap = array())
	{
		$app = JFactory::getApplication();

		// Pre-process tags for adding new ones
		if (is_array($tags) && !empty($tags))
		{
			// We will use the tags table to store them
			JTable::addIncludePath(JPATH_ADMINISTRATOR . '/components/com_tags/tables');
			$tagTable = JTable::getInstance('Tag', 'TagsTable');

			foreach ($tags as $key => $tag)
			{
				// Currently a new tag is a non-numeric
				if (!is_numeric($tag))
				{
					// Unset the tag to avoid trying to insert a wrong value
					unset($tags[$key]);

					// Remove the #new# prefix that identifies new tags
					$tagText = str_replace('#new#', '', $tag);

					// Clear old data if exist
					$tagTable->reset();

					// Try to load the selected tag
					if ($tagTable->load(array('title' => $tagText)))
					{
						$tags[] = $tagTable->id;

					}
					else
					{
						// Prepare tag data
						$tagTable->id        = 0;
						$tagTable->title     = $tagText;
						$tagTable->published = 1;

						// $tagTable->language = property_exists ($item, 'language') ? $item->language : '*';
						$tagTable->language = '*';
						$tagTable->access   = property_exists($item, 'access') ? $item->access : 0;

						// Make this item a child of the root tag
						$tagTable->setLocation($tagTable->getRootId(), 'last-child');

						// Try to store tag
						if ($tagTable->check())
						{
							// Assign the alias as path (autogenerated tags have always level 1)
							$tagTable->path = $tagTable->alias;

							if ($tagTable->store())
							{
								$tags[] = $tagTable->id;
							}
						}
					}
				}
			}

			unset($tag);
		}

		// Check again that we have tags
		if (is_array($tags) && empty($tags))
		{
			return false;
		}

		$db = JFactory::getDbo();

		// Set up the field mapping array
		if (empty($fieldMap))
		{
			$typeId = $this->getTypeId($prefix);
			$contenttype = JTable::getInstance('Contenttype');
			$contenttype->load($typeId);
			$map = json_decode($contenttype->field_mappings, true);

			foreach ($map['common'][0] as $i => $field)
			{
				if ($field && $field != 'null' && property_exists($item, $field))
				{
					$fieldMap[$i] = $item->$field;
				}
			}
		}

		$types = $this->getTypes('objectList', $prefix, true);
		$type = $types[0];

		$typeid = $type->type_id;

		if ($id == 0)
		{
			$queryid = $db->getQuery(true);

			$queryid->select($db->qn('id'))
				->from($db->qn($type->table))
				->where($db->qn('type_alias') . ' = ' . $db->q($prefix));
			$db->setQuery($queryid);
			$id = $db->loadResult();
		}

		if ($isNew == 0)
		{
			// Delete the old tag maps.
			$query = $db->getQuery(true);
			$query->delete();
			$query->from($db->quoteName('#__contentitem_tag_map'));
			$query->where($db->quoteName('type_alias') . ' = ' . $db->quote($prefix));
			$query->where($db->quoteName('content_item_id') . ' = ' . (int) $id);
			$db->setQuery($query);
			$db->execute();
		}

		// Set the new tag maps.
		if (!empty($tags))
		{
			// First we fill in the core_content table.
			$querycc = $db->getQuery(true);

			// Check if the record is already there in a content table if it is not a new item.
			// It could be old but never tagged.
			if ($isNew == 0)
			{
				$querycheck = $db->getQuery(true);
				$querycheck->select($db->qn('core_content_id'))
					->from($db->qn('#__core_content'))
					->where(
						array(
							$db->qn('core_content_item_id') . ' = ' . $id,
							$db->qn('core_type_alias') . ' = ' . $db->q($prefix)
						)
				);
				$db->setQuery($querycheck);

				$ccId = $db->loadResult();
			}

			// For new items we need to get the id from the actual table.
			// Throw an exception if there is no matching record
			if ($id == 0)
			{
				$queryid = $db->getQuery(true);
				$queryid->select($db->qn('id'));
				$queryid->from($db->qn($type->table));
				$queryid->where($db->qn($map['core_alias']) . ' = ' . $db->q($fieldMap['core_alias']));
				$db->setQuery($queryid);
				$id = $db->loadResult();
				$fieldMap['core_content_item_id'] = $id;
			}

			// If there is no record in #__core_content we do an insert. Otherwise an update.
			if ($isNew == 1 || empty($ccId))
			{
				$quotedValues = array();

				foreach ($fieldMap as $value)
				{
					$quotedValues[] = $db->q($value);
				}

				$values = implode(',', $quotedValues);
				$values = $values . ',' . (int) $typeid . ', ' . $db->q($prefix);

				$querycc->insert($db->quoteName('#__core_content'))
					->columns($db->quoteName(array_keys($fieldMap)))
					->columns($db->qn('core_type_id'))
					->columns($db->qn('core_type_alias'))
					->values($values);
			}
			else
			{
				$setList = '';

				foreach ($fieldMap as $fieldname => $value)
				{
					$setList .= $db->qn($fieldname) . ' = ' . $db->q($value) . ',';
				}

				$setList = $setList . ' ' . $db->qn('core_type_id') . ' = ' . $typeid . ',' . $db->qn('core_type_alias') . ' = ' . $db->q($prefix);

				$querycc->update($db->qn('#__core_content'));
				$querycc->where($db->qn('core_content_item_id') . ' = ' . $id);
				$querycc->where($db->qn('core_type_alias') . ' = ' . $db->q($prefix));
				$querycc->set($setList);
			}

			$db->setQuery($querycc);
			$db->execute();

			// Get the core_core_content_id from the new record if we do not have it.
			if (empty($ccId))
			{
				$queryCcid = $db->getQuery(true);
				$queryCcid->select($db->qn('core_content_id'));
				$queryCcid->from($db->qn('#__core_content'));
				$queryCcid->where($db->qn('core_content_item_id') . ' = ' . $id);
				$queryCcid->where($db->qn('core_type_alias') . ' = ' . $db->q($prefix));

				$db->setQuery($queryCcid);
				$ccId = $db->loadResult();
			}

			// Have to break this up into individual queries for cross-database support.
			foreach ($tags as $tag)
			{
				$query2 = $db->getQuery(true);
				$query2->insert('#__contentitem_tag_map');
				$query2->columns(array($db->quoteName('type_alias'), $db->quoteName('content_item_id'), $db->quoteName('tag_id'), $db->quoteName('tag_date'), $db->quoteName('core_content_id')));
				$query2->clear('values');
				$query2->values($db->q($prefix) . ', ' . (int) $id . ', ' . $db->q($tag) . ', ' . $query2->currentTimestamp() . ', ' . (int) $ccId);
				$db->setQuery($query2);
				$db->execute();
			}
		}

		return;
	}

	/**
	 * Method to add tags associated to a list of items. Generally used for batch processing.
	 *
	 * @param   array    $tag       Tag to be applied. Note that his method handles single tags only.
	 * @param   integer  $ids       The id (primary key) of the items to be tagged.
	 * @param   string   $contexts  Dot separated string with the option and view for a url.
	 *
	 * @return  void
	 *
	 * @since   3.1
	 */
	public function tagItems($tag, $ids, $contexts)
	{
		// Method is not ready for use
		return;

		// Check whether the tag is present already.
		$db    = JFactory::getDbo();
		$query = $db->getQuery(true);
		$query->delete();
		$query->from($db->quoteName('#__contentitem_tag_map'));
		$query->where($db->quoteName('type_alias') . ' = ' . $db->quote($prefix));
		$query->where($db->quoteName('content_item_id') . ' = ' . (int) $pk);
		$query->where($db->quoteName('tag_id') . ' = ' . (int) $tag);
		$db->setQuery($query);
		$result = $db->loadResult();
		$query->execute();

		self::tagItem($id, $prefix, $tags, $isNew, null);
		$query2 = $db->getQuery(true);

		$query2->insert($db->quoteName('#__contentitem_tag_map'));
		$query2->columns(array($db->quoteName('type_alias'), $db->quoteName('content_item_id'), $db->quoteName('tag_id'), $db->quoteName('tag_date')));

		$query2->clear('values');
		$query2->values($db->quote($prefix) . ', ' . (int) $pk . ', ' . $tag . ', ' . $query->currentTimestamp());
		$db->setQuery($query2);
		$db->execute();
	}

	/**
	 * Method to remove  tags associated with a list of items. Generally used for batch processing.
	 *
	 * @param   integer  $id      The id (primary key) of the item to be untagged.
	 * @param   string   $prefix  Dot separated string with the option and view for a url.
	 *
	 * @return  void
	 *
	 * @since   3.1
	 */
	public function unTagItem($id, $prefix)
	{
		$db = JFactory::getDbo();
		$query = $db->getQuery(true);
		$query->delete('#__contentitem_tag_map');
		$query->where($db->quoteName('type_alias') . ' = ' . $db->quote($prefix));
		$query->where($db->quoteName('content_item_id') . ' = ' . (int) $id);
		$db->setQuery($query);
		$db->execute();

		return;
	}

	/**
	 * Method to get a list of tags for a given item.
	 * Normally used for displaying a list of tags within a layout
	 *
	 * @param   integer  $id      The id (primary key) of the item to be tagged.
	 * @param   string   $prefix  Dot separated string with the option and view to be used for a url.
	 *
	 * @return  string   Comma separated list of tag Ids.
	 *
	 * @since   3.1
	 */
	public function getTagIds($id, $prefix)
	{
		if (!empty($id))
		{
			if (is_array($id))
			{
				$id = implode(',', $id);
			}

			$db = JFactory::getDbo();
			$query = $db->getQuery(true);

			// Load the tags.
			$query->clear();
			$query->select($db->quoteName('t.id'));

			$query->from($db->quoteName('#__tags') . ' AS t ');
			$query->join('INNER', $db->quoteName('#__contentitem_tag_map') . ' AS m' .
				' ON ' . $db->quoteName('m.tag_id') . ' = ' . $db->quoteName('t.id') . ' AND ' .
						$db->quoteName('m.type_alias') . ' = ' .
						$db->quote($prefix) . ' AND ' . $db->quoteName('m.content_item_id') . ' IN ( ' . $id . ')');

			$db->setQuery($query);

			// Add the tags to the content data.
			$tagsList = $db->loadColumn();
			$this->tags = implode(',', $tagsList);
		}
		else
		{
			$this->tags = array();
		}

		return $this->tags;
	}

	/**
	 * Method to get a list of tags for an item, optionally with the tag data.
	 *
	 * @param   integer  $contentType  Name of an item. Dot separated.
	 * @param   integer  $id           Item ID
	 * @param   boolean  $getTagData   If true, data from the tags table will be included, defaults to true.
	 *
	 * @return  array    Array of of tag objects
	 *
	 * @since   3.1
	 */
	public function getItemTags($contentType, $id, $getTagData = true)
	{
		if (is_array($id))
		{
			$id = implode($id);
		}
		// Initialize some variables.
		$db = JFactory::getDbo();
		$query = $db->getQuery(true);

		$query->select(array($db->quoteName('m.tag_id'), $db->quoteName('t') . '.*'));
		$query->from($db->quoteName('#__contentitem_tag_map') . ' AS m ');
		$query->where(
			array(
				$db->quoteName('m.type_alias') . ' = ' . $db->quote($contentType),
				$db->quoteName('m.content_item_id') . ' = ' . $db->quote($id),
				$db->quoteName('t.published') . ' = 1'
			)
		);

		$user = JFactory::getUser();
		$groups	= implode(',', $user->getAuthorisedViewLevels());

		$query->where('t.access IN (' . $groups . ')');

		// Optionally filter on language
		if (empty($language))
		{
			$language = JComponentHelper::getParams('com_tags')->get('tag_list_language_filter', 'all');
		}

		if ($language != 'all')
		{
			if ($language == 'current_language')
			{
				$language = JHelperContent::getCurrentLanguage();
			}
			$query->where($db->qn('language') . ' IN (' . $db->q($language) . ', ' . $db->q('*') . ')');
		}

		if ($getTagData)
		{
			$query->join('INNER', $db->quoteName('#__tags') . ' AS t ' . ' ON ' . $db->quoteName('m.tag_id') . ' = ' . $db->quoteName('t.id'));
		}

		$db->setQuery($query);
		$this->itemTags = $db->loadObjectList();

		return $this->itemTags;
	}

	/**
	 * Method to get a query to retrieve a detailed list of items for a tag.
	 *
	 * @param   mixed    $tagId            Tag or array of tags to be matched
	 * @param   mixed    $typesr           Null, type or array of type aliases for content types to be included in the results
	 * @param   boolean  $includeChildren  True to include the results from child tags
	 * @param   boolean  $anyOrAll         True to include items matching at least one tag, false to include
	 *                                     items all tags in the array.
	 * @param   string   $languageFilter   Optional filter on language. Options are 'all', 'current' or any string.
	 *
	 * @return  JDatabaseQuery  Query to retrieve a list of tags
	 *
	 * @since   3.1
	 */
	public function getTagItemsQuery($tagId, $typesr = null, $includeChildren = false, $orderByOption = 'title', $orderDir = 'ASC',
			$anyOrAll = true, $languageFilter = 'all')
	{
		// Create a new query object.
		$db = JFactory::getDbo();
		$query = $db->getQuery(true);
		$user = JFactory::getUser();
		$nullDate = $db->q($db->getNullDate());

		$ntagsr = substr_count($tagId, ',') + 1;

		// If we want to include children we have to adjust the list of tags.
		// We do not search child tags when the match all option is selected.
		if ($includeChildren)
		{
			if (!is_array($tagId))
			{
				$tagIdArray = explode(',', $tagId);
			}
			else
			{
				$tagIdArray = $tagId;
			}

			$tagTreeList = '';

			foreach ($tagIdArray as $tag)
			{
				if ($this->getTagTreeArray($tag, $tagTreeArray))
				{
					$tagTreeList .= implode(',', $this->getTagTreeArray($tag, $tagTreeArray)) . ',';
				}
			}
			if ($tagTreeList)
			{
				$tagId = trim($tagTreeList, ',');
			}
		}
		if (is_array($tagId))
		{
			$tagId = implode($tagId);
		}
		// M is the mapping table. C is the core_content table. Ct is the content_types table.
		$query->select('m.type_alias, m.content_item_id, m.core_content_id, count(m.tag_id) AS match_count,  MAX(m.tag_date) as tag_date, MAX(c.core_title) AS core_title');
		$query->select('MAX(c.core_alias) AS core_alias, MAX(c.core_body) AS core_body, MAX(c.core_state) AS core_state, MAX(c.core_access) AS core_access');
		$query->select('MAX(c.core_metadata) AS core_metadata, MAX(c.core_created_user_id) AS core_created_user_id, MAX(c.core_created_by_alias) AS core_created_by_alias');
		$query->select('MAX(c.core_created_time) as core_created_time, MAX(c.core_images) as core_images');
		$query->select('CASE WHEN c.core_modified_time = ' . $nullDate . ' THEN c.core_created_time ELSE c.core_modified_time END as core_modified_time');
		$query->select('MAX(c.core_language) AS core_language');
		$query->select('MAX(c.core_publish_up) AS core_publish_up, MAX(c.core_publish_down) as core_publish_down');
		$query->select('MAX(ct.type_title) AS content_type_title, MAX(ct.router) AS router');

		$query->from('#__contentitem_tag_map AS m');
		$query->join('INNER', '#__core_content AS c ON m.type_alias = c.core_type_alias AND m.core_content_id = c.core_content_id');
		$query->join('INNER', '#__content_types AS ct ON ct.type_alias = m.type_alias');

		// Join over the users for the author and email
		$query->select("CASE WHEN c.core_created_by_alias > ' ' THEN c.core_created_by_alias ELSE ua.name END AS author");
		$query->select("ua.email AS author_email");

		$query->join('LEFT', '#__users AS ua ON ua.id = c.core_created_user_id');

		$query->where('m.tag_id IN (' . $tagId . ')');

		// Optionally filter on language
		if (empty($language))
		{
			$language = $languageFilter;
		}

		if ($language != 'all')
		{
			if ($language == 'current_language')
			{
				$language = JHelperContent::getCurrentLanguage();
			}
			$query->where($db->qn('core_language') . ' IN (' . $db->q($language) . ', ' . $db->q('*') . ')');
		}

		$contentTypes = new JTags;

		// Get the type data, limited to types in the request if there are any specified.
		$typesarray = $contentTypes->getTypes('assocList', $typesr, false);

		$typeAliases = '';

		foreach ($typesarray as $type)
		{
			$typeAliases .= "'" . $type['type_alias'] . "'" . ',';
		}

		$typeAliases = rtrim($typeAliases, ',');
		$query->where('m.type_alias IN (' . $typeAliases . ')');

		$groups	= implode(',', $user->getAuthorisedViewLevels());
		$query->where('c.core_access IN ('.$groups.')');
		$query->group('m.type_alias, m.content_item_id, m.core_content_id');

		// Use HAVING if matching all tags and we are matching more than one tag.
		if ($ntagsr > 1  && $anyOrAll != 1 && $includeChildren != 1)
		{
			// The number of results should equal the number of tags requested.
			$query->having("COUNT('m.tag_id') = " . $ntagsr);
		}

		// Set up the order by using the option chosen
		if ($orderByOption == 'match_count')
		{
			$orderBy = 'COUNT(m.tag_id)';
		}
		else
		{
			$orderBy = 'MAX(c.core_' . $orderByOption . ')';
		}

		$query->order($orderBy . ' ' . $orderDir);

		return $query;
	}

	/**
	 * Returns content name from a tag map record as an array
	 *
	 * @param   string  $typeAlias  The tag item name to explode.
	 *
	 * @return  array   The exploded type alias. If name doe not exist an empty array is returned.
	 *
	 * @since   3.1
	 */
	public function explodeTypeAlias($typeAlias)
	{
		return $explodedTypeAlias = explode('.', $typeAlias);
	}

	/**
	 * Returns the component for a tag map record
	 *
	 * @param   string  $typeAlias          The tag item name.
	 * @param   array   $explodedTypeAlias  Exploded alias if it exists
	 *
	 * @return  string  The content type title for the item.
	 *
	 * @since   3.1
	 */
	public function getTypeName($typeAlias, $explodedTypeAlias = null)
	{
		if (!isset($explodedTypeAlias))
		{
			$this->explodedTypeAlias = $this->explodeTypeAlias($typeAlias);
		}

		return $this->explodedTypeAlias[0];
	}

	/**
	 * Returns the url segment for a tag map record.
	 *
	 * @param   string   $typeAlias          The tag item name.
	 * @param   integer  $id                 Id of the item
	 * @param   array    $explodedTypeAlias  Exploded alias if it exists
	 *
	 * @return  string  The url string e.g. index.php?option=com_content&vew=article&id=3.
	 *
	 * @since   3.1
	 */
	public function getContentItemUrl($typeAlias, $id, $explodedTypeAlias = null)
	{
		if (!isset($explodedTypeAlias))
		{
			$explodedTypeAlias = self::explodedTypeAlias($typeAlias);
		}

		$this->url = 'index.php?option=' . $explodedTypeAlias[0] . '&view=' . $explodedTypeAlias[1] . '&id=' . $id;

		return $this->url;
	}

	/**
	 * Returns the url segment for a tag map record.
	 *
	 * @param   string   $typeAlias          Unknown
	 * @param   integer  $id                 The item ID
	 * @param   string   $explodedTypeAlias  The tag item name.
	 *
	 * @return  string  The url string e.g. index.php?option=com_content&vew=article&id=3.
	 *
	 * @since   3.1
	 */
	public function getTagUrl($typeAlias, $id, $explodedTypeAlias = null)
	{
		if (!isset($explodedTypeAlias))
		{
			$explodedTypeAlias = self::explodeTypeAlias($typeAlias);
		}

		$this->url = 'index.php&option=com_tags&view=tag&id=' . $id;

		return $this->url;
	}

	/**
	 * Method to get the table name for a type alias.
	 *
	 * @param   string  $tagItemAlias  A type alias.
	 *
	 * @return  string  Name of the table for a type
	 *
	 * @since   3.1
	 */
	public function getTableName($tagItemAlias)
	{
		// Initialize some variables.
		$db = JFactory::getDbo();
		$query = $db->getQuery(true);

		$query->select($db->quoteName('table'));
		$query->from($db->quoteName('#__content_types'));
		$query->where($db->quoteName('type_alias') . ' = ' . $db->quote($tagItemAlias));
		$db->setQuery($query);
		$this->table = $db->loadResult();

		return $this->table;
	}

	/**
	 * Method to get the type id for a type alias.
	 *
	 * @param   string  $typeAlias  A type alias.
	 *
	 * @return  string  Name of the table for a type
	 *
	 * @since   3.1
	 */
	public function getTypeId($typeAlias)
	{
		// Initialize some variables.
		$db = JFactory::getDbo();
		$query = $db->getQuery(true);

		$query->select($db->quoteName('type_id'));
		$query->from($db->quoteName('#__content_types'));
		$query->where($db->quoteName('type_alias') . ' = ' . $db->quote($typeAlias));
		$db->setQuery($query);
		$this->type_id = $db->loadResult();

		return $this->type_id;
	}

	/**
	 * Method to get a list of types with associated data.
	 *
	 * @param   string   $arrayType    Optionally specify that the returned list consist of objects, associative arrays, or arrays.
	 *                                 Options are: rowList, assocList, and objectList
	 * @param   array    $selectTypes  Optional array of type ids to limit the results to. Often from a request.
	 * @param   boolean  $useAlias     If true, the alias is used to match, if false the type_id is used.
	 *
	 * @return  array   Array of of types
	 *
	 * @since   3.1
	 */
	public static function getTypes($arrayType = 'objectList', $selectTypes = null, $useAlias = true)
	{
		// Initialize some variables.
		$db = JFactory::getDbo();
		$query = $db->getQuery(true);
		$query->select('*');

		if (!empty($selectTypes))
		{
			if (is_array($selectTypes))
			{
				$selectTypes = implode(',', $selectTypes);
			}
			if ($useAlias)
			{
				$query->where($db->qn('type_alias') . ' IN (' . $query->q($selectTypes) . ')');
			}
			else
			{
				$query->where($db->qn('type_id') . ' IN (' . $selectTypes . ')');
			}
		}

		$query->from($db->quoteName('#__content_types'));

		$db->setQuery($query);

		if (empty($arrayType) || $arrayType == 'objectList')
		{
			$types = $db->loadObjectList();
		}
		elseif ($arrayType == 'assocList')
		{
			$types = $db->loadAssocList();
		}
		else
		{
			$types = $db->loadRowList();
		}

		return $types;
	}

	/**
	 * Method to delete all instances of a tag from the mapping table. Generally used when a tag is deleted.
	 *
	 * @param   integer  $tag_id  The tag_id (primary key) for the deleted tag.
	 *
	 * @return  void
	 *
	 * @since   3.1
	 */
	public function tagDeleteInstances($tag_id)
	{
		// Delete the old tag maps.
		$db = JFactory::getDbo();
		$query = $db->getQuery(true);
		$query->delete();
		$query->from($db->quoteName('#__contentitem_tag_map'));
		$query->where($db->quoteName('tag_id') . ' = ' . (int) $tag_id);
		$db->setQuery($query);
		$db->execute();
	}

	/**
	 * Function to search tags
	 *
	 * @param   array  $filters  Filter to apply to the search
	 *
	 * @return  array
	 */
	public static function searchTags($filters = array())
	{
		$results = array();
		$db  = JFactory::getDbo();

		$query	= $db->getQuery(true);

		$query->select('a.id AS value')
			->select($query->concatenate(array('a.path', 'a.title'), ':') . ' AS text')
			->from('#__tags AS a')
			->join('LEFT', $db->quoteName('#__tags', 'b') . ' ON a.lft > b.lft AND a.rgt < b.rgt');

		// Filter language
		if (!empty($filters['flanguage']))
		{
			$query->where('a.language IN (' . $db->quote($filters['flanguage']) . ',' . $db->quote('*') . ') ');
		}

		// Do not return root
		$query->where($db->quoteName('a.alias') . ' <> ' . $db->quote('root'));

		// Search in title or path
		if (!empty($filters['like']))
		{
			$query->where(
				'(' . $db->quoteName('a.title') . ' LIKE ' . $db->quote('%' . $filters['like'] . '%')
				. ' OR ' . $db->quoteName('a.path') . ' LIKE ' . $db->quote('%' . $filters['like'] . '%') . ')'
			);
		}

		// Filter title
		if (!empty($filters['title']))
		{
			$query->where($db->quoteName('a.title') . '=' . $db->quote($filters['title']));
		}

		// Filter on the published state
		if (is_numeric($filters['published']))
		{
			$query->where('a.published = ' . (int) $filters['published']);
		}

		// Filter by parent_id
		if (!empty($filters['parent_id']))
		{
			JTable::addIncludePath(JPATH_ADMINISTRATOR . '/components/com_tags/tables');
			$tagTable = JTable::getInstance('Tag', 'TagsTable');

			if ($children = $tagTable->getTree($filters['parent_id']))
			{
				foreach ($children as $child)
				{
					$childrenIds[] = $child->id;
				}

				$query->where('a.id IN (' . implode(',', $childrenIds) . ')');
			}
		}

		$query->group('a.id, a.title, a.level, a.lft, a.rgt, a.parent_id, a.published');
		$query->order('a.lft ASC');

		// Get the options.
		$db->setQuery($query);

		try
		{
			$results = $db->loadObjectList();
		}
		catch (RuntimeException $e)
		{
			return false;
		}

		return $results;
	}
	 /**
	 * Method to get an array of tag ids for the current tag and its children
	 *
	 * @param   integer  $id             An optional ID
	 * @param   array    &$tagTreeArray
	 *
	 * @return  mixed
	 *
	 * @since   3.1
	 */
	public function getTagTreeArray($id, &$tagTreeArray = array())
	{
		// Get a level row instance.
		$table = JTable::getInstance('Tag', 'TagsTable');

		if ($table->isLeaf($id))
		{
			$tagTreeArray[] .= $id;
			return $tagTreeArray;
		}
		$tagTree = $table->getTree($id);

		// Attempt to load the tree
		if ($tagTree)
		{
			foreach ($tagTree as $tag)
			{
				$tagTreeArray[] = $tag->id;
			}
			return $tagTreeArray;
		}
	}
}
