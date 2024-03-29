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
defined('_JEXEC') or die('Restricted access');

jimport('joomla.application.component.controller');
JLoader::register( 'J2StoreHelperCart', JPATH_SITE.'/components/com_j2store/helpers/cart.php');
JTable::addIncludePath( JPATH_ADMINISTRATOR.'/components/com_j2store/tables' );
require_once (JPATH_COMPONENT_ADMINISTRATOR.'/library/tax.php');
class J2StoreControllerCheckout extends J2StoreController
{

	var $_order        = null;
//	var $defaultShippingMethod = null; // set in constructor
	var $initial_order_state   = 4;
	var $_cartitems = null;
	var $tax = null;
	var $session = null;
	var $option = 'com_j2store';
	var $params = null;

	function __construct()
	{
		parent::__construct();
		header('Content-Type: text/html; charset=utf-8');
		$this->params = JComponentHelper::getParams($this->option);
	//	$this->defaultShippingMethod = J2StoreHelperCart::getStoreAddress()->config_shipping_default;
		// create the order object
		$this->_order = JTable::getInstance('Orders', 'Table');
		//initialise tax class
		$this->tax = new J2StoreTax();
		//initialise the session object
		$this->session = JFactory::getSession();
		//language
		$language = JFactory::getLanguage();
		/* Set the base directory for the language */
		$base_dir = JPATH_SITE;
		/* Load the language. IMPORTANT Becase we use ajax to load cart */
		$language->load('com_j2store', $base_dir, $language->getTag(), true);
	}

	function display($cachable = false, $urlparams = array()) {
		$app = JFactory::getApplication();

		$values =  $app->input->getArray($_POST);
		$view = $this->getView( 'checkout', 'html' );
		$task = JRequest::getVar('task');
		$model		= $this->getModel('checkout');
		$cart_helper = new J2StoreHelperCart();
		$cart_model = $this->getModel('mycart');

		if (!$cart_helper->hasProducts() && $task != 'confirmPayment' )
		{
			$msg = JText::_('J2STORE_NO_ITEMS_IN_CART');
			$link = JRoute::_('index.php?option=com_j2store&view=mycart');
			$app->redirect($link, $msg);
		}

		//minimum order value check
		//prepare order
		$order= $this->_order;
		$order = $this->populateOrder(false);
		if(!$this->checkMinimumOrderValue($order)) {
			$msg = JText::_('J2STORE_ERROR_MINIMUM_ORDER_VALUE').J2StorePrices::number($this->params->get('global_minordervalue'));
			$link = JRoute::_('index.php?option=com_j2store&view=mycart');
			$app->redirect($link, $msg);
		}

		$user 		=	JFactory::getUser();

		$isLogged = 0;
		if($user->id) {
			$isLogged = 1;
		}
		$view->assign('logged',$isLogged);

		//prepare shipping
		// Checking whether shipping is required
		$showShipping = false;

		if($this->params->get('show_shipping_address', 0)) {
			$showShipping = true;
		}

		if ($isShippingEnabled = $cart_model->getShippingIsEnabled())
		{
			$showShipping = true;
		}
		$view->assign( 'showShipping', $showShipping );
		$view->assign('params', $this->params);
		$view->setLayout( 'checkout');

		$view->display();
		return;
	}


	function login() {
		$app = JFactory::getApplication();

		$view = $this->getView( 'checkout', 'html' );
		$model		= $this->getModel('checkout');
		//check session
		$account = $this->session->get('account', 'register', 'j2store');
		if (isset($account)) {
			$view->assign('account', $account);
		} else {
			$view->assign('account', 'register');
		}

		$view->assign('params', $this->params);
		$view->setLayout( 'checkout_login');
		$html = '';
		ob_start();
		$view->display();
		$html = ob_get_contents();
		ob_end_clean();
		echo $html;
		$app->close();
	}

	function login_validate() {

		$app = JFactory::getApplication();
		$user = JFactory::getUser();

		$model = $this->getModel('checkout');
		$cart_helper = new J2StoreHelperCart();
		$redirect_url = JRoute::_('index.php?option=com_j2store&view=checkout');

		$json = array();

		if ($user->id) {
			$json['redirect'] = $redirect_url;
		}

		if ((!$cart_helper->hasProducts())) {
			$json['redirect'] = $redirect_url;
		}

		if (!$json) {

			require_once (JPATH_ADMINISTRATOR.'/components/com_j2store/library/user.php');
			$userHelper = new J2StoreHelperUser;
			//now login the user
			if ( !$userHelper->login(
					array('username' => $app->input->getString('email'), 'password' => $app->input->getString('password'))
			))
			{
				$json['error']['warning'] = JText::_('J2STORE_CHECKOUT_ERROR_LOGIN');
			}

		}

		if (!$json) {
			$this->session->clear('guest', 'j2store');

			// Default Addresses
			$address_info = $this->getModel('address')->getSingleAddressByUserID();

			if ($address_info) {
				if ($this->params->get('config_tax_default') == 'shipping') {
					$this->session->set('shipping_country_id', $address_info->country_id, 'j2store');
					$this->session->set('shipping_zone_id',$address_info->zone_id, 'j2store');
					$this->session->set('shipping_postcode',$address_info->zip, 'j2store');
				}

				if ($this->params->get('config_tax_default') == 'billing') {
					$this->session->set('billing_country_id', $address_info->country_id, 'j2store');
					$this->session->set('billing_zone_id',$address_info->zone_id, 'j2store');
				}
			} else {
				$this->session->clear('shipping_country_id', 'j2store');
				$this->session->clear('shipping_zone_id', 'j2store');
				$this->session->clear('shipping_postcode', 'j2store');
				$this->session->clear('billing_country_id', 'j2store');
				$this->session->clear('billing_zone_id', 'j2store');
			}

			$json['redirect'] = $redirect_url;
		}
		echo json_encode($json);
		$app->close();
	}

	function register() {
		$app = JFactory::getApplication();

		$view = $this->getView( 'checkout', 'html' );
		$model		= $this->getModel('checkout');
		$cart_model = $this->getModel('mycart');

		$bill_country = $model->getCountryList('country_id','country_id', '');
		$view->assign('bill_country', $bill_country);

		$showShipping = false;
		if($this->params->get('show_shipping_address', 0)) {
			$showShipping = true;
		}

		if ($isShippingEnabled = $cart_model->getShippingIsEnabled())
		{
			$showShipping = true;
		}
		$view->assign( 'showShipping', $showShipping );
		$view->assign('params', $this->params);
		$view->setLayout( 'checkout_register');

		ob_start();
		$view->display();
		$html = ob_get_contents();
		ob_end_clean();
		echo $html;
		$app->close();
	}

	function register_validate() {

		$app = JFactory::getApplication();
		$user = JFactory::getUser();
		$model = $this->getModel('checkout');
		$redirect_url = JRoute::_('index.php?option=com_j2store&view=checkout');


		$json = array();

		// Validate if customer is already logged out.
		if ($user->id) {
			$json['redirect'] = $redirect_url;
		}

		// Validate cart has products and has stock.
		if (!J2StoreHelperCart::hasProducts()) {
			$json['redirect'] = $redirect_url;
		}

		// TODO Validate minimum quantity requirments.
		if (!$json) {

			if ((JString::strlen($app->input->post->getString('first_name')) < 1)) {
				$json['error']['first_name'] = JText::_('J2STORE_FIRST_NAME_REQUIRED');
			}

			if ((JString::strlen($app->input->post->getString('last_name')) < 1)) {
				$json['error']['last_name'] = JText::_('J2STORE_LAST_NAME_REQUIRED');
			}

			//if ((JString::strlen($app->input->post->get('email')) > 96) || !preg_match('/^[^\@]+@.*\.[a-z]{2,6}$/i', $app->input->post->get('email'))) {
			if (filter_var($app->input->post->getString('email'), FILTER_VALIDATE_EMAIL) == false) {
				$json['error']['email'] = JText::_('J2STORE_EMAIL_REQUIRED');
			}

			 $email_exists = $model->getTotalCustomersByEmail($app->input->post->getString('email'));
			if ($email_exists > 0) {
				$json['error']['warning'] = JText::_('J2STORE_EMAIL_EXISTS');
			}

			if ((JString::strlen($app->input->post->getString('phone_1')) < 3)) {
				$json['error']['phone_1'] = JText::_('J2STORE_PHONE_REQUIRED');
			}

			if($this->params->get('bill_company_name', 2)==1) {
				if ((JString::strlen($app->input->post->getString('company')) < 1)) {
					$json['error']['company'] = JText::_('J2STORE_COMPANY_REQUIRED');
				}
			}

			if($this->params->get('bill_tax_number', 2)==1) {
				if ((JString::strlen($app->input->post->getString('tax_number')) < 1)) {
					$json['error']['tax_number'] = JText::_('J2STORE_TAX_ID_REQUIRED');
				}
			}

			if ((JString::strlen($app->input->post->getString('address_1')) < 3)) {
				$json['error']['address_1'] = JText::_('J2STORE_ADDRESS_REQUIRED');
			}

			if ((JString::strlen($app->input->post->getString('city')) < 2)) {
				$json['error']['city'] = JText::_('J2STORE_CITY_REQUIRED');
			}

			if ((JString::strlen($app->input->post->getString('zip')) < 2)) {
				$json['error']['zip'] = JText::_('J2STORE_ZIP_REQUIRED');
			}

			if ($app->input->post->get('country_id') == '') {
				$json['error']['country'] = JText::_('J2STORE_SELECT_A_COUNTRY');
			}
			$zone_id = $app->input->post->get('zone_id');
			if (!isset($zone_id) || $zone_id == '') {
				$json['error']['zone'] = JText::_('J2STORE_SELECT_A_ZONE');
			}

			if ((JString::strlen($app->input->post->get('password')) < 4)) {
				$json['error']['password'] = JText::_('J2STORE_PASSWORD_REQUIRED');
			}

			if ($app->input->post->get('confirm') != $app->input->post->get('password')) {
				$json['error']['confirm'] = JText::_('J2STORE_PASSWORDS_DOESTNOT_MATCH');
			}
		}

		if (!$json) {

			require_once (JPATH_ADMINISTRATOR.'/components/com_j2store/library/user.php');
			$userHelper = new J2StoreHelperUser;
			//now create the user
			// create the details array with new user info
			$details = array(
					'email' =>  $app->input->getString('email'),
					'name' => $app->input->getString('first_name').' '.$app->input->getString('last_name'),
					'username' =>  $app->input->getString('email'),
					'password' => $app->input->getString('password'),
					'password2'=> $app->input->getString('confirm')
			);
			$msg = '';
			$user = $userHelper->createNewUser($details, $msg);

			$this->session->set('account', 'register', 'j2store');

			//now login the user
			if ( $userHelper->login(
						array('username' => $user->username, 'password' => $details['password'])
				)
			) {
				$billing_address_id = $userHelper->addCustomer();

				$this->session->set('billing_address_id', $billing_address_id , 'j2store');
				$this->session->set('billing_country_id', $app->input->post->get('country_id'), 'j2store');
				$this->session->set('billing_zone_id', $app->input->post->get('zone_id'), 'j2store');
				$shipping_address = $app->input->post->get('shipping_address');
				if (!empty($shipping_address )) {
					$this->session->set('shipping_address_id', $billing_address_id, 'j2store');
					$this->session->set('shipping_country_id', $app->input->post->get('country_id'), 'j2store');
					$this->session->set('shipping_zone_id', $app->input->post->get('zone_id'), 'j2store');
					$this->session->set('shipping_postcode', $app->input->post->get('zip'), 'j2store');
				}
			} else {
				$json['redirect'] = $redirect_url;
			}

			$this->session->clear('guest', 'j2store');
			$this->session->clear('shipping_method', 'j2store');
			$this->session->clear('shipping_methods', 'j2store');
			$this->session->clear('payment_method', 'j2store');
			$this->session->clear('payment_methods', 'j2store');
		}
		echo json_encode($json);
		$app->close();
	}


