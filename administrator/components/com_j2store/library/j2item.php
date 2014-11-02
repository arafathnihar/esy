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
defined('_JEXEC') or die('Restricted access');
JTable::addIncludePath(JPATH_COMPONENT_ADMINISTRATOR.DS.'tables');
class J2StoreItem
{

	/**
	 *
	 * @return unknown_type
	 */
	public static function display( $articleid )
	{
		$html = '';

		$mainframe = JFactory::getApplication();

		$item = JTable::getInstance('content', 'JTable');
		$item->load( $articleid );
		// Return html if the load fails
		if (!$item->id)
		{
			return $html;
		}

		$item->title = JFilterOutput::ampReplace($item->title);

		$item->text = '';

		$item->text = $item->introtext . chr(13).chr(13) . $item->fulltext;

		$limitstart	= JRequest::getVar('limitstart', 0, '', 'int');
		$params		=$mainframe->getParams('com_content');

		// Use param for displaying article title
		$j2store_params = JComponentHelper::getParams('com_j2store');
		$show_title = $j2store_params->get('show_title', $params->get('show_title') );
		if ($show_title)
		{
			$html .= "<h3>{$item->title}</h3>";
		}
		$html .= $item->introtext;

		return $html;
	}


	public static function getJ2Image($id, $jparams) {

		$app = JFactory::getApplication();
		$item = JTable::getInstance('content','JTable');
		$item->load($id);
		$item_image =new JRegistry();
		$item_image->loadString($item->images, 'JSON');
		/*
		 * JRegistry Object ( [data:protected] => stdClass Object ( [image_intro] => [float_intro] =>
		 		* [image_intro_alt] => [image_intro_caption] =>
		 		* [image_fulltext] => images/sampledata/parks/landscape/120px_rainforest_bluemountainsnsw.jpg [float_fulltext] =>
		 		* [image_fulltext_alt] => [image_fulltext_caption] => ) )
		* */
		if ($jparams->get('show_thumb_cart') == 'fulltext' && $item_image->get('image_fulltext') ) {
			$image = '<img src="'.JURI::root().$item_image->get('image_fulltext').
			'" alt="'.$item_image->get('image_fulltext_alt').
			'" title="'.$item_image->get('image_fulltext_caption').
			'" id="itemImg'.$jparams->get('cartimage_size','small').'" />';

		} else 	if ($jparams->get('show_thumb_cart') == 'intro' && $item_image->get('image_intro') ) {
			$image = '<img src="'.JURI::root().$item_image->get('image_intro').
			'" alt="'.$item_image->get('image_intro_alt').
			'" title="'.$item_image->get('image_intro_caption').
			'" id="itemImg'.$jparams->get('cartimage_size','small').'" />';

		} else 	if ($jparams->get('show_thumb_cart') == 'within_text') {
			$image_path = J2StoreItem::getImages($item->introtext);
			$image = '<img src="'.$image_path.
			'" id="itemImg'.$jparams->get('cartimage_size','small').'" />';
		} else {
			$image = '';
		}

		return $image;

	}

	public static function getImages($text) {
		$matches = array();
		preg_match("/\<img.+?src=\"(.+?)\".+?\/>/", $text, $matches);
		$images = '';
		$images = false;
		$paths = array();
		if (isset($matches[1])) {

			$image_path = $matches[1];

			//joomla 1.5 only
			$full_url = JURI::base();

			//remove any protocol/site info from the image path
			$parsed_url = parse_url($full_url);

			$paths[] = $full_url;
			if (isset($parsed_url['path']) && $parsed_url['path'] != "/") $paths[] = $parsed_url['path'];


			foreach ($paths as $path) {
				if (strpos($image_path,$path) !== false) {
					$image_path = substr($image_path,strpos($image_path, $path)+strlen($path));
				}
			}

			// remove any / that begins the path
			if (substr($image_path, 0 , 1) == '/') $image_path = substr($image_path, 1);

			//if after removing the uri, still has protocol then the image
			//is remote and we don't support thumbs for external images
			if (strpos($image_path,'http://') !== false ||
					strpos($image_path,'https://') !== false) {
				return false;
			}

			$images = JURI::Root(True)."/".$image_path;
		}
		return $images;
	}

	public static function isShippingEnabled($product_id) {
		$row = J2StoreItem::_getJ2Item($product_id);
		if($row->item_shipping) {
			return true;
		}
		return false;
	}

	public static function _getJ2Item($id) {
		$db		= JFactory::getDbo();
		$query	= $db->getQuery(true);

		$query->select('*');
		$query->from('`#__j2store_prices`');
		$query->where('article_id='.$id);

		$db->setQuery($query);
		$item=$db->loadObject();
		return $item;
	}

	public static function getTaxProfileId($product_id){
		$row = J2StoreItem::_getJ2Item($product_id);
		return $row->item_tax;
	}
}

