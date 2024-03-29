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

class TableTaxProfile extends JTable
{

	/** @var int Primary key */
	var $taxprofile_id = null;

	/** @var int */
	var $taxprofile_name = null;

	/** @var int */
	var $tax_percent = null;

	/** @var int */
	var $published = null;

	/**
	 * @param database A database connector object
	 */
	function __construct(&$db)
	{
		parent::__construct('#__j2store_taxprofiles', 'taxprofile_id', $db );
	}

	public function publish($pks = null, $state = 1, $userId = 0)
	{
		// Initialise variables.
		$k = $this->_tbl_key;	// Sanitize input.
		JArrayHelper::toInteger($pks);
		$userId = (int) $userId;
		$state  = (int) $state;
	
		// If there are no primary keys set check to see if the instance key is set.
		if (empty($pks))
		{
			if ($this->$k) {
				$pks = array($this->$k);
			}
			// Nothing to set publishing state on, return false.
			else {
				$this->setError(JText::_('JLIB_DATABASE_ERROR_NO_ROWS_SELECTED'));
				return false;
			}
		}
	
		// Build the WHERE clause for the primary keys.
		$where = $k.'='.implode(' OR '.$k.'=', $pks);
	
		// Update the publishing state for rows with the given primary keys.
		$this->_db->setQuery(
				'UPDATE `'.$this->_tbl.'`' .
				' SET `state` = '.(int) $state .
				' WHERE ('.$where.')' .
				$checkin
		);
		$this->_db->query();
	
		// Check for a database error.
		if ($this->_db->getErrorNum()) {
			$this->setError($this->_db->getErrorMsg());
			return false;
		}
	
		// If checkin is supported and all rows were adjusted, check them in.
		if ($checkin && (count($pks) == $this->_db->getAffectedRows()))
		{
			// Checkin the rows.
			foreach($pks as $pk)
			{
				$this->checkin($pk);
			}
		}
	
		// If the JTable instance value is in the list of primary keys that were set, set the instance.
		if (in_array($this->$k, $pks)) {
			$this->state = $state;
		}
	
		$this->setError('');
		return true;
	}
	public function delete($pk = null)
	{
		// Initialise variables.
		$k = $this->_tbl_key;	// Sanitize input.
		JArrayHelper::toInteger($pk);
		if(is_array($pk)){
			$pks = implode(',',$pk);
		}
		$pk = (is_null($pk)) ? $this->$k : $pk;
	
		// If no primary key is given, return false.
		if ($pk === null) {
			return false;
		}
		// Delete the row by primary key.
		$this->_db->setQuery(
				'DELETE FROM `'.$this->_tbl.'`' .
				' WHERE `'.$this->_tbl_key.'` IN ('.$pks.')'
		);
		$this->_db->query();
	
	
		// Check for a database error.
		if ($this->_db->getErrorNum()) {
			$this->setError($this->_db->getErrorMsg());
			return false;
		}
	
		return true;
	}
	

}
?>
