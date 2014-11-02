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
defined( '_JEXEC' ) or die( 'Restricted access' );

JLoader::register( 'J2StoreTable', JPATH_ADMINISTRATOR.DS.'components'.DS.'com_j2store'.DS.'tables'.DS.'_base.php' );

class TablePrices extends J2StoreTable
{
	function TablePrices ( &$db )
	{
		parent::__construct('#__j2store_prices', 'price_id', $db );
	}

}
