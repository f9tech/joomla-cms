<?php
/**
 * @package     Joomla.Site
 * @subpackage  com_services
 *
 * @copyright   Copyright (C) 2005 - 2013 Open Source Matters, Inc. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

// No direct access
defined('_JEXEC') or die('Restricted access');

// Sessions
jimport('joomla.session.session');

// Load classes
JLoader::registerPrefix('Services', JPATH_COMPONENT);

// Tell the browser not to cache this page.
JResponse::setHeader('Expires', 'Mon, 26 Jul 1997 05:00:00 GMT', true);

// Application
$app = JFactory::getApplication();

if ($controllerTask = $app->input->get('controller'))
{
	// Checking for new MVC controller
	$tasks = explode('.', $controllerTask);
}
else
{
	// Checking for old MVC task
	$task = $app->input->get('task');
	$tasks = explode('.', $task);
}

// Get the controller name
if (empty($tasks[1]))
{
	$activity = 'display';
}
elseif ($tasks[1] == 'apply')
{
	$activity = 'save';
}
else
{
	$activity = $tasks[1];
}

// Create the controller
if ($tasks[0] == 'config')
{
	// For Config
	$classname  = 'ServicesControllerConfig' . ucfirst($activity);
}
elseif ($tasks[0] == 'templates')
{
	// For Templates
	$classname  = 'ServicesControllerTemplates' . ucfirst($activity);
}
else
{
	$app->enqueueMessage(JText::_('COM_SERVICES_ERROR_CONTROLLER_NOT_FOUND'), 'error');

	return;

}

$controller = new $classname;

// Perform the Request task
$controller->execute();
