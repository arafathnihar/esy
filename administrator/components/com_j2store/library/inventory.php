<?php
/*------------------------------------------------------------------------
 # com_j2store - J2Store
# ------------------------------------------------------------------------
# author    Ramesh Elamathi - Weblogicx India http://www.weblogicxindia.com
# copyright Copyright (C) 2012 Weblogicxindia.com. All Rights Reserved.
# @license - http://www.gnu.org/licenses/gpl-2.0.html GNU/GPL
# Websites: http://j2store.org
# Technical Support:  Forum - http://j2store.org/forum/index.html
-------------------------------------------------------------------------*/

//class to manage inventory

// no direct access
defined('_JEXEC') or die('Restricted access');

class J2StoreInventory {

	public static function setInventory($orderpayment_id, $order_state_id) {

		//only reduce the inventory if the order is successful. 1==CONFIRMED.
		if($order_state_id == 1) {

			require_once(JPATH_SITE.'/components/com_j2store/models/orders.php');
			$model =  new J2StoreModelOrders();
			//lets set the id first
			$model->setId($orderpayment_id);

			$order = $model->getTable( 'orders' );
			$order->load( $model->getId() );
			$orderitems = $order->getItems();
			foreach($orderitems as $item) {

				J2StoreInventory::minusStock($item->product_id, $item->orderitem_quantity);

			}
		} else {
			return;
		}
		return;
	}

	protected function minusStock($product_id, $quantity) {
		$db = JFactory::getDbo();

		//first get stock and then minus
		$stock = J2StoreInventory::getStock($product_id);
		if($stock->result > 0){
			$query = $db->getQuery(true);
			$query->update('#__j2store_productquantities')->set('quantity=quantity-'.(int)$quantity);
			$query->where('product_id='.$db->quote($product_id));
			$db->setQuery($query);
			$db->query();
		}
	}

public static function validateStock($product_id, $qty=1) {

		$params = JComponentHelper::getParams('com_j2store');

		//if inventory is not enabled, return true
		if(!$params->get('enable_inventory', 0)) {
			return true;
		}

		$obj = J2StoreInventory::getStock($product_id);

		if(!$obj->error) {

			if($obj->result == -1) {
				return true;
			}

			if($obj->result < 1) {
				return false;
			}

			if($obj->result < $qty) {
				return false;
			}

			return true;

		} else {
			//no imput/record found for this item. So assume inventory is not managed
			return true;
		}

	}

	public static function getStock($product_id) {

		$db = JFactory::getDbo();
		$query = $db->getQuery(true);
		$query->select('quantity');
		$query->from('#__j2store_productquantities');
		$query->where('product_id='.$db->quote($product_id));
		$db->setQuery($query);
		$result = $db->loadResult();

		$var = new JObject();
		if(empty($result)) {
			$var->result = NULL;
			$var->error = true;
		} else {
			$var->result = $result;
			$var->error = false;
		}

		return $var;
	}

	public static function isAllowed($item) {

		$params = JComponentHelper::getParams('com_j2store');

		//set the result object
		$result = new JObject();
		$result->backorder = false;
		//we always want to allow users to buy. so initialise to 1.
		$result->can_allow = 1;

		return $result;

		//first check if inventory is enabled.

		if(!$params->get('enable_inventory', 0)) {
			//if not enabled, allow adding and return here
			$result->can_allow = 1;
			return $result;
		}

		//now, inventory seems enabled. So check stock.
		if($item->product_stock > 0) { //greater than zero, so product is available
			$result->can_allow = 1;

		}elseif($item->product_stock == -1 || is_null($item->product_stock)) { //if -1, then this is disabled. If empty, assume it's disabled
			$result->can_allow = 1;

		}elseif($item->product_stock < 1) { // less then 1, then stock level is set
			$result->can_allow = 0;
		}

		//if backorder is allowed, set it and override to allow adding
		if($params->get('enable_inventory', 0) && $params->get('allow_backorder', 0)) {
			$result->backorder = true;
		}

		return $result;

	}
}