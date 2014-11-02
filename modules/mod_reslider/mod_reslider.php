<?php

#@license - http://www.gnu.org/licenses/gpl-2.0.html GNU/GPL

/* FANCY PANTS ACCORDION */

defined('_JEXEC') or die;

//add stylesheet
$doc = JFactory::getDocument();

//include the class of the syndicate functions only once
require_once(dirname(__FILE__).'/helper.php');

//keeps module class suffix even if templateer tries to stop it
$moduleclass_sfx = htmlspecialchars($params->get('moduleclass_sfx'));
$object = new mod_resliderHelper();
$listofimages = $object->getImages($params);


mod_resliderHelper::load_jquery($params);

$doc->addStyleSheet(JURI::base(true) . '/modules/mod_reslider/assets/css/style.css', 'text/css' );
$doc->addStyleSheet(JURI::base(true) . '/modules/mod_reslider/assets/css/flexslider.css', 'text/css' );

$doc->addScript(JURI::base(true) . '/modules/mod_reslider/assets/js/jquery.flexslider-min.js');

require(JModuleHelper::getLayoutPath('mod_reslider'));
