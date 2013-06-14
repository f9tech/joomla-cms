<?php
/**
 * @package     Joomla.Site
 * @subpackage  com_services
 *
 * @copyright   Copyright (C) 2005 - 2013 Open Source Matters, Inc. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */
defined('_JEXEC') or die('Restricted access');

/**
 * Save Controller for global configuration
 *
 * @package     Joomla.Administrator
 * @subpackage  com_services
 * @since       3.2
*/
class ServicesControllerConfigSave extends JControllerBase
{
	/**
	 * Method to save global configuration.
	 *
	 * @return  bool	True on success.
	 *
	 * @since   3.2
	 */
	public function execute()
	{
		// Check for request forgeries.
		JSession::checkToken() or jexit(JText::_('JINVALID_TOKEN'));

		// Check if the user is authorized to do this.
		if (!JFactory::getUser()->authorise('core.admin'))
		{
			JFactory::getApplication()->redirect('index.php', JText::_('JERROR_ALERTNOAUTHOR'));

			return;
		}

		// Set FTP credentials, if given.
		JClientHelper::setCredentialsFromRequest('ftp');

		$app   = JFactory::getApplication();
		$model = new ServicesModelConfig;
		$form  = $model->getForm();
		$data  = $this->input->post->get('jform', array(), 'array');

		// Validate the posted data.
		$return = $model->validate($form, $data);

		// Check for validation errors.
		if ($return === false)
		{
			// Get the validation messages.
			$errors	= $model->getErrors();

			// Push up to three validation messages out to the user.
			for ($i = 0, $n = count($errors); $i < $n && $i < 3; $i++)
			{
				if ($errors[$i] instanceof Exception)
				{
					$app->enqueueMessage($errors[$i]->getMessage(), 'warning');
				}
				else
				{
					$app->enqueueMessage($errors[$i], 'warning');
				}
			}

			// Save the data in the session.
			$app->setUserState('com_services.config.global.data', $data);

			// Redirect back to the edit screen.
			$app->redirect(JRoute::_('index.php?option=com_services&controller=config', false));

			return false;
		}

		// Attempt to save the configuration.
		$data	= $return;

		$return = $model->save($data);

		// Check the return value.
		if ($return === false)
		{
			// Save the data in the session.
			$app->setUserState('com_services.config.global.data', $data);

			// Save failed, go back to the screen and display a notice.
			$message = JText::sprintf('JERROR_SAVE_FAILED', $model->getError());

			$app->redirect(JRoute::_('index.php?option=com_services&controller=config', false), $message, 'error');

			return false;
		}

		// Set the success message.
		$message = JText::_('COM_SERVICES_SAVE_SUCCESS');

		// Redirect back to com_services display
		$app->redirect(JRoute::_('index.php?option=com_services&controller=config', false), $message);

		return true;
	}
}