	function guest() {
		$app = JFactory::getApplication();
		$cart_model = $this->getModel('mycart');
		$view = $this->getView( 'checkout', 'html' );
		$model = $this->getModel('checkout');
		$tax = new J2StoreTax();
		//set guest varibale to session as the array, if it does not exist
		if(!$this->session->has('guest', 'j2store')) {
			$this->session->set('guest', array(), 'j2store');
		}
		$guest = $this->session->get('guest', array(), 'j2store');

		$data = array();

	if (isset($guest['first_name'])) {
			$data['first_name'] = $guest['first_name'];
		} else {
			$data['first_name'] = '';
		}

		if (isset($guest['last_name'])) {
			$data['last_name'] = $guest['last_name'];
		} else {
			$data['last_name'] = '';
		}

		if (isset($guest['email'])) {
			$data['email'] = $guest['email'];
		} else {
			$data['email'] = '';
		}

		if (isset($guest['phone_1'])) {
			$data['phone_1'] = $guest['phone_1'];
		} else {
			$data['phone_1'] = '';
		}

		if (isset($guest['phone_2'])) {
			$data['phone_2'] = $guest['phone_2'];
		} else {
			$data['phone_2'] = '';
		}

		if($this->params->get('bill_company_name', 2)!=3) {
			if (isset($guest['billing']['company'])) {
				$data['company'] = $guest['billing']['company'];
			} else {
				$data['company'] = '';
			}
		}

		if($this->params->get('bill_tax_number', 2)!=3) {
			if (isset($guest['billing']['tax_number'])) {
				$data['tax_number'] = $guest['billing']['tax_number'];
			} else {
				$data['tax_number'] = '';
			}
		}


		if (isset($guest['billing']['address_1'])) {
			$data['address_1'] = $guest['billing']['address_1'];
		} else {
			$data['address_1'] = '';
		}

		if (isset($guest['billing']['address_2'])) {
			$data['address_2'] = $guest['billing']['address_2'];
		} else {
			$data['address_2'] = '';
		}

		if (isset($guest['billing']['zip'])) {
			$data['zip'] = $guest['billing']['zip'];
		} elseif ($this->session->has('shipping_postcode', 'j2store')) {
			$data['zip'] = $this->session->get('shipping_postcode', '', 'j2store');
		} else {
			$data['zip'] = '';
		}

		if (isset($guest['billing']['city'])) {
			$data['city'] = $guest['billing']['city'];
		} else {
			$data['city'] = '';
		}

		if (isset($guest['billing']['country_id'])) {
			$data['country_id'] = $guest['billing']['country_id'];
		} elseif ($this->session->has('shipping_country_id', 'j2store')) {
			$data['country_id'] = $this->session->get('shipping_country_id', '', 'j2store');
		} else {
			$data['country_id'] = $tax->getStoreAddress()->country_id;
		}

		if (isset($guest['billing']['zone_id'])) {
			$data['zone_id'] = $guest['billing']['zone_id'];
		} elseif ($this->session->has('shipping_zone_id', 'j2store')) {
			$data['zone_id'] = $this->session->get('shipping_zone_id', '', 'j2store');
		} else {
			$data['zone_id'] = '';
		}

		$guest_bill_country = $model->getCountryList('country_id','country_id', $data['country_id']);
		$view->assign('guest_bill_country', $guest_bill_country);

		$showShipping = false;
		if($this->params->get('show_shipping_address', 0)) {
			$showShipping = true;
		}

		if ($isShippingEnabled = $cart_model->getShippingIsEnabled())
		{
			$showShipping = true;
		}
		$view->assign( 'showShipping', $showShipping );

		$data['shipping_required'] = $showShipping;

		if (isset($guest['shipping_address'])) {
			$data['shipping_address'] = $guest['shipping_address'];
		} else {
			$data['shipping_address'] = true;
		}
		$view->assign( 'data', $data);

		$view->setLayout( 'checkout_guest');

		ob_start();
		$view->display();
		$html = ob_get_contents();
		ob_end_clean();
		echo $html;
		$app->close();

	}

	function guest_validate() {

		$app = JFactory::getApplication();
		$cart_helper = new J2StoreHelperCart();
		$address_model = $this->getModel('address');
		$model = $this->getModel('checkout');
		$redirect_url = JRoute::_('index.php?option=com_j2store&view=checkout');

		//initialise guest value from session
		$guest = $this->session->get('guest', array(), 'j2store');

		$json = array();

		// Validate if customer is logged in.
		if (JFactory::getUser()->id) {
			$json['redirect'] = $redirect_url;
		}

		// Validate cart has products and has stock.
		if ((!$cart_helper->hasProducts())) {
			$json['redirect'] = $redirect_url;
		}

		// Check if guest checkout is avaliable.
		//TODO prevent if products have downloads also
		if (!$this->params->get('allow_guest_checkout')) {
			$json['redirect'] = $redirect_url;
		}

		if (!$json) {

			if ((JString::strlen($app->input->post->getString('first_name')) < 1)) {
				$json['error']['first_name'] = JText::_('J2STORE_FIRST_NAME_REQUIRED');
			}

			if ((JString::strlen($app->input->post->getString('last_name')) < 1)) {
				$json['error']['last_name'] = JText::_('J2STORE_LAST_NAME_REQUIRED');
			}

			//if ((JString::strlen($app->input->post->get('email')) > 96) || !preg_match('/^[^\@]+@.*\.[a-z]{2,6}$/i', $app->input->post->get('email'))) {
			if (filter_var($app->input->post->getString('email'), FILTER_VALIDATE_EMAIL) == false) {
				$json['error']['email'] = JText::_('J2STORE_EMAIL_REQUIRED');
			}

			if ((JString::strlen($app->input->post->getString('phone_1')) < 3)) {
				$json['error']['phone_1'] = JText::_('J2STORE_PHONE_REQUIRED');
			}

			if($this->params->get('bill_company_name', 2)==1) {
				if ((JString::strlen($app->input->post->getString('company')) < 1)) {
					$json['error']['company'] = JText::_('J2STORE_COMPANY_REQUIRED');
				}
			}

			if($this->params->get('bill_tax_number', 2)==1) {
				if ((JString::strlen($app->input->post->getString('tax_number')) < 1)) {
					$json['error']['tax_number'] = JText::_('J2STORE_TAX_ID_REQUIRED');
				}
			}

			if ((JString::strlen($app->input->post->getString('address_1')) < 3)) {
				$json['error']['address_1'] = JText::_('J2STORE_ADDRESS_REQUIRED');
			}

			if ((JString::strlen($app->input->post->getString('city')) < 2)) {
				$json['error']['city'] = JText::_('J2STORE_CITY_REQUIRED');
			}

			if ((JString::strlen($app->input->post->getString('zip')) < 2)) {
				$json['error']['zip'] = JText::_('J2STORE_ZIP_REQUIRED');
			}

			if ($app->input->post->get('country_id') == '') {
				$json['error']['country'] = JText::_('J2STORE_SELECT_A_COUNTRY');
			}
			$zone_id = $app->input->post->get('zone_id');
			if (!isset($zone_id) || $zone_id == '') {
				$json['error']['zone'] = JText::_('J2STORE_SELECT_A_ZONE');
			}
		}

		if (!$json) {

			$guest['first_name'] = $app->input->getString('first_name');
			$guest['last_name'] = $app->input->getString('last_name');
			$guest['email'] = $app->input->getString('email');
			$guest['phone_1'] = $app->input->getString('phone_1');
			$guest['phone_2'] = $app->input->getString('phone_2');

			$guest['billing']['first_name'] = $app->input->getString('first_name');
			$guest['billing']['last_name'] = $app->input->getString('last_name');

			if($this->params->get('bill_company_name', 2)!=3) {
				$guest['billing']['company'] = $app->input->getString('company');
			}
			if($this->params->get('bill_tax_number', 2)!=3) {
				$guest['billing']['tax_number'] = $app->input->getString('tax_number');
			}
			$guest['billing']['email'] = $app->input->getString('email');
			$guest['billing']['phone_1'] = $app->input->getString('phone_1');
			$guest['billing']['phone_2'] = $app->input->getString('phone_2');
			$guest['billing']['address_1'] = $app->input->getString('address_1');
			$guest['billing']['address_2'] = $app->input->getString('address_2');
			$guest['billing']['zip'] = $app->input->getString('zip');
			$guest['billing']['city'] = $app->input->getString('city');
			$guest['billing']['country_id'] = $app->input->getInt('country_id');
			$guest['billing']['zone_id'] = $app->input->getInt('zone_id');

			//now get the country info
			//returns an object
			$country_info = $model->getCountryById($app->input->getInt('country_id'));

			if ($country_info) {
				$guest['billing']['country_name'] = $country_info->country_name;
				$guest['billing']['iso_code_2'] = $country_info->country_isocode_2;
				$guest['billing']['iso_code_3'] = $country_info->country_isocode_3;
			} else {
				$guest['billing']['country_name'] = '';
				$guest['billing']['iso_code_2'] = '';
				$guest['billing']['iso_code_3'] = '';
			}

			$zone_info = $model->getZonesById($app->input->getInt('zone_id'));

			if ($zone_info) {
				$guest['billing']['zone_name'] = $zone_info->zone_name;
				$guest['billing']['zone_code'] = $zone_info->zone_code;
			} else {
				$guest['billing']['zone_name'] = '';
				$guest['billing']['zone_code'] = '';
			}

			if ($app->input->getInt('shipping_address')) {
				$guest['shipping_address'] = true;
			} else {
				$guest['shipping_address'] = false;
			}

			// Default billing address
			$this->session->set('billing_country_id', $app->input->getInt('country_id'), 'j2store');
			$this->session->set('billing_zone_id', $app->input->getInt('zone_id'), 'j2store');

			if ($guest['shipping_address']) {
				$guest['shipping']['first_name'] = $app->input->getString('first_name');
				$guest['shipping']['last_name'] = $app->input->getString('last_name');

				if($this->params->get('ship_company_name', 2)!=3) {
					$guest['shipping']['company'] = $app->input->getString('company');
				}

				$guest['shipping']['address_1'] = $app->input->getString('address_1');
				$guest['shipping']['address_2'] = $app->input->getString('address_2');
				$guest['shipping']['phone_1'] = $app->input->getString('phone_1');
				$guest['shipping']['phone_2'] = $app->input->getString('phone_2');
				$guest['shipping']['zip'] = $app->input->getString('zip');
				$guest['shipping']['city'] = $app->input->getString('city');
				$guest['shipping']['country_id'] = $app->input->getInt('country_id');
				$guest['shipping']['zone_id'] = $app->input->getInt('zone_id');

				if ($country_info) {
					$guest['shipping']['country_name'] = $country_info->country_name;
					$guest['shipping']['iso_code_2'] = $country_info->country_isocode_2;
					$guest['shipping']['iso_code_3'] = $country_info->country_isocode_3;
				} else {
					$guest['shipping']['country_name'] = '';
					$guest['shipping']['iso_code_2'] = '';
					$guest['shipping']['iso_code_3'] = '';
				}

				if ($zone_info) {
					$guest['shipping']['zone_name'] = $zone_info->zone_name;
					$guest['shipping']['zone_code'] = $zone_info->zone_code;
				} else {
					$guest['shipping']['zone_name'] = '';
					$guest['shipping']['zone_code'] = '';
				}
				// Default Shipping Address
				$this->session->set('shipping_country_id', $app->input->getInt('country_id'), 'j2store');
				$this->session->set('shipping_zone_id', $app->input->getInt('zone_id'), 'j2store');
				$this->session->set('shipping_postcode', $app->input->getString('zip'), 'j2store');

			}
			//now set the guest values to the session
			$this->session->set('guest', $guest, 'j2store');
			$this->session->set('account', 'guest', 'j2store');

			$this->session->clear('shipping_method', 'j2store');
			$this->session->clear('shipping_methods', 'j2store');
			$this->session->clear('payment_method', 'j2store');
			$this->session->clear('payment_methods', 'j2store');
		}
		echo json_encode($json);
		$app->close();

	}

