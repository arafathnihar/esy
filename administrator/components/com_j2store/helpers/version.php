<?php

class J2StoreVersion {

public static function getPreviousVersion() {

	jimport('joomla.filesystem.file');
	$target = JPATH_ADMINISTRATOR.'/components/com_j2store/pre-version.txt';
	if(JFile::exists($target)) {
		$rawData = JFile::read($target);
		$info = explode("\n", $rawData);
		$version = trim($info[0]);
	} else {
		//if no file is found then assume its latest
		$version = '2.5.1';
	}
	return $version;

}

/**
	 * Populates global constants holding the Akeeba version
	 */
	public static function load_version_defines()
	{
		if(file_exists(JPATH_COMPONENT_ADMINISTRATOR.'/version.php'))
		{
			require_once(JPATH_COMPONENT_ADMINISTRATOR.'/version.php');
		}

		if(!defined('J2STORE_VERSION')) define("J2STORE_VERSION", "svn");
		if(!defined('J2STORE_PRO')) define('J2STORE_PRO', false);
		if(!defined('J2STORE_DATE')) {
			jimport('joomla.utilities.date');
			$date = new JDate();
			define( "J2STORE_DATE", $date->format('Y-m-d') );
		}
		if(!defined('J2STORE_ATTRIBUTES_MIGRATED')) define('J2STORE_ATTRIBUTES_MIGRATED', false);
	}

}