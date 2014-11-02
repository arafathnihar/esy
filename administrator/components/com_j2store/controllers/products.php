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

class J2StoreControllerProducts extends J2StoreController {

	function __construct()
	{
		parent::__construct();

	}


	//product attributie option extra
	function setpaimport()
	{
		$app = JFactory::getApplication();
		$model = $this->getModel('paimport');
		$ns = 'com_j2store.paimport';
		$filter_order		= $app->getUserStateFromRequest( $ns.'filter_order',		'filter_order',		'p.ordering',	'cmd' );
		$filter_order_Dir	= $app->getUserStateFromRequest( $ns.'filter_order_Dir',	'filter_order_Dir',	'',				'word' );

		// table ordering
		$lists['order_Dir'] = $filter_order_Dir;
		$lists['order'] = $filter_order;

		$product_id = JRequest::getVar('id', 0, 'get', 'int');
		$row = JTable::getInstance('content','JTable');
		$row->load($model->getId());

		$items = $model->getData();
		$total		= $model->getTotal();
		$pagination = $model->getPagination();

		$view   = $this->getView( 'paimport', 'html' );
		$view->set( '_controller', 'products' );
		$view->set( '_view', 'products' );
		$view->set( '_action', "index.php?option=com_j2store&view=products&task=setpaimport&tmpl=component&id=".$model->getId());
		$view->setModel( $model, true );
		$view->assign('model', $model);
		$view->assign( 'state', $model->getState() );
		$view->assign( 'row', $row);
		$view->assign( 'items', $items );
		$view->assign( 'total', $total );
		$view->assign( 'lists', $lists );
		$view->assign( 'pagination', $pagination );
		$view->assign('product_id', $product_id);
		$view->setLayout( 'default' );
		$view->display();
	}

	function importattributes() {
		$app = JFactory::getApplication();
		$error = false;
		$this->messagetype  = '';
		$this->message      = '';
		$cids = JRequest::getVar('cid', array(), 'request', 'array');
		$product_id = JRequest::getVar('id', 0, 'post', 'int');
		if(empty($cids) || count($cids) < 1) {
			$error = true;
			$this->message .= JText::_('J2STORE_PAI_SELECT_PRODUCT_TO_IMPORT');
			$this->messagetype = 'notice';
		} else {

			//get the model
			$model = $this->getModel('paimport');
			foreach($cids as $cid) {
				if(!$model->importAttributeFromProduct($cid, $product_id)){
					$this->message .= $model->getError();
					$this->messagetype = 'error';
				}
			}

		}
		if ($error)
		{
			$this->message = JText::_('J2STORE_ERROR') . " - " . $this->message;
		}
		else
		{
			$this->message = JText::_('J2STORE_PAI_SELECT_ATTRIBUTES_IMPORTED');
			$this->messageType = 'message';
		}

		$redirect = "index.php?option=com_j2store&view=products&task=setpaimport&id={$product_id}&tmpl=component";
		$redirect = JRoute::_( $redirect, false );

		$this->setRedirect( $redirect, $this->message, $this->messagetype );

	}

	/*
	 * PA options section
	 */

	function createproductoptionvalue() {
		$app = JFactory::getApplication();
		$model  = $this->getModel( 'productoptionvalues' );
		$row = $model->getTable();
		$row->product_option_id = $app->input->getInt( 'product_option_id' );
		$row->option_id = $app->input->getInt( 'option_id' );
		$row->product_id = $app->input->getInt( 'product_id' );
		$row->optionvalue_id = $app->input->getInt( 'productoptionvalue_id' );
		$row->product_optionvalue_price = $app->input->get( 'product_optionvalue_price');
		$row->product_optionvalue_prefix = JFactory::getDbo()->escape($app->input->getString( 'product_optionvalue_prefix'));
		$row->product_optionvalue_weight = $app->input->get( 'product_optionvalue_weight');
		$row->product_optionvalue_weight_prefix = JFactory::getDbo()->escape($app->input->getString( 'product_optionvalue_weight_prefix'));

		$row->ordering = '99';
		//  $post=JRequest::get('post');

		if ( !$row->save() )
		{
			$messagetype = 'notice';
			$message = JText::_( 'J2STORE_SAVE_FAILED' )." - ".$row->getError();
		}

		$redirect = "index.php?option=com_j2store&view=products&task=setproductoptionvalues&product_option_id={$row->product_option_id}&tmpl=component";
		$redirect = JRoute::_( $redirect, false );

		$this->setRedirect( $redirect, $message, $messagetype );

	}


