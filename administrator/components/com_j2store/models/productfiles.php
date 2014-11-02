<?php
/**
 * @version		$Id: product.php 2017.02.2011 23:30:27 Sasi varna kumar S$
 * @package		Joomla.Site
 * @subpackage	com_j2store
 * @copyright	Copyright (C) 2010 - 2015 Weblogicx India Private Limited. All rights reserved.
 * @license		GNU General Public License version 2 or later; see LICENSE.txt
 */

// No direct access.
defined('_JEXEC') or die;

jimport('joomla.application.component.modellist');
jimport('joomla.filesystem.folder');
jimport('joomla.filesystem.file');


/**
 * Weblinks model.
 *
 * @package		Joomla.Site
 * @subpackage	com_j2store
 * @since 2.5
*/
class J2StoreModelProductFiles extends JModelList
{
	protected $text_prefix = 'com_j2store';

	protected $data;

	/*protected function populateState($ordering = null, $direction = null)	{

	$app = JFactory::getApplication('site');

	// Load state from the request.
	$pk = JRequest::getInt('product_id');
	$this->setState('product.id', $pk);

	}
	*/


	public function getTable($type = 'productfiles', $prefix = 'Table', $config = array())	{

		return JTable::getInstance($type, $prefix, $config);
	}

	protected function getListQuery()
	{
		// Create a new query object.
		$db		= $this->getDbo();
		$query	= $db->getQuery(true);
		$user 	= JFactory::getUser();
		$query->select('a.*');
		$id = JRequest::getVar('id', $this->getState('product.id'), 'get', 'int');
		//$query->select($this->getState('list.select','a.*'));
		$query->from('`#__j2store_productfiles` AS a');

		$query->where('a.product_id='.$id);

		// Add the list ordering clause.
		$orderCol	= $this->getState('list.ordering');
		$orderDirn	= $this->getState('list.direction');
		if ($orderCol == 'a.ordering') {
			//$orderCol = ' '.$orderDirn.', a.ordering';
		}

		if(!empty($orderCol)) {
			$query->order($db->escape($orderCol.' '.$orderDirn));
		}

		// Select the required fields from the table.
		return $query;
	}

	function saveFile($file){

		//$id = JRequest::getVar('product_id', $this->getState('product.id'), 'get', 'int');
		$id = JRequest::getVar('id', $this->getState('product.id'), 'get', 'int');

		if(!$id){
			$this->setError(JText::_('J2STORE_PFILE_SAVE_ARTICLE_FIRST'));
			return false;
		}

		$params = JComponentHelper::getParams('com_j2store');
		$folder_base_path = $params->get('attachmentfolderpath', JPATH_SITE.'/media/j2store/');

		//lets check if this base path exisists
		if( !JFolder::exists($folder_base_path)){
			$this->setError(JText::_('J2STORE_PFILE_SET_PATH'));
			return false;
		}

		$mode = (int)0755;
		$index_file_src_path = JPATH_SITE.'/media/index.html';

		//if exists lets create products folder here
		$products_folder_path  = $folder_base_path.'/products';
		if( !JFolder::exists($products_folder_path)){
			if(! (  JFolder::create($products_folder_path,$mode)
					&& JFile::copy($index_file_src_path,$products_folder_path.'/index.html') ) ) {
				$this->setError(JText::_('J2STORE_PFILE_ERROR_CREATING_DIRECTORY'));
				return false;
			}
		}

		//Lets move on now to create product specific folders and store the files

		$folder_path = $products_folder_path.'/'.$id;

		//check if folder for the current product exists and
		//if not a folder in name of 'prod_id' is created and index.html copied
		if( ! JFolder::exists($folder_path)){
			if(! (   JFolder::create($folder_path,$mode)
					&& JFile::copy($index_file_src_path,$folder_path.'/index.html') ) ) {
				$this->setError(JText::_('J2STORE_PFILE_ERROR_CREATING_DIRECTORY'));
				return false;
			}
		}

		// upload the file as it is without rename , resize as it is.
		if(!$this->uploadFile($file,$folder_path)) {
			$this->setError(JText::_('J2STORE_PFILE_ERROR_UPLOADING_FILES'));
			return false;
		}

		return true;
	}

	function getFiles(){

		$id = JRequest::getVar('id', 0, 'get', 'int');

		//to do -- check product id if exists ...
		//...

		$folder_path = JPATH_SITE.DS.'media'.DS.'j2store'.DS.'files'.DS.'products'.DS.$id;

		//check if folder for the current product exists
		if( ! JFolder::exists($folder_path)){

			$this->setError(JText::_('J2STORE_PFILE_NO_FILES_FOUND'));
			return false;
		}


		// gets list of files in the folder except file (index.html)
		$files = JFolder::files($folder_path, $filter = '.', $recurse = false, $fullpath = false, $exclude = array('index.html'));

		$i=0;
		//$result->loaction=array();

		// TODO : get file sotring location ($folder_path ) as a parameter...

		$folder_path = 'media'.DS.'j2store'.DS.'files'.DS.'products'.DS.$id;


		return $files;

	}

	function deleteFile($file_name){

		//get the product id
		$id = JRequest::getVar('id', 0, 'get', 'int');

		$params = JComponentHelper::getParams('com_j2store');
		$folder_path=$params->get('attachmentfolderpath');
		$folder_path .= DS.'products'.DS.$id;

		// append the folder path with file name
		$file_name = $folder_path.DS.$file_name;

		// delete the file
		if(JFile::exists($file_name) ) {
			JFile::delete($file_name);
			return true;
		} else {
			$this->setError(JText::_('J2STORE_PFILE_ERROR_DELETING_FILE'));
		}
		return false;
	}

	function uploadFile($file,$save_path)
	{

		$file_src = $file['tmp_name'];
		$save_path = $save_path.DS.$file['name'];
		//0755 folder
		//0644 file
		if(!JFile::upload($file_src, $save_path)) {
			return false;
		}

		return true;
		/*
		 if($file)
		 {
		//$file_src = $file['tmp_name'];
		$handle = new Upload($file_src);

		if ($handle->uploaded)
		{
		//Original file
		$handle->file_auto_rename = false;
		$handle->file_overwrite = true;
		$handle->file_new_name_body = $file_src;
		$handle->Process($savepath);
		}
		else
		{
		$error = $handle->error;
		}

		}
		*/
		//	return $this->upload;
	}

}
