<?php

/** ensure this file is being included by a parent file */
defined( '_JEXEC' ) or die( 'Restricted access' );

JLoader::register( 'J2StoreTable', JPATH_ADMINISTRATOR.'/components/com_j2store/tables/_base.php' );
class TableShippingRates extends J2StoreTable {

	function TableShippingRates ( $db )
	{
        $tbl_key    = 'shipping_rate_id';
        $tbl_suffix = 'shippingrates';
        $this->set( '_suffix', $tbl_suffix );
        $name       = 'j2store';

        parent::__construct( "#__{$name}_{$tbl_suffix}", $tbl_key, $db );
	}

	/**
	 * Checks row for data integrity.
	 * Assumes working dates have been converted to local time for display,
	 * so will always convert working dates to GMT
	 *
	 * @return unknown_type
	 */
	function check()
	{
       // if (empty($this->shipping_method_id))
       // {
       //     $this->setError( JText::_('J2STORE_SHIPPING_METHOD_REQUIRED') );
       //     return false;
       // }
		return true;
	}
}