	function setproductoptionvalues()
	{
		$app = JFactory::getApplication();
		$model = $this->getModel('productoptionvalues');
		$ns = 'com_j2store.productoptionvalues';

		$filter_order		= $app->getUserStateFromRequest( $ns.'filter_order',		'filter_order',		'a.ordering',	'cmd' );
		$filter_order_Dir	= $app->getUserStateFromRequest( $ns.'filter_order_Dir',	'filter_order_Dir',	'',				'word' );

		// table ordering
		$lists['order_Dir'] = $filter_order_Dir;
		$lists['order'] = $filter_order;

		$product_option_id = $app->input->getInt('product_option_id', 0);

		//load the product options table joining general options tables
		$product_options = $model->getProductOptions();

		//load the general option values
		$lists['option_values'] = null;
		$option_values = array();
		$option_values = $model->getOptionValues($product_options->option_id);
		if(count($option_values)) {
			foreach($option_values as $option_value) {
				$options[] = JHtml::_('select.option', $option_value->optionvalue_id, $option_value->optionvalue_name);
			}
			$attribs = array('class' => 'inputbox', 'size'=>'1', 'title'=>JText::_('J2STORE_SELECT_AN_OPTION'));
			$lists['option_values'] = JHtml::_('select.genericlist', $options, 'productoptionvalue_id', $attribs, 'value', 'text', '', 'productoptionvalue_id');
		}

		$items = $model->getData();
		$total		= $model->getTotal();
		$pagination = $model->getPagination();

		$view   = $this->getView( 'productoptionvalues', 'html' );
		$view->set( '_controller', 'products' );
		$view->set( '_view', 'products' );
		$view->set( '_action', "index.php?option=com_j2store&view=products&task=setproductoptionvalues&tmpl=component&product_option_id=".$product_option_id);
		$view->setModel( $model, true );
		$view->assign( 'state', $model->getState() );
		$view->assign( 'row', $product_options );
		$view->assign( 'items', $items );
		$view->assign( 'total', $total );
		$view->assign( 'lists', $lists );
		$view->assign( 'pagination', $pagination );
		$view->assign( 'product_option_id', $product_option_id);
		$view->setLayout( 'default' );
		$view->display();
	}


	function saveproductoptionvalues()
	{
		$error = false;
		$this->messageType  = '';
		$this->message      = '';
		$app = JFactory::getApplication();
		$model = $this->getModel('productoptionvalues');
		$row = $model->getTable();

		$product_option_id = $app->input->getInt('product_option_id', 0);
		$product_id = $app->input->getInt('product_id', 0);
		$option_id = $app->input->getInt('option_id', 0);
		$redirect = "index.php?option=com_j2store&view=products&task=setproductoptionvalues&product_option_id={$product_option_id}&tmpl=component";
		if(!$product_option_id || !$product_id || !$option_id ) {
			$this->messageType  = 'notice';
			$this->message      = JText::_('J2STORE_OPTIONVALUES_MISSING_VALUES');
			$app->redirect($redirect);
		}

		$cids = $app->input->post->get('cid', array(0), 'ARRAY');
		$optionvalue_ids = $app->input->post->get('optionvalue_id', array(0), 'ARRAY');
		$prefix = $app->input->post->get('prefix', array(0), 'ARRAY');
		$price = $app->input->post->get('price', array(0), 'ARRAY');
		$weight_prefix = $app->input->post->get('weight_prefix', array(0), 'ARRAY');
		$weight = $app->input->post->get('weight', array(0), 'ARRAY');

		$ordering = $app->input->post->get('ordering', array(0), 'ARRAY');

		foreach (@$cids as $cid)
		{
			$row->load( $cid );
			$row->optionvalue_id = $optionvalue_ids[$cid];
			$row->product_optionvalue_prefix = $prefix[$cid];
			$row->product_optionvalue_price = $price[$cid];
			$row->product_optionvalue_weight_prefix = $weight_prefix[$cid];
			$row->product_optionvalue_weight = $weight[$cid];
			$row->ordering = $ordering[$cid];

			if (!$row->check() || !$row->store())
			{
				$this->message .= $row->getError();
				$this->messagetype = 'notice';
				$error = true;
			}
		}
		$row->reorder();

		if ($error)
		{
			$this->message = JText::_('J2STORE_ERROR') . " - " . $this->message;
		}
		else
		{
			$this->message = "";
		}

		$redirect = JRoute::_( $redirect, false );

		$this->setRedirect( $redirect, $this->message, $this->messagetype );
	}