	function guest_shipping() {

		$app = JFactory::getApplication();
		$cart_model = $this->getModel('mycart');
		$view = $this->getView( 'checkout', 'html' );
		$model = $this->getModel('checkout');
		$guest = $this->session->get('guest', array(), 'j2store');
		$tax = new J2StoreTax();

		$data = array();

		if (isset($guest['shipping']['first_name'])) {
			$data['first_name'] = $guest['shipping']['first_name'];
		} else {
			$data['first_name'] = '';
		}

		if (isset($guest['shipping']['last_name'])) {
			$data['last_name'] = $guest['shipping']['last_name'];
		} else {
			$data['last_name'] = '';
		}

		if (isset($guest['shipping']['phone_1'])) {
			$data['phone_1'] = $guest['shipping']['phone_1'];
		} else {
			$data['phone_1'] = '';
		}

		if (isset($guest['shipping']['phone_2'])) {
			$data['phone_2'] = $guest['shipping']['phone_2'];
		} else {
			$data['phone_2'] = '';
		}

		if($this->params->get('ship_company_name', 2)!=3) {
			if (isset($guest['shipping']['company'])) {
				$data['company'] = $guest['shipping']['company'];
			} else {
				$data['company'] = '';
			}
		}

		if (isset($guest['shipping']['address_1'])) {
			$data['address_1'] = $guest['shipping']['address_1'];
		} else {
			$data['address_1'] = '';
		}

		if (isset($guest['shipping']['address_2'])) {
			$data['address_2'] = $guest['shipping']['address_2'];
		} else {
			$data['address_2'] = '';
		}

		if (isset($guest['shipping']['zip'])) {
			$data['zip'] = $guest['shipping']['zip'];
		} elseif ($this->session->has('shipping_postcode', 'j2store')) {
			$data['zip'] = $this->session->get('shipping_postcode', '', 'j2store');
		} else {
			$data['zip'] = '';
		}

		if (isset($guest['shipping']['city'])) {
			$data['city'] = $guest['shipping']['city'];
		} else {
			$data['city'] = '';
		}

		if (isset($guest['shipping']['country_id'])) {
			$data['country_id'] = $guest['shipping']['country_id'];
		} elseif ($this->session->has('shipping_country_id', 'j2store')) {
			$data['country_id'] = $this->session->get('shipping_country_id','', 'j2store');
		} else {
			$data['country_id'] = $tax->getStoreAddress()->country_id;
		}

		if (isset($guest['shipping']['zone_id'])) {
			$data['zone_id'] = $guest['shipping']['zone_id'];
		} elseif ($this->session->has('shipping_zone_id', 'j2store')) {
			$data['zone_id'] = $this->session->get('shipping_zone_id', '', 'j2store');
		} else {
			$data['zone_id'] = '';
		}

		$guest_ship_country = $model->getCountryList('country_id','country_id', $data['country_id']);
		$view->assign('guest_ship_country', $guest_ship_country);

		$view->assign( 'data', $data);

		$view->setLayout( 'checkout_guest_shipping');

		ob_start();
		$view->display();
		$html = ob_get_contents();
		ob_end_clean();
		echo $html;
		$app->close();

	}

	function guest_shipping_validate() {
		$app = JFactory::getApplication();
		$cart_helper = new J2StoreHelperCart();
		$address_model = $this->getModel('address');
		$model = $this->getModel('checkout');
		$redirect_url = JRoute::_('index.php?option=com_j2store&view=checkout');

		//initialise guest value from session
		$guest = $this->session->get('guest', array(), 'j2store');
		$json = array();

		// Validate if customer is logged in.
		if (JFactory::getUser()->id) {
			$json['redirect'] = $redirect_url;
		}

		// Validate cart has products and has stock.
		if ((!$cart_helper->hasProducts())) {
			$json['redirect'] = $redirect_url;
		}

		// Check if guest checkout is avaliable.
		//TODO prevent if products have downloads also
		if (!$this->params->get('allow_guest_checkout')) {
			$json['redirect'] = $redirect_url;
		}

		if (!$json) {

			if ((JString::strlen($app->input->post->getString('first_name')) < 1)) {
				$json['error']['first_name'] = JText::_('J2STORE_FIRST_NAME_REQUIRED');
			}

			if ((JString::strlen($app->input->post->getString('last_name')) < 1)) {
				$json['error']['last_name'] = JText::_('J2STORE_LAST_NAME_REQUIRED');
			}

			if($this->params->get('ship_company_name', 2)==1) {
				if ((JString::strlen($app->input->post->getString('company')) < 1)) {
					$json['error']['company'] = JText::_('J2STORE_COMPANY_REQUIRED');
				}
			}

			if ((JString::strlen($app->input->post->getString('phone_1')) < 3)) {
				$json['error']['phone_1'] = JText::_('J2STORE_PHONE_REQUIRED');
			}

			if ((JString::strlen($app->input->post->getString('address_1')) < 3)) {
				$json['error']['address_1'] = JText::_('J2STORE_ADDRESS_REQUIRED');
			}

			if ((JString::strlen($app->input->post->getString('city')) < 2)) {
				$json['error']['city'] = JText::_('J2STORE_CITY_REQUIRED');
			}

				if ((JString::strlen($app->input->post->getString('zip')) < 2)) {
					$json['error']['zip'] = JText::_('J2STORE_ZIP_REQUIRED');
				}

			if ($app->input->post->get('country_id') == '') {
				$json['error']['country'] = JText::_('J2STORE_SELECT_A_COUNTRY');
			}
			$zone_id = $app->input->post->get('zone_id');
			if (!isset($zone_id) || $zone_id == '') {
				$json['error']['zone'] = JText::_('J2STORE_SELECT_A_ZONE');
			}
		}

		if(!$json) {
			$guest['shipping']['first_name'] = $app->input->getString('first_name');
			$guest['shipping']['last_name'] = $app->input->getString('last_name');

			if($this->params->get('ship_company_name', 2)!=3) {
				$guest['shipping']['company'] = $app->input->getString('company');
			}

			$guest['shipping']['address_1'] = $app->input->getString('address_1');
			$guest['shipping']['address_2'] = $app->input->getString('address_2');
			$guest['shipping']['phone_1'] = $app->input->getString('phone_1');
			$guest['shipping']['phone_2'] = $app->input->getString('phone_2');
			$guest['shipping']['zip'] = $app->input->getString('zip');
			$guest['shipping']['city'] = $app->input->getString('city');
			$guest['shipping']['country_id'] = $app->input->getInt('country_id');
			$guest['shipping']['zone_id'] = $app->input->getInt('zone_id');

			//now get the country info
			//returns an object
			$country_info = $model->getCountryById($app->input->getInt('country_id'));

			if ($country_info) {
				$guest['shipping']['country_name'] = $country_info->country_name;
				$guest['shipping']['iso_code_2'] = $country_info->country_isocode_2;
				$guest['shipping']['iso_code_3'] = $country_info->country_isocode_3;
			} else {
				$guest['shipping']['country_name'] = '';
				$guest['shipping']['iso_code_2'] = '';
				$guest['shipping']['iso_code_3'] = '';
			}

			$zone_info = $model->getZonesById($app->input->getInt('zone_id'));

			if ($zone_info) {
				$guest['shipping']['zone_name'] = $zone_info->zone_name;
				$guest['shipping']['zone_code'] = $zone_info->zone_code;
			} else {
				$guest['shipping']['zone_name'] = '';
				$guest['shipping']['zone_code'] = '';
			}
			// Default Shipping Address
			$this->session->set('shipping_country_id', $app->input->getInt('country_id'), 'j2store');
			$this->session->set('shipping_zone_id', $app->input->getInt('zone_id'), 'j2store');
			$this->session->set('shipping_postcode', $app->input->getString('zip'), 'j2store');

			//now set the guest values to the session
			$this->session->set('guest', $guest, 'j2store');

			$this->session->clear('shipping_method', 'j2store');
			$this->session->clear('shipping_methods', 'j2store');

		}
		echo json_encode($json);
		$app->close();
	}

