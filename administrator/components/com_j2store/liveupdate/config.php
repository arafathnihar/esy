<?php
/**
 * @package LiveUpdate
 * @copyright Copyright ©2011 Nicholas K. Dionysopoulos / AkeebaBackup.com
 * @license GNU LGPLv3 or later <http://www.gnu.org/copyleft/lesser.html>
 */

defined('_JEXEC') or die();

/**
 * Configuration class for your extension's updates. Override to your liking.
 */
class LiveUpdateConfig extends LiveUpdateAbstractConfig
{
	var $_extensionName			= 'com_j2store';
	var $_extensionTitle		= 'J2Store Core';
	var $_updateURL				= 'http://j2store.org/updates/update.ini';
	var $_requiresAuthorization	= false;
	var $_versionStrategy		= 'different';

	public function __construct() {
		jimport('joomla.filesystem.file');
		//$isPro = defined('J2STORE_PRO') ? (J2STORE_PRO == 1) : false;

		// Load the component parameters, not using JComponentHelper to avoid conflicts ;)
		jimport('joomla.html.parameter');
		jimport('joomla.application.component.helper');
		$db = JFactory::getDbo();
		$sql = $db->getQuery(true)
			->select($db->quoteName('params'))
			->from($db->quoteName('#__extensions'))
			->where($db->quoteName('type').' = '.$db->quote('component'))
			->where($db->quoteName('element').' = '.$db->quote('com_j2store'));
		$db->setQuery($sql);
		$rawparams = $db->loadResult();
		$params = new JRegistry();
		if(version_compare(JVERSION, '3.0', 'ge')) {
			$params->loadString($rawparams, 'JSON');
		} else {
			$params->loadJSON($rawparams);
		}

		// Dev releases use the "newest" strategy
		if(substr($this->_currentVersion,1,2) == 'ev') {
			$this->_versionStrategy = 'newest';
		} else {
			$this->_versionStrategy = 'vcompare';
		}

		// Get the minimum stability level for updates
		$this->_minStability = $params->get('minstability', 'stable');

		// Do we need authorized URLs?
	//	$this->_requiresAuthorization = $isPro;

		// Should I use our private CA store?
		if(@file_exists(dirname(__FILE__).'/../assets/cacert.pem')) {
			$this->_cacerts = dirname(__FILE__).'/../assets/cacert.pem';
		}

		parent::__construct();
	}
}
