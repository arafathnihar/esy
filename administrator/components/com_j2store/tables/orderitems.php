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



// Check to ensure this file is included in Joomla!
defined('_JEXEC') or die( 'Restricted access' );
JLoader::register( 'J2StoreTable', JPATH_ADMINISTRATOR.DS.'components'.DS.'com_j2store'.DS.'tables'.DS.'_base.php' );

class TableOrderItems extends J2StoreTable
{

	/**
	 * @param database A database connector object
	 */
	function TableOrderItems ( &$db )
	{

		parent::__construct('#__j2store_orderitems', 'orderitem_id', $db );
	}

	function check()
	{
		$nullDate	= $this->_db->getNullDate();
		if (empty($this->modified_date) || $this->modified_date == $nullDate)
		{
			$date = JFactory::getDate();
			$this->modified_date = $date->toSql();
		}

		// be sure that product_attributes is sorted numerically
		if ($product_attributes = explode( ',', $this->orderitem_attributes ))
		{
			sort($product_attributes);
			$this->orderitem_attributes = implode(',', $product_attributes);
		}

		return true;
	}

	function delete( $oid='' )
	{
		if (empty($oid))
		{
			// if empty, use the values of the current keys
			$keynames = $this->getKeyNames();
			foreach ($keynames as $key=>$value)
			{
				$oid[$key] = $this->$key;
			}
			if (empty($oid))
			{
				// if still empty, fail
				$this->setError( JText::_( "Cannot delete with empty key" ) );
				return false;
			}
		}

		if (!is_array($oid))
		{
			$keyName = $this->getKeyName();
			$arr = array();
			$arr[$keyName] = $oid;
			$oid = $arr;
		}

		$db = $this->getDBO();

		// initialize the query
		$query = new J2StoreQuery();
		$query->delete();
		$query->from( $this->getTableName() );

		foreach ($oid as $key=>$value)
		{
			// Check that $key is field in table
			if ( !in_array( $key, array_keys( $this->getProperties() ) ) )
			{
				$this->setError( get_class( $this ).' does not have the field '.$key );
				return false;
			}
			// add the key=>value pair to the query
			$value = $db->Quote( $db->escape( trim( strtolower( $value ) ) ) );
			$query->where( $key.' = '.$value);
		}

		$db->setQuery( (string) $query );

		if ($db->query())
		{
			return true;
		}
		else
		{
			$this->setError($db->getErrorMsg());
			return false;
		}
	}


}