	function billing_address() {

		$app = JFactory::getApplication();
		$address = $this->getModel('address')->getSingleAddressByUserID();
		$view = $this->getView( 'checkout', 'html' );
		$model = $this->getModel('checkout');

		//get the billing address id from the session
		if ($this->session->has('billing_address_id', 'j2store')) {
			$billing_address_id = $this->session->get('billing_address_id', '', 'j2store');
		} else {
			$billing_address_id = isset($address->id)?$address->id:'';
		}

		$view->assign('address_id', $billing_address_id);

		if ($this->session->has('billing_country_id', 'j2store')) {
			$billing_country_id = $this->session->get('billing_country_id', '', 'j2store');
		} else {
			$billing_country_id = isset($address->country_id)?$address->country_id:'';
		}

		if ($this->session->has('billing_zone_id', 'j2store')) {
			$billing_zone_id = $this->session->get('billing_zone_id', '', 'j2store');
		} else {
			$billing_zone_id = isset($address->zone_id)?$address->zone_id:'';
		}
		$view->assign('zone_id', $billing_zone_id);

		//get all address
		$addresses = $this->getModel('address')->getAddresses();
		$view->assign('addresses', $addresses);

		$bill_country = $model->getCountryList('country_id','country_id', $billing_country_id);
		$view->assign('bill_country', $bill_country);

		$view->setLayout( 'checkout_billing');

		ob_start();
		$view->display();
		$html = ob_get_contents();
		ob_end_clean();
		echo $html;
		$app->close();
	}

	//validate billing address

	function billing_address_validate() {

		$app = JFactory::getApplication();
		$user = JFactory::getUser();
		$address_model = $this->getModel('address');
		$redirect_url = JRoute::_('index.php?option=com_j2store&view=checkout');

		$json = array();

		// Validate if customer is logged or not.
		if (!$user->id) {
			$json['redirect'] = $redirect_url;
		}

		// Validate cart has products and has stock.
		if (!J2StoreHelperCart::hasProducts()) {
			$json['redirect'] = $redirect_url;
		}

		// TODO Validate minimum quantity requirments.

		//Has the customer selected an existing address?
		$selected_billing_address =$app->input->getString('billing_address');
		if (isset($selected_billing_address ) && $app->input->getString('billing_address') == 'existing') {
			$selected_address_id =	$app->input->getInt('address_id');
			if (empty($selected_address_id)) {
				$json['error']['warning'] = JText::_('J2STORE_ADDRESS_SELECTION_ERROR');
			} elseif (!in_array($app->input->getInt('address_id'), array_keys($address_model->getAddresses('id')))) {
				$json['error']['warning'] = JText::_('J2STORE_ADDRESS_SELECTION_ERROR');
			} else {
				// Default Payment Address
				$address_info = $address_model->getAddress($app->input->getInt('address_id'));
			}

			if (!$json) {
				$this->session->set('billing_address_id', $app->input->getInt('address_id'), 'j2store');

				if ($address_info) {
					$this->session->set('billing_country_id',$address_info['country_id'], 'j2store');
					$this->session->set('billing_zone_id',$address_info['zone_id'], 'j2store');
				} else {
					$this->session->clear('billing_country_id', 'j2store');
					$this->session->clear('billing_zone_id', 'j2store');
				}
				$this->session->clear('payment_method', 'j2store');
				$this->session->clear('payment_methods', 'j2store');
			}
		} else {

			if (!$json) {

				if ((JString::strlen($app->input->post->getString('first_name')) < 1)) {
					$json['error']['first_name'] = JText::_('J2STORE_FIRST_NAME_REQUIRED');
				}

				if ((JString::strlen($app->input->post->getString('last_name')) < 1)) {
					$json['error']['last_name'] = JText::_('J2STORE_LAST_NAME_REQUIRED');
				}

				if ((JString::strlen($app->input->post->getString('phone_1')) < 3)) {
					$json['error']['phone_1'] = JText::_('J2STORE_PHONE_REQUIRED');
				}

				if($this->params->get('bill_company_name', 2)==1) {
					if ((JString::strlen($app->input->post->getString('company')) < 1)) {
						$json['error']['company'] = JText::_('J2STORE_COMPANY_REQUIRED');
					}
				}

				if($this->params->get('bill_tax_number', 2)==1) {
					if ((JString::strlen($app->input->post->getString('tax_number')) < 1)) {
						$json['error']['tax_number'] = JText::_('J2STORE_TAX_ID_REQUIRED');
					}
				}

				if ((JString::strlen($app->input->post->getString('address_1')) < 3)) {
					$json['error']['address_1'] = JText::_('J2STORE_ADDRESS_REQUIRED');
				}

				if ((JString::strlen($app->input->post->getString('city')) < 2)) {
					$json['error']['city'] = JText::_('J2STORE_CITY_REQUIRED');
				}

				if ((JString::strlen($app->input->post->getString('zip')) < 2)) {
					$json['error']['zip'] = JText::_('J2STORE_ZIP_REQUIRED');
				}

				if ($app->input->post->get('country_id') == '') {
					$json['error']['country'] = JText::_('J2STORE_SELECT_A_COUNTRY');
				}
				$zone_id = $app->input->post->get('zone_id');
				if (!isset($zone_id) || $zone_id == '') {
					$json['error']['zone'] = JText::_('J2STORE_SELECT_A_ZONE');
				}

				if(!$json) {
					$address_id = $address_model->addAddress('billing');
					//now get the address and save to session
					$address_info = $address_model->getAddress($address_id);

					$this->session->set('billing_address_id', $address_info['id'], 'j2store');
					$this->session->set('billing_country_id',$address_info['country_id'], 'j2store');
					$this->session->set('billing_zone_id',$address_info['zone_id'], 'j2store');
					$this->session->clear('payment_method', 'j2store');
					$this->session->clear('payment_methods', 'j2store');
				}

			}

		}
		echo json_encode($json);
		$app->close();

	}

	//shipping address

