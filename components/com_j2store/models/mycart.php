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

// no direct access
defined( '_JEXEC' ) or die( 'Restricted access' );
jimport( 'joomla.filter.filterinput' );
jimport('joomla.application.component.model');

JTable::addIncludePath(JPATH_COMPONENT_ADMINISTRATOR.DS.'tables');
JLoader::register('J2StoreModel',  JPATH_ADMINISTRATOR.'/components/com_j2store/models/model.php');
require_once (JPATH_ADMINISTRATOR.'/components/com_j2store/library/inventory.php');
class J2StoreModelMyCart extends J2StoreModel {

	private $_product_id;
	private $_data = array();


	function __construct($config = array())
	{
		parent::__construct($config);
	}

	function getDataNew()
	{
		require_once (JPATH_SITE.'/components/com_j2store/helpers/cart.php');

		$session = JFactory::getSession();

		// Lets load the content if it doesn't already exist
		if (empty($this->_data) && count($session->get('j2store_cart')))
		{

			foreach ($session->get('j2store_cart') as $key => $quantity) {

				$product = explode(':', $key);
				$product_id = $product[0];
				$stock = true;

				// Options
				if (isset($product[1])) {
					$options = unserialize(base64_decode($product[1]));
				} else {
					$options = array();
				}

				//now get product details
				$product_info = J2StoreHelperCart::getItemInfo($product_id);

				//now get product options
				if($product_info) {
					$option_price = 0;
					$option_weight = 0;
					$option_data = array();

					foreach ($options as $product_option_id => $option_value) {

						$product_option = $this->getCartProductOptions($product_option_id , $product_id);

						if ($product_option) {
							if ($product_option->type == 'select' || $product_option->type == 'radio') {

								//ok now get product option values
								$product_option_value = $this->getCartProductOptionValues($product_option->product_option_id, $option_value );

								if ($product_option_value) {

									//price
									if ($product_option_value->product_optionvalue_prefix == '+') {
										$option_price += $product_option_value->product_optionvalue_price;
									} elseif ($product_option_value->product_optionvalue_prefix == '-') {
										$option_price -= $product_option_value->product_optionvalue_price;
									}

									//options weight
									if ($product_option_value->product_optionvalue_weight_prefix == '+') {
										$option_weight += $product_option_value->product_optionvalue_weight;
									} elseif ($product_option_value->product_optionvalue_weight_prefix == '-') {
										$option_weight -= $product_option_value->product_optionvalue_weight;
									}


									$option_data[] = array(
											'product_option_id'       => $product_option_id,
											'product_optionvalue_id' => $option_value,
											'option_id'               => $product_option->option_id,
											'optionvalue_id'         => $product_option_value->optionvalue_id,
											'name'                    => $product_option->option_name,
											'option_value'            => $product_option_value->optionvalue_name,
											'type'                    => $product_option->type,
											'price'                   => $product_option_value->product_optionvalue_price,
											'price_prefix'            => $product_option_value->product_optionvalue_prefix,
											'weight'                   => $product_option_value->product_optionvalue_weight,
											'weight_prefix'            => $product_option_value->product_optionvalue_weight_prefix
									);
								}
							}
						}
					}

					//get the product price

					//base price
					$price = $product_info->price;

					//we may have special price or discounts. so check
					$price_override = J2StorePrices::getPrice($product_info->product_id, $quantity);

					if(isset($price_override) && !empty($price_override)) {
						$price = $price_override->product_price;
					}

					$this->_data[$key] = array(
							'key'             => $key,
							'product_id'      =>  $product_info->product_id,
							'name'            =>  $product_info->product_name,
							'model'           =>  $product_info->product_sku,
							'option'          => $option_data,
							'option_price'    => $option_price,
							'quantity'        => $quantity,
							'tax_profile_id'  => $product_info->tax_profile_id,
							'shipping' 		  => $product_info->item_shipping,
							'price'           => ($price + $option_price),
							'total'           => ($price + $option_price) * $quantity,
							'weight'          => ($product_info->item_weight + $option_weight),
							'weight_total'    => ($product_info->item_weight + $option_weight) * $quantity,
							'option_weight'   => ($option_weight * $quantity),
							'weight_class_id' => $product_info->item_weight_class_id,
							'length'          => $product_info->item_length,
							'width'           => $product_info->item_width,
							'height'          => $product_info->item_height,
							'length_class_id' => $product_info->item_length_class_id

					);

				} // end of product info if
				else {
					$this->remove($key);
				}
			}
		}
		return $this->_data;
	}

