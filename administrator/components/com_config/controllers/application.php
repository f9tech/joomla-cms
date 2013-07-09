<?php
/**
 * @package     Joomla.Administrator
 * @subpackage  com_config
 *
 * @copyright   Copyright (C) 2005 - 2013 Open Source Matters, Inc. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

defined('_JEXEC') or die;

/**
 * Controller for global configuration
 *
 * @package     Joomla.Administrator
 * @subpackage  com_config
 * @since       1.5
 * @deprecated  3.2
 */
class ConfigControllerApplication extends JControllerLegacy
{
	/**
	 * Class Constructor
	 *
	 * @param   array  $config  An optional associative array of configuration settings.
	 *
	 * @return  void
	 *
	 * @since   1.5
	 * @deprecated  3.2
	 */
	public function __construct($config = array())
	{
		parent::__construct($config);

		// Map the apply task to the save method.
		$this->registerTask('apply', 'save');
	}

	/**
	 * Method to save the configuration.
	 *
	 * @return  bool  True on success, false on failure.
	 *
	 * @since   1.5
	 * @deprecated  3.2  Use ConfigControllerApplicationSave instead.
	 */
	public function save()
	{
		
		JLog::add('ConfigControllerApplication is deprecated. Use ConfigControllerApplicationSave instead.', JLog::WARNING, 'deprecated');
		JLoader::registerPrefix('Config', JPATH_ADMINISTRATOR . '/components/com_config');
		$controller = new ConfigControllerApplicationSave;

		return $controller->execute();		
	}

	/**
	 * Cancel operation
	 * @deprecated  3.2  Use ConfigControllerApplicationCancel instead.
	 */
	public function cancel()
	{

		JLog::add('ConfigControllerApplication is deprecated. Use ConfigControllerApplicationCancel instead.', JLog::WARNING, 'deprecated');
		JLoader::registerPrefix('Config', JPATH_ADMINISTRATOR . '/components/com_config');
		$controller = new ConfigControllerApplicationCancel;

		return $controller->execute();
	}

	public function refreshHelp()
	{
		
		JLog::add('ConfigControllerApplication is deprecated. Use ConfigControllerApplicationRefreshhelp instead.', JLog::WARNING, 'deprecated');
		JLoader::registerPrefix('Config', JPATH_ADMINISTRATOR . '/components/com_config');
		$controller = new ConfigControllerApplicationRefreshhelp;

		$controller->execute();
	}

	/**
	 * Method to remove the root property from the configuration.
	 *
	 * @return  bool  True on success, false on failure.
	 *
	 * @since   1.5
	 * @deprecated  3.2  Use ConfigControllerApplicationRemoveroot instead.
	 */
	public function removeroot()
	{

		JLog::add('ConfigControllerApplication is deprecated. Use ConfigControllerApplicationRemoveroot instead.', JLog::WARNING, 'deprecated');
		JLoader::registerPrefix('Config', JPATH_ADMINISTRATOR . '/components/com_config');
		$controller = new ConfigControllerApplicationRemoveroot;

		return $controller->execute();
	}
}