	function deleteproductoptionvalues()
	{
		$app = JFactory::getApplication();
		$error = false;
		$this->messagetype	= '';
		$this->message 		= '';
		$product_option_id = $app->input->getInt('po_id');
		if (!isset($this->redirect)) {
			$this->redirect = $app->input->getString('return' )
			? base64_decode( $app->input->getString( 'return' ) )
			: 'index.php?option=com_j2store&view=products&task=setproductoptionvalues&id='.$product_option_id.'&tmpl=component';
			$this->redirect = JRoute::_( $this->redirect, false );
		}

		$model = $this->getModel('productoptionvalues');
		$row = $model->getTable();

		$cids = $app->input->get('cid', array (0),'ARRAY');
		foreach (@$cids as $cid)
		{
			if (!$row->delete($cid))
			{
				$this->message .= $row->getError();
				$this->messagetype = 'notice';
				$error = true;
			}
		}

		if ($error)
		{
			$this->message = JText::_('J2STORE_ERROR') . " - " . $this->message;
		}
		else
		{
			$this->message = JText::_('J2STORE_ITEMS_DELETED');
		}

		$this->setRedirect( $this->redirect, $this->message, $this->messagetype );
	}

	public static function removeProductOption() {

		$app = JFactory::getApplication();
		JTable::addIncludePath(JPATH_ADMINISTRATOR.'/components/com_j2store/tables');
		$result = array();
		$id = $app->input->post->get('pao_id');

		//first remove option values associated with this
		$db = JFactory::getDbo();

		$table =  JTable::getInstance('ProductOptions', 'Table');
		if($table->delete($id)){
			$result['success'] = 1;

			//now remove option values associated with this
			$query = $db->getQuery(true);
			$query->delete('#__j2store_product_optionvalues')->where('product_option_id='.$id);
			try {
				$db->query();
			} catch (Exception $e) {
				//failed... dont worry about it
			}

		} else {
			$result['success'] = 0;
		}

		echo json_encode($result);
		$app->close();
	}


	/*
	 *  product files
	* */

	function setfiles()
	{

		$app = JFactory::getApplication();
		$model = $this->getModel('productfiles');
		$context = 'com_j2store.productfiles';
		$filter_order		= $app->getUserStateFromRequest( $context.'filter_order',		'filter_order',		'a.ordering',	'cmd' );
		$filter_order_Dir	= $app->getUserStateFromRequest( $context.'filter_order_Dir',	'filter_order_Dir',	'',				'word' );

		// table ordering
		$lists['order_Dir'] = $filter_order_Dir;
		$lists['order'] = $filter_order;

		$total		= $model->getTotal();
		$pagination = $model->getPagination();

		//$id = JRequest::getVar('id', 0, 'get', 'int');
		$id = JRequest::getVar('id');

		//set states
		$model->setState('product.id',$id);

		// get items from the table
		$items = $model->getItems();
		$row = JTable::getInstance('content','JTable');
		$row->load($id);

		$files	 = $model->getFiles();
		$error = $model->getError();

		$view   = $this->getView( 'productfiles', 'html' );
		$view->set( '_controller', 'products' );
		$view->set( '_view', 'products' );
		$view->set( '_action', "index.php?option=com_j2store&view=products&task=setfiles&tmpl=component&id=".$id);
		$view->setModel( $model, true );
		$view->assign( 'state', $model->getState() );
		$view->assign( 'row', $row );
		$view->assign( 'items', $items );
		$view->assign( 'files', $files );
		$view->assign( 'lists', $lists ); // for pagination (footer)
		$view->assign( 'error', $error);
		$view->assign( 'total', $total);
		$view->assign( 'pagination', $pagination);
		$view->assign( 'product_id', $id );
		//$view->assign( 'product_id', $id );

		$view->setLayout( 'default' );
		$view->display();
	}