	 public function getShippingIsEnabled()
    {
	   	$model = JModelLegacy::getInstance( 'MyCart', 'J2StoreModel');
		$list = $model->getDataNew();

    	// If no item in the list, return false
        if ( empty( $list ) )
        {
          	return false;
        }

        require_once(JPATH_ADMINISTRATOR.'/components/com_j2store/library/j2item.php');
        $product_helper = new J2StoreItem();
        foreach ($list as $item)
        {
           	$shipping = $product_helper->isShippingEnabled($item['product_id']);
        	if ($shipping)
        	{
        	    return true;
        	}
        }

        return false;
    }

    function getProductOptions($product_id) {

    	//first get the product options
    	$db = JFactory::getDbo();
    	$product_option_data = array();
    	$query = $db->getQuery(true);
    	$query->select('po.*');
    	$query->from('#__j2store_product_options AS po');
    	$query->where('po.product_id='.$product_id);

    	//join the options table to get the name
    	$query->select('o.option_name, o.type');
    	$query->join('LEFT', '#__j2store_options AS o ON po.option_id=o.option_id');
    	$query->where('o.state=1');
    	$query->order('po.product_option_id ASC');

    	$db->setQuery($query);
    	$product_options = $db->loadObjectList();
		//now prepare to get the product option values
    	foreach($product_options as $product_option) {

    		//if multiple choices available, then we got to get them
    		if ($product_option->type == 'select' || $product_option->type == 'radio' || $product_option->type == 'checkbox') {

    			$product_option_value_data = array();

    			$product_option_values = $this->getProductOptionValues($product_option->product_option_id, $product_option->product_id);

    			foreach ($product_option_values as $product_option_value) {
    				$product_option_value_data[] = array(
    						'product_optionvalue_id' 		=> $product_option_value->product_optionvalue_id,
    						'optionvalue_id'         		=> $product_option_value->optionvalue_id,
    						'optionvalue_name'       		=> $product_option_value->optionvalue_name,
    						'product_optionvalue_price' 	=> $product_option_value->product_optionvalue_price,
    						'product_optionvalue_prefix'	=> $product_option_value->product_optionvalue_prefix,
    						'product_optionvalue_weight' 	=> $product_option_value->product_optionvalue_weight,
    						'product_optionvalue_weight_prefix'	=> $product_option_value->product_optionvalue_weight_prefix
    				);
    			}

    			$product_option_data[] = array(
    					'product_option_id' => $product_option->product_option_id,
    					'option_id'         => $product_option->option_id,
    					'option_name'		=> $product_option->option_name,
    					'type'              => $product_option->type,
    					'optionvalue'       => $product_option_value_data,
    					'required'          => $product_option->required
    			);

    		} else {

    			//if no option values are present, then
    			$product_option_data[] = array(
    					'product_option_id' => $product_option->product_option_id,
    					'option_id'         => $product_option->option_id,
    					'option_name'		=> $product_option->option_name,
    					'type'              => $product_option->type,
    					'optionvalue'       => '',
    					'required'          => $product_option->required
    			);
    		} //endif
    	} //end product option foreach

    	return $product_option_data;
    }

    function getProductOptionValues($product_option_id, $product_id) {

    	//first get the product options
    	$db = JFactory::getDbo();
    	$query = $db->getQuery(true);
    	$query->select('pov.*');
    	$query->from('#__j2store_product_optionvalues AS pov');
    	$query->where('pov.product_id='.$product_id);
    	$query->where('pov.product_option_id='.$product_option_id);

    	//join the optionvalues table to get the name
    	$query->select('ov.optionvalue_id, ov.optionvalue_name');
    	$query->join('LEFT', '#__j2store_optionvalues AS ov ON pov.optionvalue_id=ov.optionvalue_id');
    	$query->order('pov.ordering ASC');

    	$db->setQuery($query);
    	$product_option_values = $db->loadObjectList();
    	return $product_option_values;
    }

