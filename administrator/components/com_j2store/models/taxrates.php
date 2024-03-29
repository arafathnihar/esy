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

// No direct access to this file
defined('_JEXEC') or die('Restricted access');

// import Joomla view library
jimport('joomla.application.component.modellist');

class J2StoreModelTaxRates extends JModelList {


	protected function populateState($ordering = null, $direction = null)
	{
		// Initialise variables.
		$app = JFactory::getApplication('administrator');

		// Load the filter state.
		$search = $app->getUserStateFromRequest($this->context.'.filter.search', 'filter_search');
		$this->setState('filter.search', $search);
		$published = $app->getUserStateFromRequest($this->context.'.filter.state', 'filter_published', '', 'string');
		$this->setState('filter.state', $published);
		$type = $app->getUserStateFromRequest($this->context.'.filter.geozone_type', 'filter_geozone_type', '', 'string');
		$this->setState('filter.geozone_type', $type);
		$geozone = $app->getUserStateFromRequest($this->context.'.filter.geozone_options', 'filter_geozone_options', '', 'string');
		$this->setState('filter.geozone_options', $geozone);


		// Load the parameters.
		$params = JComponentHelper::getParams('com_j2store');
		$this->setState('params', $params);

		// List state information.
		parent::populateState('a.taxrate_id', 'asc');
	}


	protected function getStoreId($id = '')
	{
		// Compile the store id.
		$id.= ':' . $this->getState('filter.search');
		$id.= ':' . $this->getState('filter.state');
		return parent::getStoreId($id);
	}

	public function getGeoZones(){
		// Create a new query object.
		$db		= $this->getDbo();
		$query	= $db->getQuery(true);
		$query->select('a.geozone_id,a.geozone_name');
		$query->from('#__j2store_geozones AS a');
		$query->where('state = 1');
		$query->order('a.geozone_name');
		$db->setQuery($query);
		return $db->loadObjectList();
	}

	protected function getListQuery()
	{
		// Create a new query object.
		$db		= $this->getDbo();
		$query	= $db->getQuery(true);
		$query->select(
				$this->getState(
						'list.select',
						'a.taxrate_id, a.taxrate_name,a.tax_percent,a.geozone_id,a.state,g.geozone_name'
				)
		);

		$query->from('#__j2store_taxrates AS a');

		$query->join('LEFT', '#__j2store_geozones AS g ON g.geozone_id =a.geozone_id ');

		// Filter by published state
		$published = $this->getState('filter.state');
		if (is_numeric($published)) {
			$query->where('a.state = '.(int) $published);
		} else if ($published === '') {
			$query->where('(a.state IN (0, 1))');
		}
		// Filter by search in title
		$search = $this->getState('filter.search');
		if (!empty($search)) {
			if (stripos($search, 'id:') === 0) {
				$query->where('a.taxrate_id = '.(int) substr($search, 3));
			} else {
				$search = $db->Quote('%'.$db->escape($search, true).'%');
				$query->where('(a.taxrate_name LIKE '.$search.
						' OR a.taxrate_code LIKE '.$search.' OR a.geozone_id LIKE '.$search.')');
			}
		}

		// Filter by Country.
		$geozone = $this->getState('filter.geozone_options');
		if ($geozone) {
			$query->where('a.geozone_id = '.$db->quote($geozone));
		}
		// Add the list ordering clause.
		$orderCol	= $this->state->get('list.ordering');
		$orderDirn	= $this->state->get('list.direction');

		if($orderCol == 'a.taxrate_id' ) {
			$orderCol = 'a.taxrate_id '.$orderDirn.', a.taxrate_id';
		} else {
			$orderCol = 'a.taxrate_id '.$orderDirn.', a.taxrate_id';
		}

		$query->order($db->escape($orderCol.' '.$orderDirn));

		//echo nl2br(str_replace('#__','jos_',$query));
		return $query;
	}
}