	function savefiles()
	{

		$error = false;
		$this->messagetype  = '';
		$this->message      = '';

		$model = $this->getModel('productfiles');
		$row = $model->getTable();

		$id = JRequest::getVar('id', 0, 'get', 'int');
		$cids = JRequest::getVar('cid', array(0), 'request', 'array');
		$file_disp_name = JRequest::getVar('product_file_display_name', array(0), 'request', 'array');
		$purchase_required = JRequest::getVar('product_file_purchase_required', array(0), 'request', 'array');
		$state = JRequest::getVar('product_file_state', array(0), 'request', 'array');
		$download_limit = JRequest::getVar('product_file_download_limit', array(0), 'request', 'array');
		$ordering = JRequest::getVar('product_file_ordering', array(0), 'request', 'array');

		foreach ($cids as $cid)
		{
			$row->load( $cid );
			$row->product_file_display_name = $file_disp_name[$cid];
			$row->purchase_required = $purchase_required[$cid];
			$row->state = $state[$cid];
			$row->download_limit = $download_limit[$cid];
			$row->ordering = $ordering[$cid];

			if (!$row->check() || !$row->store())
			{
				$this->message .= $row->getError();
				$this->messagetype = 'notice';
				$error = true;
			}
		}
		$row->reorder();

		if ($error)
		{
			$this->message = JText::_('Error') . " - " . $this->message;
		}
		else
		{
			$this->message = JText::_("Changes saved successfully");
		}

		$redirect = "index.php?option=com_j2store&view=products&task=setfiles&id=".$id."&tmpl=component";
		$redirect = JRoute::_( $redirect, false );

		$this->setRedirect( $redirect, $this->message, $this->messagetype );
	}


	function createfile() {

		$model  = $this->getModel( 'productfiles' );
		$row = $model->getTable();
		$row->product_id = JRequest::getVar( 'id' );
		$id=$row->product_id ;
		$file = JRequest::getVar('savename', array(), 'files', 'array');

		//get display name or use he file's original name
		$display_file_name = JRequest::getVar( 'displayname' );
		$row->product_file_save_name = $file_name = $file['name'];
		$row->product_file_display_name = (!empty($display_file_name))?$display_file_name: $file_name;
		$row->purchase_required = JRequest::getVar( 'purchase_required' );
		$row->state = JRequest::getVar( 'state' );
		$row->download_limit = JRequest::getVar( 'download_limit' );
		$row->ordering = '99';

		//  $post=JRequest::get('post');
		if (!$model->saveFile($file)){
			$messagetype = 'notice';
			$message = JText::_( 'File Save Failed' )." - ".$model->getError();
		} else {

			if ( !$row->save())
			{
				$messagetype = 'notice';
				$message = JText::_( 'Save Failed' )." - ".$row->getError();
			}

		}

		$redirect = "index.php?option=com_j2store&view=products&task=setfiles&id={$row->product_id}&tmpl=component";
		$redirect = JRoute::_( $redirect, false );

		$this->setRedirect( $redirect, $message, $messagetype );

	}

	function deletefile()
	{
		$app = JFactory::getApplication();
		$model = $this->getModel('productfiles');
		$user	= JFactory::getUser();

		// Deletes the selected rows from the table
		$error = false;
		$this->messagetype	= '';
		$this->message 		= '';
		$id = JRequest::getVar( 'id' );
		if (!isset($this->redirect)) {
			$this->redirect = JRequest::getVar( 'return' )
			? base64_decode( JRequest::getVar( 'return' ) )
			: 'index.php?option=com_j2store&view=products&task=setfiles&id='.$id.'&tmpl=component';
			$this->redirect = JRoute::_( $this->redirect, false );
		}

		$model = $this->getModel('productfiles');
		$row = $model->getTable();

		$cids = JRequest::getVar('cid', array (0), 'request', 'array');

		foreach (@$cids as $cid)
		{
			//code to delete the file from disk	 (call to model)
			//get the image name and call delete from model

			if($row->load($cid))
				$file_name = $row->product_file_save_name;

			if ( ! $model->deleteFile($file_name) )
			{
				$this->message = JText::_( 'Delete Failed' )." - ".$model->getError();
				$this->messagetype = 'error';
			}

			if (!$row->delete($cid))
			{
				$this->message .= $row->getError();
				$this->messagetype = 'error';
				$error = true;
			}
		}

		if ($error)
		{
			$this->message = JText::_('Error') . " - " . $this->message;
		}
		else
		{
			$this->message = JText::_('Items Deleted');
			$this->messagetype = 'notice';
		}


		$redirect = "index.php?option=com_j2store&view=products&task=setfiles&id={$id}&tmpl=component";
		$redirect = JRoute::_( $redirect, false );
		$this->setRedirect( $redirect, $this->message, $this->messagetype );
	}



	/*
	 *
	* Price range */

	/*-------------------------------------------------------------------------------*/

	function createpricerange() {
		$app = JFactory::getApplication();
		$model  = $this->getModel( 'productprices' );
		$row = $model->getTable();
		$row->product_id = $app->input->getInt( 'id' );
		$row->quantity_start = $app->input->getString( 'pricerange_quantity_start' );
		$row->condition = $app->input->getString( 'pricerange_condition', 'above' );
		$row->price = $app->input->get( 'pricerange_price', 0 );
		$row->ordering = '99';

		//$post=JRequest::get('post');

		if ( !$row->save() )
		{
			$messagetype = 'notice';
			$message = JText::_( 'J2STORE_SAVE_FAILED' )." - ".$row->getError();
		}

		$redirect = "index.php?option=com_j2store&view=products&task=setpricerange&id={$row->product_id}&tmpl=component";
		$redirect = JRoute::_( $redirect, false );

		$this->setRedirect( $redirect, $message, $messagetype );

	}


