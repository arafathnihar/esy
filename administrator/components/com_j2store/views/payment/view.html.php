<?php
/** ensure this file is being included by a parent file */
defined('_JEXEC') or die('Restricted access');

class J2StoreViewPayment extends J2StoreView
{

	function display($tpl = null) {

		$mainframe = JFactory::getApplication();
		$option = 'com_j2store';
		$ns = 'com_j2store.payment';
		$task = $mainframe->input->getCmd('task');
		$session = JFactory::getSession();

			$db		=JFactory::getDBO();
			$uri	=JFactory::getURI();
			$params = JComponentHelper::getParams('com_j2store');

			$filter_order		= $mainframe->getUserStateFromRequest( $ns.'filter_order',		'filter_order',		'tbl.id',	'cmd' );
			$filter_order_Dir	= $mainframe->getUserStateFromRequest( $ns.'filter_order_Dir',	'filter_order_Dir',	'',				'word' );
			$filter_orderstate	= $mainframe->getUserStateFromRequest( $ns.'filter_orderstate',	'filter_orderstate',	'', 'string' );
			$filter_name = $mainframe->getUserStateFromRequest( $ns.'filter_name',		'filter_name',		'tbl.name',	'cmd' );

			$search				= $mainframe->getUserStateFromRequest( $ns.'search',			'search',			'',				'string' );
			if (strpos($search, '"') !== false) {
				$search = str_replace(array('=', '<'), '', $search);
			}
			$search = JString::strtolower($search);

			$model = $this->getModel('payment');
			// Get data from the model
			$items		=  $model->getList();
			$total		=  $model->getTotal();
			$pagination =  $model->getPagination();

			// table ordering
			$lists['order_Dir'] = $filter_order_Dir;
			$lists['order'] = $filter_order;
			$lists['filter_name'] = $filter_name;

			$update = array();

			//only call once per session. Dont call this often
			if(!$session->has('plugin_update_data', 'j2store')) {
				$xmlfile = 'http://cdn.j2store.org/extensions.xml';
				$extensions = simplexml_load_file($xmlfile, 'SimpleXMLElement');
				$type = (string) $extensions->extension->attributes()->type;
				if($type == 'payment') {
					$plugins = $extensions->extension->plugins->plugin;
					foreach ($plugins as $plugin) {
						$update[(string) $plugin->attributes()->element] = (array)$plugin;
					}
				}
				$session->set('plugin_update_data', $update, 'j2store' );

			} else {
				$update = $session->get('plugin_update_data', array(), 'j2store' );
			}

			$this->assignRef('update',		$update);

			// search filter
			$lists['search']= $search;

			$this->assignRef('lists',		$lists);
			$this->assignRef('items',		$items);
			$this->assignRef('pagination',	$pagination);

			JToolBarHelper::title(JText::_('J2STORE_PAYMENT_PLUGINS'),'j2store-logo');
			$toolbar = new J2StoreToolBar();
			$toolbar->renderLinkbar();

			parent::display($tpl);
	}

}
