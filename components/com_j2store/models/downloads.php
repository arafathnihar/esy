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



/** ensure this file is being included by a parent file */
defined('_JEXEC') or die('Restricted access');

jimport('joomla.application.component.modellist');
jimport('joomla.filesystem.file');
/**
 * Methods supporting a list of j2store records.
 *
 * @package		Joomla.Administrator
 * @subpackage	com_j2store
 * @since 2.5
*/

class J2StoreModelDownloads extends JModelList
{
	/**
	 * The category context (allows other extensions to derived from this model).
	 *
	 * @var		string
	 */

	public $_context = 'com_j2store.categories';
	protected $_extension = 'com_j2store';

	private $_parent = null;
	private $_items = null;

	protected function populateState($ordering = null, $direction = null)
	{
		// Initialise variables.
		$app = JFactory::getApplication('administrator');

		// Get the parent id if defined.
		$parentId = JRequest::getInt('parent_id');
		$this->setState('filter.parentId', $parentId);

		// Load the filter state.
		$search = $app->getUserStateFromRequest($this->context.'.filter.search', 'filter_search');
		$this->setState('filter.search', $search);

		// Load the parameters.
		$params = JComponentHelper::getParams('com_j2store');
		$this->setState('params', $params);

		// List state information.
		parent::populateState('a.title', 'asc');
	}

	public function getItems()
	{
		/*if (!count($this->_items)) {
		 $this->_items = parent::getItems();
		return $this->_items;
		}*/

		$db		= $this->getDbo();
		$query	= $db->getQuery(true);
		$user =JFactory::getUser();

		$query->select('o.order_id,o.order_state_id');
		$query->from('#__j2store_orders AS o');
		$query->where('o.order_state_id=1 AND o.user_id='.$db->Quote($user->id));
		$db->setQuery($query);
		$orders = $db->loadObjectList();

		//now we have confirmed orders. Based on these orders, we have to extract items for each order
		$orderfiles = array();
		foreach($orders as $order) {

			$query	= $db->getQuery(true);

			$query->select('oi.orderitem_id,oi.order_id,oi.product_id,oi.orderitem_name');
			$query->from('#__j2store_orderitems AS oi');
			$query->where('oi.order_id='.$db->Quote($order->order_id));

			$query->select('of.orderfile_id,of.productfile_id,of.limit_count,of.user_id,of.orderitem_id');
			$query->join('LEFT', '#__j2store_orderfiles AS of ON of.orderitem_id=oi.orderitem_id');
			$query->where('of.user_id='.$db->Quote($user->id));
			$query->select('pf.product_file_display_name,
					pf.product_file_save_name,
					pf.download_limit');
				
			$query->join('LEFT', '#__j2store_productfiles AS pf ON pf.productfile_id=of.productfile_id');
			$query->where('pf.state=1 AND pf.purchase_required=1');
			$query->where('(of.limit_count < pf.download_limit OR pf.download_limit=-1)');
			//echo $query;
			$db->setQuery($query);
			$orderfiles[] = $db->loadObjectList();
		}
		return $orderfiles;
	}

	/**
	 * Method to get a store id based on model configuration state.
	 *
	 * This is necessary because the model is used by the component and
	 * different modules that might need different sets of data or different
	 * ordering requirements.
	 *
	 * @param	string		$id	A prefix for the store id.
	 * @return	string		A store id.
	 * @since	1.6
	 */
	protected function getStoreId($id = '')
	{
		// Compile the store id.
		$id.= ':' . $this->getState('filter.search');
		return parent::getStoreId($id);
	}

	/**
	 * Build an SQL query to load the list data.
	 *
	 * @return	JDatabaseQuery
	 * @since	1.6
	 */
	/*
	 //protected
	function getProductIds(){

	$db		= $this->getDbo();
	$query	= $db->getQuery(true);
	//$q	= $db->getQuery(true);
	$user = JFactory::getUser();

	$query->select('oi.product_id');
	$query->from('#__j2store_orders AS o');
	$query->where('o.user_id = '.$user->id);
	$conf = 'Confirmed';
	$query->where('o.order_state = "'.$conf.'"');

	$query->join('LEFT', '`#__j2store_orderitems` AS oi ON oi.order_id = o.order_id');
	//$query->join('LEFT', '`#__j2store_productfiles` AS pf ON pf.product_id = oi.product_id');
	//echo nl2br(str_replace('#__','rre1u_',$query));

	$db->setQuery($query);
	//$row = $db->loadObjectList();
	$row = $db->loadResultArray();

	return $row;
	}
	*/

	function getFreeFiles($product_id){

		$db		= JFactory::getDBO();
		$query	= $db->getQuery(true);

		$query->select(
				'c.title as product_title,'.
				'a.product_id, a.productfile_id, a.product_file_display_name as display_name');

		$query->from('#__j2store_productfiles as a');
		$query->where('a.purchase_required = 0 AND a.state=1 AND a.product_id='.(int)$product_id);
		$query->join('LEFT', '`#__content` AS c ON c.id = a.product_id');
		$query->where('c.id='.(int)$product_id);
		$db->setQuery($query);
		$files = $db->loadObjectList();
		return $files;
	}

	protected function getListQuery()
	{
		// Create a new query object.
		$app = JFactory::getApplication();
		$db		= $this->getDbo();
		$query	= $db->getQuery(true);
		$user 	= JFactory::getUser();
		$params = JComponentHelper::getParams('com_j2store');
		//$prod_ids = $this->getProductIds();

		// Select the required fields from the table.
		$query->select(
				$this->getState(
						'list.select',
						'of.orderfile_id, of.limit_count,'.
						//'o.product_id as pid,o.orderitem_id,'.
						'c.title as product_title,'.
						'a.product_id, a.productfile_id, a.product_file_display_name as display_name,'.
						' a.product_file_save_name as savename, a.state, a.purchase_required, a.download_limit'
				)
		);
		$query->from('`#__j2store_orderfiles` AS of');

		//$query->from('`#__j2store_productfiles` AS a');
		$query->where('of.user_id='.$user->id);
		$query->join('LEFT', '`#__j2store_productfiles` AS a ON of.productfile_id = a.productfile_id');
		$query->where('a.state=1 AND (of.limit_count<a.download_limit OR a.download_limit=-1)');
		$query->join('LEFT', '`#__content` AS c ON c.id = a.product_id');

		//$query->join('LEFT', '`#__j2store_orderitems` AS o ON o.product_id = a.product_id');


		/*		// Filter by search in title
		 $search = $this->getState('filter.search');
		if (!empty($search)) {
		if (stripos($search, 'id:') === 0) {
		$query->where('a.product_id = '.(int) substr($search, 3));
		} else {
		$search = $db->Quote('%'.$db->escape($search, true).'%');
		$query->where('(a.title LIKE '.$search.' OR a.alias LIKE '.$search.')');
		}
		}
		*/
		//echo nl2br(str_replace('#__','jos_',$query));
		return $query;
	}
}
