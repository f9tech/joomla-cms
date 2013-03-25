<?php
/**
 * @package     Joomla.Site
 * @subpackage  mod_tags_popular
 *
 * @copyright   Copyright (C) 2005 - 2013 Open Source Matters, Inc. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

defined('_JEXEC') or die;

/**
 * Helper for mod_tags_popular
 *
 * @package     Joomla.Site
 * @subpackage  mod_tags_popular
 * @since       3.1
 */
abstract class ModTagsPopularHelper
{
	public static function getList($params)
	{
		$db        = JFactory::getDbo();
		$user      = JFactory::getUser();
		$groups    = implode(',', $user->getAuthorisedViewLevels());
		$timeframe = $params->get('timeframe', 'alltime');
		$maximum   = $params->get('maximum', 5);

		$query = $db->getQuery(true);

		$query->select(
			array(
				'MAX(' . $db->quoteName('tag_id') . ') AS tag_id',
				' COUNT(*) AS count', 'MAX(' . $db->quoteName('t.title') . ') AS title',
				'MAX(' .$db->quoteName('t.access') . ') AS access',
				'MAX(' .$db->quoteName('t.alias') . ') AS alias'
			)
		);
		$query->group($db->quoteName(array('tag_id', 'title', 'access', 'alias')));
		$query->from($db->quoteName('#__contentitem_tag_map'));
		$query->where($db->quoteName('t.access') . ' IN (' . $groups . ')');

		// Only return published tags
		$query->where($db->quoteName('t.published') . ' = 1 ');

		// Optionally filter on language
		$language = JComponentHelper::getParams('com_tags')->get('tag_list_language_filter', 'all');

		if ($language != 'all')
		{
			if ($language == 'current_language')
			{
				$language = JHelperContent::getCurrentLanguage();
			}
			$query->where($db->qn('t.language') . ' IN (' . $db->q($language) . ', ' . $db->q('*') . ')');
		}

		if ($timeframe != 'alltime')
		{
			$now = new JDate;
			$query->where($db->quoteName('tag_date') . ' > ' . $query->dateAdd($now->toSql('date'), '-1', strtoupper($timeframe)));
		}

		$query->join('INNER', $db->quoteName('#__tags', 't') . ' ON ' . $db->quoteName('tag_id') . ' = ' . $db->quoteName('t.id'));
		$query->order('count DESC');
		$db->setQuery($query, 0, $maximum);
		$results = $db->loadObjectList();

		return $results;
	}
}
