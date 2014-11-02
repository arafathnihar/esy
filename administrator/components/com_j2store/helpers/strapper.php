<?php

class J2StoreStrapper {

	public static function addJS() {
		$mainframe = JFactory::getApplication();
		$j2storeparams = JComponentHelper::getParams('com_j2store');
		$document =JFactory::getDocument();
		//load name spaced jquery only for j 2.5
		if (!version_compare(JVERSION, '3.0', 'ge'))
		{
			if($j2storeparams->get('load_jquery', 1)) {
				$document->addScript(JURI::root(true).'/media/j2store/js/j2storejq.js');
			}
			$document->addScript(JURI::root(true).'/media/j2store/js/bootstrap.min.js');
		} else {
			JHtml::_('jquery.framework');
			JHtml::_('bootstrap.framework');
		}
		//load name spaced jqueryui
		//load name spacer
		$document->addScript(JURI::root(true).'/media/j2store/js/j2store.namespace.js');
		$document->addScript(JURI::root(true).'/media/j2store/js/j2storejqui.js');

		if($mainframe->isAdmin()) {
			$document->addScript(JURI::root(true).'/media/j2store/js/jquery.validate.min.js');
			$document->addScript(JURI::root(true).'/media/j2store/js/j2store_admin.js');
		}
		else {
	//		$document->addScript(JUri::root(true).'/media/j2store/js/jquery-ui-timepicker-addon.js');
			$document->addScript(JURI::root(true).'/media/j2store/js/j2store.js');
		}

	}

	public static function addCSS() {
		$mainframe = JFactory::getApplication();
		$j2storeparams = JComponentHelper::getParams('com_j2store');
		$document =JFactory::getDocument();

		if (!version_compare(JVERSION, '3.0', 'ge'))
		{
			$document->addStyleSheet(JURI::root(true).'/media/j2store/css/bootstrap.min.css');
		}

		if($mainframe->isAdmin()) {
			$document->addStyleSheet(JURI::root(true).'/media/j2store/css/jquery-ui-custom.css');
			$document->addStyleSheet(JURI::root(true).'/media/j2store/css/j2store_admin.css');
		}
		else {
		//	$document->addStyleSheet(JURI::root(true).'/media/j2store/css/jquery-ui-custom.css');

			// Add related CSS to the <head>
			if ($document->getType() == 'html' && $j2storeparams->get('j2store_enable_css')) {

				$db = JFactory::getDBO();
				$query = "SELECT template FROM #__template_styles WHERE client_id = 0 AND home=1";
				$db->setQuery( $query );
				$template = $db->loadResult();

				jimport('joomla.filesystem.file');
				// j2store.css
				if(JFile::exists(JPATH_SITE.'/templates/'.$template .'/css/j2store.css'))
					$document->addStyleSheet(JURI::root(true).'/templates/'.$template .'/css/j2store.css');
				else
					$document->addStyleSheet(JURI::root(true).'/media/j2store/css/j2store.css');

			} else {
				$document->addStyleSheet(JURI::root(true).'/media/j2store/css/j2store.css');
			}
	}

	}


}