	function shipping_address() {

		$app = JFactory::getApplication();
		$address = $this->getModel('address')->getSingleAddressByUserID();
		$view = $this->getView( 'checkout', 'html' );
		$model = $this->getModel('checkout');

		//get the billing address id from the session
		if ($this->session->has('shipping_address_id', 'j2store')) {
			$shipping_address_id = $this->session->get('shipping_address_id', '', 'j2store');
		} else {
			$shipping_address_id = $address->id;
		}

		$view->assign('address_id', $shipping_address_id);

		if ($this->session->has('shipping_postcode', 'j2store')) {
			$shipping_postcode = $this->session->get('shipping_postcode', '', 'j2store');
		} else {
			$shipping_postcode = $address->zip;
		}

		if ($this->session->has('shipping_country_id', 'j2store')) {
			$shipping_country_id = $this->session->get('shipping_country_id', '', 'j2store');
		} else {
			$shipping_country_id = $address->country_id;
		}

		if ($this->session->has('shipping_zone_id', 'j2store')) {
			$shipping_zone_id = $this->session->get('shipping_zone_id', '', 'j2store');
		} else {
			$shipping_zone_id = $address->zone_id;
		}
		$view->assign('zone_id', $shipping_zone_id);

		//get all address
		$addresses = $this->getModel('address')->getAddresses();
		$view->assign('addresses', $addresses);

		$ship_country = $model->getCountryList('country_id','country_id', $shipping_country_id);
		$view->assign('ship_country', $ship_country);

		$view->setLayout( 'checkout_shipping');

		ob_start();
		$view->display();
		$html = ob_get_contents();
		ob_end_clean();
		echo $html;
		$app->close();
	}

function shipping_address_validate() {

	$app = JFactory::getApplication();
	$user = JFactory::getUser();
	$address_model = $this->getModel('address');
	$redirect_url = JRoute::_('index.php?option=com_j2store&view=checkout');
	$cart_model = $this->getModel('mycart');
	$json = array();

	// Validate if customer is logged or not.
	if (!$user->id) {
		$json['redirect'] = $redirect_url;
	}
	// Validate if shipping is required. If not the customer should not have reached this page.
	$showShipping = false;

	if($this->params->get('show_shipping_address', 0)) {
		$showShipping = true;
	}

	if ($isShippingEnabled = $cart_model->getShippingIsEnabled())
	{
		$showShipping = true;
	}


	if ($showShipping == false) {
		$json['redirect'] = $redirect_url;
	}

	// Validate cart has products and has stock.
	if (!J2StoreHelperCart::hasProducts()) {

		$json['redirect'] = $redirect_url;
	}
	// TODO Validate minimum quantity requirments.

	//Has the customer selected an existing address?
	$selected_shipping_address =$app->input->getString('shipping_address');
	if (isset($selected_shipping_address ) && $app->input->getString('shipping_address') == 'existing') {
		$selected_address_id =	$app->input->getInt('address_id');
		if (empty($selected_address_id)) {
			$json['error']['warning'] = JText::_('J2STORE_ADDRESS_SELECTION_ERROR');
		} elseif (!in_array($app->input->getInt('address_id'), array_keys($address_model->getAddresses('id')))) {
			$json['error']['warning'] = JText::_('J2STORE_ADDRESS_SELECTION_ERROR');
		} else {
			// Default shipping Address. returns associative list of single record
			$address_info = $address_model->getAddress($app->input->getInt('address_id'));
		}

		if (!$json) {
			$this->session->set('shipping_address_id', $app->input->getInt('address_id'), 'j2store');

			if ($address_info) {
				$this->session->set('shipping_country_id',$address_info['country_id'], 'j2store');
				$this->session->set('shipping_zone_id',$address_info['zone_id'], 'j2store');
				$this->session->set('shipping_postcode',$address_info['zip'], 'j2store');
			} else {
				$this->session->clear('shipping_country_id', 'j2store');
				$this->session->clear('shipping_zone_id', 'j2store');
				$this->session->clear('shipping_postcode', 'j2store');
			}
			$this->session->clear('shipping_method', 'j2store');
			$this->session->clear('shipping_methods', 'j2store');
		}
	} else {
		if (!$json) {

			if ((JString::strlen($app->input->post->getString('first_name')) < 1)) {
				$json['error']['first_name'] = JText::_('J2STORE_FIRST_NAME_REQUIRED');
			}

			if ((JString::strlen($app->input->post->getString('last_name')) < 1)) {
				$json['error']['last_name'] = JText::_('J2STORE_LAST_NAME_REQUIRED');
			}

			if($this->params->get('ship_company_name', 2)==1) {
				if ((JString::strlen($app->input->post->getString('company')) < 1)) {
					$json['error']['company'] = JText::_('J2STORE_COMPANY_REQUIRED');
				}
			}

			if ((JString::strlen($app->input->post->getString('phone_1')) < 3)) {
				$json['error']['phone_1'] = JText::_('J2STORE_PHONE_REQUIRED');
			}

			if ((JString::strlen($app->input->post->getString('address_1')) < 3)) {
				$json['error']['address_1'] = JText::_('J2STORE_ADDRESS_REQUIRED');
			}

			if ((JString::strlen($app->input->post->getString('city')) < 2)) {
				$json['error']['city'] = JText::_('J2STORE_CITY_REQUIRED');
			}

			if ((JString::strlen($app->input->post->getString('zip')) < 2)) {
				$json['error']['zip'] = JText::_('J2STORE_ZIP_REQUIRED');
			}

			if ($app->input->post->get('country_id') == '') {
				$json['error']['country'] = JText::_('J2STORE_SELECT_A_COUNTRY');
			}
			$zone_id = $app->input->post->get('zone_id');
			if (!isset($zone_id) || $zone_id == '') {
				$json['error']['zone'] = JText::_('J2STORE_SELECT_A_ZONE');
			}

			if(!$json) {
				$address_id = $address_model->addAddress('shipping');
				//now get the address and save to session
				$address_info = $address_model->getAddress($address_id);

				$this->session->set('shipping_address_id', $address_info['id'], 'j2store');
				$this->session->set('shipping_country_id',$address_info['country_id'], 'j2store');
				$this->session->set('shipping_zone_id',$address_info['zone_id'], 'j2store');
				$this->session->set('shipping_postcode',$address_info['zip'], 'j2store');
				$this->session->clear('shipping_method', 'j2store');
				$this->session->clear('shipping_methods', 'j2store');
			}

		}

	}

	echo json_encode($json);
	$app->close();
}

//shipping and payment method
//TODO:: after developing shipping options, divide this function into two

	function shipping_payment_method() {
		$app = JFactory::getApplication();
		$view = $this->getView( 'checkout', 'html' );
		$task = JRequest::getVar('task');
		$model		= $this->getModel('checkout');
		$cart_helper = new J2StoreHelperCart();
		$cart_model = $this->getModel('mycart');

		if (!$cart_helper->hasProducts())
		{
			$msg = JText::_('J2STORE_NO_ITEMS_IN_CART');
			$link = JRoute::_('index.php?option=com_j2store&view=mycart');
			$app->redirect($link, $msg);
		}

		//prepare order
		$order= $this->_order;
		$order = $this->populateOrder(false);
		// get the order totals
		$order->calculateTotals();
//print_r($order->order_discount);
//print_r($order->order_tax);

		$dispatcher = JDispatcher::getInstance();
		JPluginHelper::importPlugin ('j2store');

		//shipping
		$showShipping = false;

		if($this->params->get('show_shipping_address', 0)) {
			$showShipping = true;
		}

		if ($isShippingEnabled = $cart_model->getShippingIsEnabled())
		{
			$showShipping = true;
			//$this->setShippingMethod();
		}
		$view->assign( 'showShipping', $showShipping );

		if($showShipping)
		{
			$rates = $this->getShippingRates();

			$shipping_layout = "shipping_yes";
		//	if (!$this->session->has('shipping_address_id', 'j2store'))
		//	{
		//		$shipping_layout = "shipping_calculate";
		//	}

			$shipping_method_form = $this->getShippingHtml( $shipping_layout );
			$view->assign( 'showShipping', $showShipping );
			$view->assign( 'shipping_method_form', $shipping_method_form );

			$view->assign( 'rates', $rates );
		}


		//process payment plugins
		$showPayment = true;
		if ((float)$order->order_total == (float)'0.00')
		{
			$showPayment = false;
		}
		$view->assign( 'showPayment', $showPayment );

		require_once (JPATH_SITE.'/components/com_j2store/helpers/plugin.php');
		$payment_plugins = J2StoreHelperPlugin::getPluginsWithEvent( 'onJ2StoreGetPaymentPlugins' );



		$plugins = array();
		if ($payment_plugins)
		{
			foreach ($payment_plugins as $plugin)
			{
				$results = $dispatcher->trigger( "onJ2StoreGetPaymentOptions", array( $plugin->element, $order ) );
				if (in_array(true, $results, true))
				{
					$plugins[] = $plugin;
				}
			}
		}

		if (count($plugins) == 1)
		{
			$plugins[0]->checked = true;
			ob_start();
			$this->getPaymentForm( $plugins[0]->element );
			$html = json_decode( ob_get_contents() );
			ob_end_clean();
			$view->assign( 'payment_form_div', $html->msg );
		}

		$view->assign('plugins', $plugins);
		//also set the payment methods to session



		//terms and conditions
		if( $this->params->get('termsid') ){
			$tos_link = JRoute::_('index.php?option=com_content&view=article&tmpl=component&id='.$this->params->get('termsid'));
		}else{
			$tos_link=null;
		}

		$view->assign( 'tos_link', $tos_link);

		//Get and Set Model
		$view->setModel( $model, true );
		$view->assign( 'order', $order );
		$view->assign('params', $this->params);
		$view->setLayout( 'checkout_shipping_payment');
		ob_start();
		$view->display();
		$html = ob_get_contents();
		ob_end_clean();
		echo $html;
		$app->close();

	}

	function shipping_payment_method_validate() {

		$app = JFactory::getApplication();
		$user = JFactory::getUser();
		$model		= $this->getModel('checkout');
		$cart_helper = new J2StoreHelperCart();
		$cart_model = $this->getModel('mycart');
		$address_model = $this->getModel('address');
		$redirect_url = JRoute::_('index.php?option=com_j2store&view=checkout');
		//now get the values posted by the plugin, if any
		$values = $app->input->getArray($_POST);
		$json = array();
		//validate weather the customer is logged in
		$billing_address = '';
		if ($user->id && $this->session->has('billing_address_id', 'j2store')) {
			$billing_address = $address_model->getAddress($this->session->get('billing_address_id', '', 'j2store'));
		} elseif ($this->session->has('guest', 'j2store')) {
			$guest = $this->session->get('guest', array(), 'j2store');
			$billing_address = $guest['billing'];
		}

		if (empty($billing_address)) {
			$json['redirect'] = $redirect_url;
		}

		//cart has products?
		if(!$cart_helper->hasProducts()) {
			$json['redirect'] = $redirect_url;
		}

		if (!$json) {

			$isShippingEnabled = $cart_model->getShippingIsEnabled();
			//validate selection of shipping methods and set the shipping rates
			if($this->params->get('show_shipping_address', 0) || $isShippingEnabled ) {
				//shipping is required.

				if ($user->id && $this->session->has('shipping_address_id', 'j2store')) {
					$shipping_address = $address_model->getAddress($this->session->get('shipping_address_id', '', 'j2store'));
				} elseif ($this->session->has('guest', 'j2store')) {
					$guest = $this->session->get('guest', array(), 'j2store');
					$shipping_address = $guest['shipping'];
				}

				//check if shipping address id is set in session. If not, redirect
				if(empty($shipping_address)) {
					$json['error']['shipping'] = JText::_('J2STORE_CHECKOUT_ERROR_SHIPPING_ADDRESS_NOT_FOUND');
					$json['redirect'] = $redirect_url;
				}

				try {
					$this->validateSelectShipping($values);
				} catch (Exception $e) {
					$json['error']['shipping'] = $e->getMessage();
				}

				if(!$json) {

						$shipping_values = array();
						$shipping_values['shipping_price']    = isset($values['shipping_price']) ? $values['shipping_price'] : 0;
						$shipping_values['shipping_extra']   = isset($values['shipping_extra']) ? $values['shipping_extra'] : 0;
						$shipping_values['shipping_code']     = isset($values['shipping_code']) ? $values['shipping_code'] : '';
						$shipping_values['shipping_name']     = isset($values['shipping_name']) ? $values['shipping_name'] : '';
						$shipping_values['shipping_tax']      = isset($values['shipping_tax']) ? $values['shipping_tax'] : 0;
						$shipping_values['shipping_plugin']     = isset($values['shipping_plugin']) ? $values['shipping_plugin'] : '';
						//set the shipping method to session
						$this->session->set('shipping_method',$shipping_values['shipping_plugin'], 'j2store');
						$this->session->set('shipping_values',$shipping_values, 'j2store');
				}

			}

		}

		//validate selection of payment methods
		if (!$json) {

			//payment validation had to be done only when the order value is greater than zero
			//prepare order
			$order= $this->_order;
			$order = $this->populateOrder(false);
			// get the order totals
			$order->calculateTotals();
			$showPayment = true;
			if ((float)$order->order_total == (float)'0.00')
			{
				$showPayment = false;
			}



			if($showPayment) {
				$payment_plugin = $app->input->getString('payment_plugin');
				if (!isset($payment_plugin)) {
					$json['error']['warning'] = JText::_('J2STORE_CHECKOUT_ERROR_PAYMENT_METHOD');
				} elseif (!isset($payment_plugin )) {
					$json['error']['warning'] = JText::_('J2STORE_CHECKOUT_ERROR_PAYMENT_METHOD');
				}
				//validate the selected payment
				try {
					$this->validateSelectPayment($payment_plugin, $values);
				} catch (Exception $e) {
					$json['error']['warning'] = $e->getMessage();
				}

			}

			if($this->params->get('show_terms', 0) && $this->params->get('terms_display_type', 'link') =='checkbox' ) {
				$tos_check = $app->input->get('tos_check');
				if (!isset($tos_check)) {
					$json['error']['warning'] = JText::_('J2STORE_CHECKOUT_ERROR_AGREE_TERMS');
				}
			}

			if (!$json) {

				$payment_plugin = $app->input->getString('payment_plugin');
				//set the payment plugin form values in the session as well.
				$this->session->set('payment_values', $values, 'j2store');
				$this->session->set('payment_method', $payment_plugin, 'j2store');
				$this->session->set('customer_note', strip_tags($app->input->getString('customer_note')), 'j2store');
			}
		}
		echo json_encode($json);
		$app->close();
	}

