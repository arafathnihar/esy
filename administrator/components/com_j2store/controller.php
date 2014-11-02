<?php

// controller

// No direct access to this file
defined('_JEXEC') or die('Restricted access');
jimport('joomla.application.component.controller');

class J2StoreController extends J2StoreController
{
	/**
	 * display task
	 *
	 * @return void
	 */
	function display($cachable = false, $urlparams = array())
	{
		// set default view if not set
		$app = JFactory::getApplication();
		$app->input->set('view', $app->input->getWord('view', 'cpanel'));
		// call parent behavior
		parent::display($cachable, $urlparams);
		return $this;
	}
}
