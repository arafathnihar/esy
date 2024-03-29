<?php
/*------------------------------------------------------------------------
 # com_j2store - J2Store
# ------------------------------------------------------------------------
# author    Sasi varna kumar - Weblogicx India http://www.weblogicxindia.com
# copyright Copyright (C) 2012 Weblogicxindia.com. All Rights Reserved.
# @license - http://www.gnu.org/licenses/gpl-2.0.html GNU/GPL
# Websites: http://j2store.org
# Technical Support:  Forum - http://j2store.org/forum/index.html
-------------------------------------------------------------------------*/


// No direct access.
defined('_JEXEC') or die;

jimport('joomla.application.component.modeladmin');

/**
 * Jsecommerce model.
 *
 * @package		Joomla.Site
 * @subpackage	com_jsecommerce
 * @since 2.5
*/
class J2StoreModelCountry extends JModelAdmin
{
	protected $text_prefix = 'COM_J2STORE';

	protected $view_list = 'countries';

	public function getForm($data = array(), $loadData = true)
	{
		// Initialise variables.
		$app	= JFactory::getApplication();
		// Get the form.
		$form = $this->loadForm('com_j2store.country', 'country', array('control' => 'jform', 'load_data' => $loadData));
		if (empty($form)) {
			return false;
		}

		return $form;
	}



	public function getTable($type = 'country', $prefix = 'Table', $config = array())
	{

		return JTable::getInstance($type, $prefix, $config);
	}


	protected function loadFormData()
	{
		// Check the session for previously entered form data.
		$data = JFactory::getApplication()->getUserState('com_j2store.edit.country.data', array());

		if (empty($data)) {
			$data = $this->getItem();

			// Prime some default values.
			if ($this->getState('country.id') == 0) {
				$app = JFactory::getApplication();
				// set the id here if it does not work.
				//$data->set('pro_id', JRequest::getInt('pro_id', $app->getUserState('com_j2store.profile.filter.category_id')));
			}
		}

		return $data;
	}

	public function getItem($pk = null)
	{
		if ($item = parent::getItem($pk)) {
			// Convert the params field to an array.
		}

		return $item;
	}

	protected function prepareTable($table)
	{
		jimport('joomla.filter.output');
		$date = JFactory::getDate();
		$user = JFactory::getUser();

		//For Future usage
		if (empty($table->country_id)) {
			// Set the values

		}
		else {
			// Set the values
				
		}
	}

}