	function confirm() {

		$app = JFactory::getApplication();
		$user = JFactory::getUser();
		$lang = JFactory::getLanguage();

		$view = $this->getView( 'checkout', 'html' );
		$model		= $this->getModel('checkout');
		$cart_helper = new J2StoreHelperCart();
		$cart_model = $this->getModel('mycart');
		$address_model = $this->getModel('address');
		$redirect_url = JRoute::_('index.php?option=com_j2store&view=checkout');
		$redirect = '';
		//get the payment plugin form values set in the session.
		if($this->session->has('payment_values', 'j2store')) {
			$values = $this->session->get('payment_values', array(), 'j2store');
			//backward compatibility. TODO: change the way the plugin gets its data
			foreach($values as $name=>$value) {
				$app->input->set($name, $value);
			}
		}
		//prepare order
		$order= $this->_order;
		$order = $this->populateOrder(false);
		// get the order totals
		$order->calculateTotals();

		//set shiping address
		if($user->id && $this->session->has('shipping_address_id', 'j2store')) {
			$shipping_address = $address_model->getAddress($this->session->get('shipping_address_id', '', 'j2store'));
		} elseif($this->session->has('guest', 'j2store')) {
			$guest = $this->session->get('guest', array(), 'j2store');
			if($guest['shipping']) {
				$shipping_address = $guest['shipping'];
			}

		}else{
			$shipping_address = array();
		}

		//validate shipping
		$showShipping = false;
		if ($isShippingEnabled = $cart_model->getShippingIsEnabled())
		{
			if (empty($shipping_address)) {
				$redirect = $redirect_url;
			}
			$showShipping = true;
			if($this->session->has('shipping_values', 'j2store')) {

				//set the shipping methods
				$shipping_values = $this->session->get('shipping_values', array(), 'j2store');
				$this->setShippingMethod($shipping_values);

			}

		}else {
			$this->session->clear('shipping_method', 'j2store');
			$this->session->clear('shipping_values', 'j2store');
		}
		$view->assign( 'showShipping', $showShipping );

		//process payment plugins
		$showPayment = true;
		if ((float)$order->order_total == (float)'0.00')
		{
			$showPayment = false;
		}
		$view->assign( 'showPayment', $showPayment );

		// Validate if billing address has been set.

		if ($user->id && $this->session->has('billing_address_id', 'j2store')) {
			$billing_address = $address_model->getAddress($this->session->get('billing_address_id', '', 'j2store'));
		} elseif ($this->session->has('guest', 'j2store')) {
			$guest = $this->session->get('guest', array(), 'j2store');
			$billing_address = $guest['billing'];
		}

		if (empty($billing_address)) {
			$redirect = $redirect_url;
		}

		// Validate if payment method has been set.
		if ($showPayment == true && !$this->session->has('payment_method', 'j2store')) {
			$redirect = $redirect_url;

			If(!$this->validateSelectPayment($this->session->get('payment_method', '', 'j2store'), $values)) {
				$redirect = $redirect_url;
			}

		}


		// Validate cart has products and has stock.
		if (!$cart_helper->hasProducts()) {
			$redirect = $redirect_url;
		}
		//minimum order value check
		if(!$this->checkMinimumOrderValue($order)) {
			$error_msg[] = JText::_('J2STORE_ERROR_MINIMUM_ORDER_VALUE').J2StorePrices::number($this->params->get('global_minordervalue'));
			$redirect = $redirect_url;
		}

		if(!$redirect) {

			$order_id = time();
			$values['order_id'] = $order_id;
			$user_email = ($user->id)?$user->email:$billing_address['email'];
			$values['user_email']=$user_email;

			// Save the orderitems with  status
			if (!$this->saveOrderItems($values))
			{	// Output error message and halt
				$error_msg[] = $this->getError();
			}

			// Save the orderfiles
			if (!$this->saveOrderFiles($values))
			{
				$error_msg[] = $this->getError();
			}

			$orderpayment_type = $this->session->get('payment_method', '', 'j2store');

			//set a default transaction status.
			$transaction_status = JText::_( "J2STORE_TRANSACTION_INCOMPLETE" );

			// in the case of orders with a value of 0.00, use custom values
			if ( (float) $order->order_total == (float)'0.00' )
			{
				$orderpayment_type = 'free';
				$transaction_status = JText::_( "J2STORE_TRANSACTION_COMPLETE" );
			}

			//set order values
			$order->user_id = $user->id;
			$order->ip_address = $_SERVER['REMOTE_ADDR'];

			//generate a unique hash
			$order->token = JApplication::getHash($order_id);
			//user email
			$user_email = ($user->id)?$user->email:$billing_address['email'];
			$order->user_email = $user_email;

			//get the customer note
			$customer_note = $this->session->get('customer_note', '', 'j2store');
			$order->customer_note = $customer_note;
			$order->customer_language = $lang->getTag();

			// Save an order with an Incomplete status
			$order->order_id = $order_id;
			$order->orderpayment_type = $orderpayment_type; // this is the payment plugin selected
			$order->transaction_status = $transaction_status; // payment plugin updates this field onPostPayment
			$order->order_state_id = 5; // default incomplete order state
			$order->orderpayment_amount = $order->order_total; // this is the expected payment amount.  payment plugin should verify actual payment amount against expected payment amount

			if ($order->save())
			{
				//set values for orderinfo table

				// send the order_id and orderpayment_id to the payment plugin so it knows which DB record to update upon successful payment
				$values["order_id"]             = $order->order_id;
				//$values["orderinfo"]            = $order->orderinfo;
				$values["orderpayment_id"]      = $order->id;
				$values["orderpayment_amount"]  = $order->orderpayment_amount;


				if($billing_address) {
					foreach ($billing_address as $key=>$value) {
						$values['orderinfo']['billing_'.$key] = $value;
						//legacy compatability for payment plugins
						$values['orderinfo'][$key] = $value;
					}
					$values['orderinfo']['country'] = $billing_address['country_name'];
					$values['orderinfo']['state'] = $billing_address['zone_name'];
				}

				if(isset($shipping_address) && is_array($shipping_address)) {

					foreach ($shipping_address as $key=>$value) {
						$values['orderinfo']['shipping_'.$key] = $value;
					}
				}

				$values['orderinfo']['user_email'] = $user_email;
				$values['orderinfo']['user_id'] = $user->id;
				$values['orderinfo']['order_id'] = $order->order_id;
				$values['orderinfo']['orderpayment_id'] = $order->id;

				try {
					$this->saveOrderInfo($values['orderinfo']);
				} catch (Exception $e) {
					$redirect = $redirect_url;
					echo $e->getMessage()."\n";
				}


				//save shipping info
				if ( isset( $order->shipping ) && !$this->saveOrderShippings( $shipping_values ))
				{
					// TODO What to do if saving order shippings fails?
					$error = true;
				}


			} else {
				// Output error message and halt
				JError::raiseNotice( 'J2STORE_ERROR_SAVING_ORDER', $order->getError() );
				$redirect = $redirect_url;
			}

			// IMPORTANT: Store the order_id in the user's session for the postPayment "View Invoice" link

			$app->setUserState( 'j2store.order_id', $order->order_id );
			$app->setUserState( 'j2store.orderpayment_id', $order->id );
			$app->setUserState( 'j2store.order_token', $order->token);
			// in the case of orders with a value of 0.00, we redirect to the confirmPayment page
			if ( (float) $order->order_total == (float)'0.00' )
			{
				$redirect = JRoute::_( 'index.php?option=com_j2store&view=checkout&task=confirmPayment' );
			}

			$payment_plugin = $this->session->get('payment_method', '', 'j2store');
			$values['payment_plugin'] =$payment_plugin;
			$dispatcher    = JDispatcher::getInstance();
			JPluginHelper::importPlugin ('j2store');
			$results = $dispatcher->trigger( "onJ2StorePrePayment", array( $payment_plugin, $values ) );

			// Display whatever comes back from Payment Plugin for the onPrePayment
			$html = "";
			for ($i=0; $i<count($results); $i++)
			{
			$html .= $results[$i];
			}

			//check if plugins set a redirect
			if($this->session->has('plugin_redirect', 'j2store') ) {
					$redirect = $this->session->get('plugin_redirect', '', 'j2store');
			}

			$view->assign('plugin_html', $html);

			$summary = $this->getOrderSummary();
			$view->assign('orderSummary', $summary);

		}
			// Set display
			$view->setLayout('checkout_confirm');
			$view->set( '_doTask', true);
			$view->assign('order', $order);
			$view->assign('redirect', $redirect);
			$view->setModel( $model, true );
			ob_start();
			$view->display();
			$html = ob_get_contents();
			ob_end_clean();
			echo $html;
			$app->close();
	}


