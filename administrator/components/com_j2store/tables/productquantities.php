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


/** ensure this file is being included by a parent file */
defined( '_JEXEC' ) or die( 'Restricted access' );
JLoader::register( 'J2StoreTable', JPATH_ADMINISTRATOR.DS.'components'.DS.'com_j2store'.DS.'tables'.DS.'_base.php' );
class TableProductQuantities extends J2StoreTable
{
	
	function TableProductQuantities(&$db ) 
	{
		$tbl_key    = 'productquantity_id';
        $tbl_suffix = 'productquantities';
        $this->set( '_suffix', $tbl_suffix );
       	$name 		= 'j2store';
		
		parent::__construct( "#__{$name}_{$tbl_suffix}", $tbl_key, $db );	
	}
	
	function check()
	{
		if (empty($this->product_id))
		{
			$this->setError( JText::_('J2STORE_PRODUCT_REQUIRED') );
			return false;
		}
	
		return true;
	}
	
	
	/**
	 * Generic save function
	 *
	 * @access	public
	 * @returns TRUE if completely successful, FALSE if partially or not successful
	 */
	function save()
	{
	
	
		$this->_isNew = false;
		$key = $this->getKeyName();
		if (empty($this->$key))
		{
			$this->_isNew = true;
		}
	
		if ( !$this->check() )
		{
			return false;
		}
		if ( !$this->store() )
		{
			return false;
		}
	
		if ( !$this->checkin() )
		{
			$this->setError( $this->_db->stderr() );
			return false;
		}
	
		$this->setError('');
	
		return true;
	}

}