	function setpricerange()
	{
		$app = JFactory::getApplication();
		$model = $this->getModel('productprices');
		$ns = 'com_j2store.productprices';

		$filter_order		= $app->getUserStateFromRequest( $ns.'filter_order',		'filter_order',		'a.ordering',	'cmd' );
		$filter_order_Dir	= $app->getUserStateFromRequest( $ns.'filter_order_Dir',	'filter_order_Dir',	'',				'word' );

		// table ordering
		$lists['order_Dir'] = $filter_order_Dir;
		$lists['order'] = $filter_order;

		$id = $app->input->getInt('id', 0);
		$row = JTable::getInstance('content','JTable');
		$row->load($id);

		$items = $model->getData();
		$total		=  $model->getTotal();
		$pagination =  $model->getPagination();

		$view   = $this->getView( 'productprices', 'html' );
		$view->set( '_controller', 'products' );
		$view->set( '_view', 'products' );
		$view->set( '_action', "index.php?option=com_j2store&view=products&task=setpricerange&tmpl=component&id=".$id);
		$view->setModel( $model, true );
		$view->assign( 'state', $model->getState() );
		$view->assign( 'row', $row );
		$view->assign( 'items', $items );
		$view->assign( 'total', $total );
		$view->assign( 'lists', $lists );
		$view->assign( 'pagination', $pagination );
		$view->assign( 'product_id', $id );
		$view->setLayout( 'default' );
		$view->display();
	}


	function savepricerange()
	{
		$error = false;
		$this->messagetype  = '';
		$this->message      = '';
		$app = JFactory::getApplication();
		$model = $this->getModel('productprices');
		$row = $model->getTable();

		$id = $app->input->getInt('id', 0);
		$cids = $app->input->post->get('cid', array(0), 'ARRAY');
		$quantity_start = $app->input->post->get('quantity_start', array(0), 'ARRAY');
		$condition = $app->input->post->get('condition', array(0), 'ARRAY');
		$price = $app->input->post->get('price', array(0), 'ARRAY');
		$ordering = $app->input->post->get('ordering', array(0), 'ARRAY');

		foreach (@$cids as $cid)
		{
			$row->load( $cid );
			$row->quantity_start = $quantity_start[$cid];
			$row->condition = $condition[$cid];
			$row->price = $price[$cid];
			$row->ordering = $ordering[$cid];

			if (!$row->check() || !$row->store())
			{
				$this->message .= $row->getError();
				$this->messagetype = 'notice';
				$error = true;
			}
		}
		$row->reorder();

		if ($error)
		{
			$this->message = JText::_('J2STORE_ERROR') . " - " . $this->message;
		}
		else
		{
			$this->message = "";
		}

		$redirect = "index.php?option=com_j2store&view=products&task=setpricerange&id={$id}&tmpl=component";
		$redirect = JRoute::_( $redirect, false );

		$this->setRedirect( $redirect, $this->message, $this->messagetype );
	}


	function deletepricerange()
	{
		$app = JFactory::getApplication();
		$error = false;
		$this->messagetype	= '';
		$this->message 		= '';
		$product_id = $app->input->getInt('product_id');
		if (!isset($this->redirect)) {
			$this->redirect = JRequest::getVar( 'return' )
			? base64_decode( JRequest::getVar( 'return' ) )
			: 'index.php?option=com_j2store&view=products&task=setpricerange&id='.$product_id.'&tmpl=component';
			$this->redirect = JRoute::_( $this->redirect, false );
		}

		$model = $this->getModel('productprices');
		$row = $model->getTable();

		$cids = JRequest::getVar('cid', array (0), 'request', 'array');
		foreach (@$cids as $cid)
		{
			if (!$row->delete($cid))
			{
				$this->message .= $row->getError();
				$this->messagetype = 'notice';
				$error = true;
			}
		}

		if ($error)
		{
			$this->message = JText::_('J2STORE_ERROR') . " - " . $this->message;
		}
		else
		{
			$this->message = JText::_('J2STORE_ITEMS_DELETED');
		}

		$this->setRedirect( $this->redirect, $this->message, $this->messagetype );
	}







}