    function getCartProductOptions($product_option_id , $product_id) {

    	$db = JFactory::getDbo();
    	$query = $db->getQuery(true);
    	$query->select('po.*');
    	$query->from('#__j2store_product_options AS po');
    	$query->where('po.product_option_id='.$product_option_id);
    	$query->where('po.product_id='.$product_id);

    	//join the options table to get the name
    	$query->select('o.option_name, o.type');
    	$query->join('LEFT', '#__j2store_options AS o ON po.option_id=o.option_id');
    	$query->order('o.ordering ASC');
    	$db->setQuery($query);

    	$product_option = $db->loadObject();
    	return $product_option;
    }

    function getCartProductOptionValues($product_option_id, $option_value ) {

    	//first get the product options
    	$db = JFactory::getDbo();
    	$query = $db->getQuery(true);
    	$query->select('pov.*');
    	$query->from('#__j2store_product_optionvalues AS pov');
    	$query->where('pov.product_optionvalue_id='.$option_value);
    	$query->where('pov.product_option_id='.$product_option_id);

    	//join the optionvalues table to get the name
    	$query->select('ov.optionvalue_id, ov.optionvalue_name');
    	$query->join('LEFT', '#__j2store_optionvalues AS ov ON pov.optionvalue_id=ov.optionvalue_id');
    	$query->order('pov.ordering ASC');

    	$db->setQuery($query);
    	$product_option_value = $db->loadObject();
    	return $product_option_value;
    }


    public function update($key, $qty) {
    	$cart = JFactory::getSession()->get('j2store_cart');
    	if ((int)$qty && ((int)$qty > 0)) {
    		$cart[$key] = (int)$qty;
    	} else {
    		$this->remove($key);
    	}
    	JFactory::getSession()->set('j2store_cart', $cart);
    	$this->_data = array();
    }


    public function remove($key) {
    	$cart = JFactory::getSession()->get('j2store_cart');

    	if (isset($cart[$key])) {
    		unset($cart[$key]);
    	}
    	JFactory::getSession()->set('j2store_cart', $cart);
    	$this->_data = array();
    }

    public function clear() {
    	JFactory::getSession()->set('j2store_cart', array());
    	$this->_data = array();
    }


    public function hasStock() {

    	$status = true;
    	$no_stock = array();
    	$products = $this->getDataNew();
    	foreach ($products as $product) {
    		$product_total = 0;

    		foreach ($products as $product_2) {
    			if ($product_2['product_id'] == $product['product_id']) {
    				$product_total += $product_2['quantity'];
    			}
    		}

    		if(!J2StoreInventory::validateStock($product['product_id'], $product_total)) {
    			$no_stock[]=$product['product_id'];
    		}

    	}

    	if(count($no_stock)) {
    		$status=false;
    	}

    	return $status;
    }

    public function hasProductStock($product_id, $qty=0, $isTotal=false) {

    	$params = JComponentHelper::getParams('com_j2store');
    	$status = true;

    	//check if backorder is enabled. If yes, then return true
    	if($params->get('allow_backorder', 0)) {
    		return $status;
    	}

    	$no_stock = array();

			$product_total = 0;

    		if($isTotal) {
				//are we checking total qty from update? then override product total
				$product_total = $qty;
			}else {
				$products = $this->getDataNew();
				foreach ($products as $product_2) {
					if ($product_2['product_id'] == $product_id) {
					$product_total += $product_2['quantity'];
				}
    		 }
				//are we adding more quantity? add it to total so that we know if we have adequate stock
				$product_total=$product_total+$qty;
			}

    		if(!J2StoreInventory::validateStock($product_id, $product_total)) {
    			$no_stock[]=$product_id;
    		}

    	if(count($no_stock)) {
    		$status=false;
    	}

    	return $status;
    }

}