	public function ajaxGetZoneList() {

		$app = JFactory::getApplication();
		$model = $this->getModel('checkout');
		$post = JRequest::get('post');
		$country_id = $post['country_id'];
		$zone_id = $post['zone_id'];
		$name=$post['field_name'];;
		$id=$post['field_id'];
		if($country_id) {
			$zones = $model->getZoneList($name,$id,$country_id,$zone_id);
			echo $zones;
		}
		$app->close();
	}

	function getOrderSummary()
	{
		// get the order object
		$order= $this->_order;
		$model = $this->getModel('mycart');
		$view = $this->getView( 'checkout', 'html' );
		$view->set( '_controller', 'checkout' );
		$view->set( '_view', 'checkout' );
		$view->set( '_doTask', true);
		$view->set( 'hidemenu', true);
		$view->setModel( $model, true );
		$view->assign( 'state', $model->getState() );

		$show_tax = $this->params->get('show_tax_total');
		$view->assign( 'show_tax', $this->params->get('show_tax_total'));
		$view->assign( 'params', $this->params);
		$view->assign( 'order', $order );

		$orderitems = $order->getItems();
		foreach ($orderitems as &$item)
        {
      		$item->orderitem_price = $item->orderitem_price + floatval( $item->orderitem_attributes_price );
        	$taxtotal = 0;
            if($show_tax)
            {
            	$taxtotal = ($item->orderitem_tax / $item->orderitem_quantity);
            }
            $item->orderitem_price = $item->orderitem_price + $taxtotal;
            $item->orderitem_final_price = $item->orderitem_price * $item->orderitem_quantity;
            $order->order_subtotal += ($taxtotal * $item->orderitem_quantity);
        }


		// Checking whether shipping is required
		$showShipping = false;

		if ($isShippingEnabled = $model->getShippingIsEnabled())
		{
			$showShipping = true;
			$view->assign( 'shipping_total', $order->getShippingTotal() );
		}
		$view->assign( 'showShipping', $showShipping );

		$view->assign( 'orderitems', $orderitems );
		$view->setLayout( 'cartsummary' );

		ob_start();
		$view->display();
		$html = ob_get_contents();
		ob_end_clean();

		return $html;
	}

	function populateOrder($guest = false)
	{
		$order= $this->_order;
		//$order->shipping_method_id = $this->defaultShippingMethod;
		$items = J2StoreHelperCart::getProducts();
		foreach ($items as $item)
		{
			$order->addItem( $item );
		}
		// get the order totals
		$order->calculateTotals();

		return $order;
	}


	function checkMinimumOrderValue($order) {



		$min_value = $this->params->get('global_minordervalue');
		if(!empty($min_value)) {
			if($order->order_subtotal >= $min_value) {
			 return true;
			} else {
			 return false;
			}
		} else {
			return true;
		}
	}


	//hipping method set

	/**
	 * Sets the selected shipping method
	 *
	 * @return unknown_type
	 */
	function setShippingMethod($values)
	{

		$app = JFactory::getApplication();
		// get the order object so we can populate it
		$order = $this->_order; // a TableOrders object (see constructor)

		// set the shipping method
		$order->shipping = new JObject();
		$order->shipping->shipping_price      = $values['shipping_price'];
		$order->shipping->shipping_extra      = $values['shipping_extra'];
		$order->shipping->shipping_code      = $values['shipping_code'];
		$order->shipping->shipping_name       = $values['shipping_name'];
		$order->shipping->shipping_tax        = $values['shipping_tax'];
		$order->shipping->shipping_type		  = $values['shipping_plugin'];

		// get the order totals
		$order->calculateTotals();

		return;
	}




	function getShippingHtml( $layout='shipping_yes' )
	{
		$order= $this->_order;

		$html = '';
		$model = $this->getModel( 'Checkout', 'J2StoreModel' );
		$view   = $this->getView( 'checkout', 'html' );
		$view->set( '_controller', 'checkout' );
		$view->set( '_view', 'checkout' );
		$view->set( '_doTask', true);
		$view->set( 'hidemenu', true);
		$view->setModel( $model, true );
		$view->setLayout( $layout );
		$rates = array();

	 switch (strtolower($layout))
        {
            case "shipping_calculate":
                break;
            case "shipping_no":
                break;
            case "shipping_yes":
            default:
                $rates = $this->getShippingRates();
                $default_rate = array();

                if (count($rates) == 1)
                {
                    $default_rate = $rates[0];
                }
                $view->assign( 'rates', $rates );
                $view->assign( 'default_rate', $default_rate );
                break;
        }

		ob_start();
		$view->display();
		$html = ob_get_contents();
		ob_end_clean();
		return $html;
	}

	/**
	 * Gets the applicable rates
	 *
	 * @return array
	 */
	public function getShippingRates()
	{
		static $rates;

		if (empty($rates) || !is_array($rates))
		{
			$rates = array();
		}

		if (!empty($rates))
		{
			return $rates;
		}
		require_once (JPATH_SITE.'/components/com_j2store/helpers/plugin.php');
		JModelLegacy::addIncludePath(JPATH_ADMINISTRATOR.'/components/com_j2store/models');
		$model = JModelLegacy::getInstance('Shipping', 'J2StoreModel');
		$model->setState('filter_enabled', '1');
		$plugins = $model->getList();

		$dispatcher = JDispatcher::getInstance();

		$rates = array();

		// add taxes, even thought they aren't displayed
		$order_tax = 0;
		$orderitems = $this->_order->getItems();
		foreach( $orderitems as $item )
		{
			$this->_order->order_subtotal += $item->orderitem_tax;
			$order_tax += $item->orderitem_tax;
		}

		if ($plugins)
		{
			foreach ($plugins as $plugin)
			{

				$shippingOptions = $dispatcher->trigger( "onJ2StoreGetShippingOptions", array( $plugin->element, $this->_order ) );

				if (in_array(true, $shippingOptions, true))
				{
					$results = $dispatcher->trigger( "onJ2StoreGetShippingRates", array( $plugin->element, $this->_order ) );

					foreach ($results as $result)
					{
						if(is_array($result))
						{
							foreach( $result as $r )
							{
								$extra = 0;
								// here is where a global handling rate would be added
							//	if ($global_handling = $this->defines->get( 'global_handling' ))
							//	{
							//		$extra = $global_handling;
							//	}
								$r['extra'] += $extra;
								$r['total'] += $extra;
								$rates[] = $r;
							}
						}
					}
				}
			}
		}

		$this->_order->order_subtotal -= $order_tax;

		return $rates;
	}


	function getPaymentForm($element='')
	{
		$app = JFactory::getApplication();
		$values = JRequest::get('post');
		$html = '';
		$text = "";
		$user = JFactory::getUser();
		if (empty($element)) {
			$element = JRequest::getVar( 'payment_element' );
		}
		$results = array();
		$dispatcher    = JDispatcher::getInstance();
		JPluginHelper::importPlugin ('j2store');

		$results = $dispatcher->trigger( "onJ2StoreGetPaymentForm", array( $element, $values ) );
		for ($i=0; $i<count($results); $i++)
		{
			$result = $results[$i];
			$text .= $result;
		}

		$html = $text;

		// set response array
		$response = array();
		$response['msg'] = $html;

		// encode and echo (need to echo to send back to browser)
		echo json_encode($response);
		//$app->close();
		return;
	}

	/**
	 * Saves each individual item in the order to the DB
	 *
	 * @return unknown_type
	 */
	function saveOrderItems($values)
	{
		$order= $this->_order;
		$order_id = $values['order_id'];
		//review things once again
		$cart_helper = new J2StoreHelperCart();
		$cart_model = $this->getModel('mycart');
//		$reviewitems = $cart_helper->getProductsInfo();

	//	foreach ($reviewitems as $reviewitem)
	//	{
	//		$order->addItem( $reviewitem );
	//	}

		$order->order_state_id = $this->initial_order_state;
		$order->calculateTotals();


		$items = $order->getItems();

		if (empty($items) || !is_array($items))
		{
			$this->setError( "saveOrderItems:: ".JText::_( "J2STORE_ORDER_SAVE_INVALID_ITEMS" ) );
			return false;
		}

		$error = false;
		$errorMsg = "";
		foreach ($items as $item)
		{
			$item->order_id = $order_id;

			if (!$item->save())
			{
				// track error
				$error = true;
				$errorMsg .= $item->getError();
			}
			else
			{
				// Save the attributes also
				if (!empty($item->orderitem_attributes))
				{
					//$attributes = explode(',', $item->orderitem_attributes);
					//first we got to convert the JSON-structured attribute options into an object
					$registry = new JRegistry;
					$registry->loadString($item->orderitem_attributes, 'JSON');
					$product_options = $registry->toObject();

					foreach ($product_options as $attribute)
					{
						unset($productattribute);
						unset($orderitemattribute);
						//we first have to load the product options table to get the data. Just for a cross check
						//TODO do we need this? the mycart model already has the data and we mapped it to orderitem_attributes in JSON format.
						$productattribute = $cart_model->getCartProductOptions($attribute->product_option_id, $item->product_id);
						$orderitemattribute = JTable::getInstance('OrderItemAttributes', 'Table');
						$orderitemattribute->orderitem_id = $item->orderitem_id;
						//this is the product option id
						$orderitemattribute->productattributeoption_id = $productattribute->product_option_id;
						$orderitemattribute->productattributeoptionvalue_id = $attribute->product_optionvalue_id;
						//product option name. Dont confuse this with the option value name
						$orderitemattribute->orderitemattribute_name = $productattribute->option_name;
						$orderitemattribute->orderitemattribute_value = $attribute->option_value;
						//option price
						$orderitemattribute->orderitemattribute_price = $attribute->price;
						//$orderitemattribute->orderitemattribute_code = $productattribute->productattributeoption_code;
						$orderitemattribute->orderitemattribute_prefix = $attribute->price_prefix;
						$orderitemattribute->orderitemattribute_type = $attribute->type;
						if (!$orderitemattribute->save())
						{
							// track error
							$error = true;
							$errorMsg .= $orderitemattribute->getError();
						}

					}
				}
			}
		}

		if ($error)
		{

			$this->setError( $errorMsg );
			return false;
		}
		return true;
	}

