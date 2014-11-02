<?php
// No direct access to this file
defined('_JEXEC') or die('Restricted access');

// import Joomla view library
jimport('joomla.application.component.view');

/**
 * HTML View class for the HelloWorld Component
 */
class J2StoreViewCoupons extends J2StoreView
{

function display($tpl = null) {

		//add toolbar
		$this->addToolBar();
		$toolbar = new J2StoreToolBar();
		$toolbar->renderLinkbar();
		parent::display($tpl);
	}

	function addToolBar() {
		JToolBarHelper::title(JText::_('J2STORE_COUPONS'),'j2store-logo');
	}
}
