<?php
/**
 * @package     Joomla.Administrator
 * @subpackage  com_finder
 *
 * @copyright   Copyright (C) 2005 - 2012 Open Source Matters, Inc. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE
 */

defined('_JEXEC') or die;

/**
 * Filters view class for Finder.
 *
 * @package     Joomla.Administrator
 * @subpackage  com_finder
 * @since       2.5
 */
class FinderViewFilters extends JViewLegacy
{
	/**
	 * Method to display the view.
	 *
	 * @param   string  $tpl  A template file to load. [optional]
	 *
	 * @return  mixed  A string if successful, otherwise a JError object.
	 *
	 * @since   2.5
	 */
	public function display($tpl = null)
	{
		// Load the view data.
		$this->items = $this->get('Items');
		$this->pagination = $this->get('Pagination');
		$this->total = $this->get('Total');
		$this->state = $this->get('State');

		// Check for errors.
		if (count($errors = $this->get('Errors')))
		{
			JError::raiseError(500, implode("\n", $errors));
			return false;
		}

		JHtml::addIncludePath(JPATH_COMPONENT . '/helpers/html');

		// Configure the toolbar.
		$this->addToolbar();

		parent::display($tpl);
	}

	/**
	 * Method to configure the toolbar for this view.
	 *
	 * @return  void
	 *
	 * @since   2.5
	 */
	protected function addToolbar()
	{
		$canDo = FinderHelper::getActions();

		JToolbarHelper::title(JText::_('COM_FINDER_FILTERS_TOOLBAR_TITLE'), 'finder');
		$toolbar = JToolBar::getInstance('toolbar');

		if ($canDo->get('core.create'))
		{
			JToolbarHelper::addNew('filter.add');
			JToolbarHelper::editList('filter.edit');
			JToolbarHelper::divider();
		}
		if ($canDo->get('core.edit.state'))
		{
			JToolbarHelper::publishList('filters.publish');
			JToolbarHelper::unpublishList('filters.unpublish');
			JToolbarHelper::divider();
		}
		if ($canDo->get('core.delete'))
		{
			JToolbarHelper::deleteList('', 'filters.delete');
			JToolbarHelper::divider();
		}
		if ($canDo->get('core.admin'))
		{
			JToolbarHelper::preferences('com_finder');
		}
		JToolbarHelper::divider();
		$toolbar->appendButton('Popup', 'stats', 'COM_FINDER_STATISTICS', 'index.php?option=com_finder&view=statistics&tmpl=component', 550, 500);
		JToolbarHelper::divider();
		JToolbarHelper::help('JHELP_COMPONENTS_FINDER_MANAGE_SEARCH_FILTERS');
	}
}