	function saveOrderFiles($values){

		$db		= JFactory::getDbo();
		$query	= $db->getQuery(true);
		$user = JFactory::getUser();

		$query->select('pf.productfile_id, oi.orderitem_id');
		$query->from('#__j2store_orderitems AS oi');
		$query->where('oi.order_id = '.$values['order_id']);
		$query->join('LEFT', '`#__j2store_productfiles` AS pf ON pf.product_id = oi.product_id');
		$db->setQuery($query);
		$file_items = $db->loadObjectList();

		if(count($file_items)) {
			foreach ($file_items as $file) {
				if($file->productfile_id) {
					unset($row);
					$row = JTable::getInstance('orderfiles','Table');
					$row->orderitem_id=$file->orderitem_id;
					$row->productfile_id=$file->productfile_id;
					$row->limit_count=0;
					$row->user_id=$user->id ;
					$row->user_email=$values['user_email'];

					if ( !$row->save() )
					{
						$messagetype = 'notice';
						$message = JText::_( 'J2STORE_ERROR_SAVING_FILES_FAILED' )." - ".$row->getError();
						$this->setError( $message );
						return false;
					}
				}
			}
		}
		return true;
	}


	function saveOrderInfo($values){

		$row = JTable::getInstance('orderinfo','Table');

		if (!$row->bind($values)) {
			throw new Exception($row->getError());
			return false;
		}

		if (!$row->check()) {
			throw new Exception($row->getError());
			return false;
		}

		if (!$row->store()) {
			throw new Exception($row->getError());
			return false;
		}

		return true;
	}


	 function saveOrderShippings( $values )
    	{
        $order = $this->_order;

        $shipping_type = isset($values['shipping_plugin']) ? $values['shipping_plugin'] : '';
		if(!empty($shipping_type)) {
	        $row = JTable::getInstance('OrderShippings', 'Table');
	        $row->order_id = $order->id;
	        $row->ordershipping_type = $values['shipping_plugin'];
	        $row->ordershipping_price = $values['shipping_price'];
	        $row->ordershipping_name = $values['shipping_name'];
	        $row->ordershipping_code = $values['shipping_code'];
	        $row->ordershipping_tax = $values['shipping_tax'];
	        $row->ordershipping_extra = $values['shipping_extra'];

	        if (!$row->save($row))
	        {
	            $this->setError( $row->getError() );
	            return false;
	        }

	        // Let the plugin store the information about the shipping
	        if (isset($values['shipping_plugin']))
	        {
	            $dispatcher = JDispatcher::getInstance();
	            $dispatcher->trigger( "onJ2StorePostSaveShipping", array( $values['shipping_plugin'], $row ) );
	        }
		}

        return true;
    }


	function validateSelectPayment($payment_plugin, $values) {

		$response = array();
		$response['msg'] = '';
		$response['error'] = '';

		$dispatcher    = JDispatcher::getInstance();
		JPluginHelper::importPlugin ('j2store');

		//verify the form data
		$results = array();
		$results = $dispatcher->trigger( "onJ2StoreGetPaymentFormVerify", array( $payment_plugin, $values) );

		for ($i=0; $i<count($results); $i++)
		{
			$result = $results[$i];
			if (!empty($result->error))
			{
				$response['msg'] =  $result->message;
				$response['error'] = '1';
			}

		}
		if($response['error']) {
			throw new Exception($response['msg']);
			return false;
		} else {
			return true;
		}
		return false;
	}


	function validateSelectShipping($values) {

		$error = 0;

		if (isset($values['shippingrequired']))
		{
			if ($values['shippingrequired'] == 1 && empty($values['shipping_plugin']))
			{
				throw new Exception(JText::_('J2STORE_CHECKOUT_SELECT_A_SHIPPING_METHOD'));
				return false;
			}
		}

		//if order value is zero, then return true
		$order = $this->_order;

		// get the items and add them to the order
		$items = J2StoreHelperCart::getProducts();
		foreach ($items as $item)
		{
			$order->addItem( $item );
		}
		$order->calculateTotals();
		if ( (float) $order->order_total == (float) '0.00' )
		{
			return true;
		}

		//trigger the plugin's validation function
		// no matter what, fire this validation plugin event for plugins that extend the checkout workflow
		$results = array();
		$dispatcher = JDispatcher::getInstance();
		$results = $dispatcher->trigger( "onValidateSelectShipping", array( $values ) );

		for ($i=0; $i<count($results); $i++)
		{
			$result = $results[$i];
			if (!empty($result->error))
			{
				throw new Exception($result->message);
				return false;
			}

		}

		 if($error == '1')
        {
            return false;
        }

        return true;
	}


	/**
	 * This method occurs after payment is attempted,
	 * and fires the onPostPayment plugin event
	 *
	 * @return unknown_type
	 */
	function confirmPayment()
	{
		$app =JFactory::getApplication();
		$orderpayment_type = $app->input->getString('orderpayment_type');

		// Get post values
		$values = $app->input->getArray($_POST);
		//backward compatibility for payment plugins
		foreach($values as $name=>$value) {
			$app->input->set($name, $value);
		}

		//set the guest mail to null if it is present
		//check if it was a guest checkout
		$account = $this->session->get('account', 'register', 'j2store');

		// get the order_id from the session set by the prePayment
		$orderpayment_id = (int) $app->getUserState( 'j2store.orderpayment_id' );
		if($account != 'guest') {
			$order_link = 'index.php?option=com_j2store&view=orders&task=view&id='.$orderpayment_id;
		} else {
			$guest_token  = $app->getUserState( 'j2store.order_token' );
			$order_link = 'index.php?option=com_j2store&view=orders&task=view';

			//assign to another session variable, for security reasons
			if($this->session->has('guest', 'j2store')) {
				$guest = $this->session->get('guest', array(), 'j2store');
				$this->session->set('guest_order_email', $guest['billing']['email']);
				$this->session->set('guest_order_token', $guest_token);
			}
		}

		$dispatcher = JDispatcher::getInstance();
		JPluginHelper::importPlugin ('j2store');

		$html = "";
		$order= $this->_order;
		$order->load( array('id'=>$orderpayment_id));

		// free product? set the state to confirmed and save the order.
		if ( (!empty($orderpayment_id)) && (float) $order->order_total == (float)'0.00' )
		{
			$order->order_state = trim(JText::_('CONFIRMED'));
			$order->order_state_id = '1'; // PAYMENT RECEIVED.
			if($order->save()) {
				// remove items from cart
				J2StoreHelperCart::removeOrderItems( $order->id );
			}
			//send email
			require_once (JPATH_SITE.'/components/com_j2store/helpers/orders.php');
			J2StoreOrdersHelper::sendUserEmail($order->user_id, $order->order_id, $order->transaction_status, $order->order_state, $order->order_state_id);

		}
		else
		{
			// get the payment results from the payment plugin
			$results = $dispatcher->trigger( "onJ2StorePostPayment", array( $orderpayment_type, $values ) );

			// Display whatever comes back from Payment Plugin for the onPrePayment
			for ($i=0; $i<count($results); $i++)
			{
				$html .= $results[$i];
			}

			// re-load the order in case the payment plugin updated it
			$order->load( array('id'=>$orderpayment_id) );
		}

		// $order_id would be empty on posts back from Paypal, for example
		if (isset($orderpayment_id))
		{

			//unset a few things from the session.
			$this->session->clear('shipping_method', 'j2store');
			$this->session->clear('shipping_methods', 'j2store');
			$this->session->clear('payment_method', 'j2store');
			$this->session->clear('payment_methods', 'j2store');
			$this->session->clear('payment_values', 'j2store');
			$this->session->clear('guest', 'j2store');
			$this->session->clear('customer_note', 'j2store');

			//save the coupon to the order_coupons table for tracking and unset session.
			if($this->session->has('coupon', 'j2store')) {
					$coupon_info = J2StoreHelperCart::getCoupon($this->session->get('coupon', '', 'j2store'));
					if($coupon_info) {
						$order_coupons = JTable::getInstance('OrderCoupons', 'Table');
						$order_coupons->set('coupon_id', $coupon_info->coupon_id);
						$order_coupons->set('orderpayment_id', $orderpayment_id);
						$order_coupons->set('customer_id', JFactory::getUser()->id);
						$order_coupons->set('amount', $order->order_discount);
						$order_coupons->set('created_date', JFactory::getDate()->toSql());
						$order_coupons->store();
					}
			}

			//clear the session
			$this->session->clear('coupon', 'j2store');


			// Set display
			$view = $this->getView( 'checkout', 'html' );
			$view->setLayout('postpayment');
			$view->set( '_doTask', true);
			$view->assign('order_link', JRoute::_($order_link) );
			$view->assign('plugin_html', $html);

			// Get and Set Model
			$model = $this->getModel('checkout');
			$view->setModel( $model, true );

			$view->display();
		}
		return;
	}

	public function getCountry() {
		$app = JFactory::getApplication();
		$model = $this->getModel('checkout');
		$country_id =$app->input->get('country_id');
		$json = array();
		$country_info = $model->getCountryById($country_id);
		if ($country_info) {
		$zones = $this->getModel('checkout')->getZonesByCountryId($app->input->get('country_id'));

			$json = array(
					'country_id'        => $country_info->country_id,
					'name'              => $country_info->country_name,
					'iso_code_2'        => $country_info->country_isocode_2,
					'iso_code_3'        => $country_info->country_isocode_3,
					'zone'              => $zones
			);
		}

		echo json_encode($json);
		$app->close();
	}

	public function getTerms() {

		$app = JFactory::getApplication();
		$id = $app->input->getInt('article_id');
		require_once (JPATH_COMPONENT_ADMINISTRATOR.'/library/j2item.php' );
		$j2item = new J2StoreItem();
		$data = $j2item->display($id);
		$view = $this->getView( 'checkout', 'html' );
		$view->set( '_controller', 'checkout' );
		$view->set( '_view', 'checkout' );
		$view->set( '_doTask', true);
		$view->set( 'hidemenu', true);
		$view->assign( 'html', $data);
		$view->setLayout( 'checkout_terms' );
		ob_start();
		$view->display();
		$html = ob_get_contents();
		ob_end_clean();
		echo $html;
		$app->close();
	}


}
