<?php
/*------------------------------------------------------------------------
# com_j2store - J2Store v 1.0
# ------------------------------------------------------------------------
# author    Sasi varna kumar - Weblogicx India http://www.weblogicxindia.com
# copyright Copyright (C) 2012 Weblogicxindia.com. All Rights Reserved.
# @license - http://www.gnu.org/licenses/gpl-2.0.html GNU/GPL
# Websites: http://j2store.org
# Technical Support:  Forum - http://j2store.org/forum/index.html
-------------------------------------------------------------------------*/

// No direct access to this file
defined('_JEXEC') or die;

/**
 * Product Options Select Form Field class for the J2Store component
 */
require_once (JPATH_ADMINISTRATOR.'/components/com_j2store/helpers/strapper.php');
class JFormFieldOptionSelect extends JFormFieldList
{
	/**
	 * The field type.
	 *
	 * @var		string
	 */
	protected $type = 'OptionSelect';

	function getInput(){
		J2StoreStrapper::addJS();
		J2StoreStrapper::addCSS();
		$fieldName = $this->fieldname;

 		$doc = JFactory::getDocument();
 		$app = JFactory::getApplication();
 		$yes = JText::_('J2STORE_YES');
 		$no = JText::_('J2STORE_NO');
 		$script = "
 		if(typeof(j2store) == 'undefined') {
			var j2store = {};
		}
		if(typeof(j2store.jQuery) == 'undefined') {
			j2store.jQuery = jQuery.noConflict();
		}

 		(function($) {
 		$(document).ready(function() {
 			$('#optionselector').autocomplete({
 				source : function(request, response) {
 					$.ajax({
 						type : 'post',
 						url :  'index.php?option=com_j2store&view=options&task=getOptions',
 						data : 'q=' + request.term,
 						dataType : 'json',
 						success : function(data) {
 							$('#optionselector').removeClass('optionsLoading');
 							response($.map(data, function(item) {
 								return {
 									label: item.option_name+' ('+item.option_unique_name+')',
 									value: item.option_id
 								}
 							}));
 						}
 					});
 				},
 				minLength : 2,
 				select : function(event, ui) {
 					$('<tr><td class=\"addedOption\">' + ui.item.label+ '</td><td><select name=\"jform[attribs][item_options][product_option_required]['+ ui.item.value+']\" ><option value=\"0\">$no</option><option value=\"1\">$yes</option></select></td><td><span class=\"optionRemove\" onclick=\"j2store.jQuery(this).parent().parent().remove();\">x</span><input type=\"hidden\" value=\"' + ui.item.value+ '\" name=\"jform[attribs][item_options][product_option_ids][]\" /></td></tr>').insertBefore('.a_options');
 					this.value = '';
 					return false;
 				},
 				search : function(event, ui) {
 					$('#optionselector').addClass('optionsLoading');
 				}
 			});

 		});
 		})(j2store.jQuery);
 		";

 		$doc->addScriptDeclaration($script);
 		$product_id = $app->input->get('id');

				//$lists = $this->_getSelectProfiles($this->name, $this->id,$this->value);
				$html='';
				$html .='<table id="attribute_options_table" class="adminlist table table-striped table-bordered j2store">';
				$html .='<thead>';
				$html .='<th>'.JText::_('J2STORE_OPTION_NAME').'</th>';
				$html .='<th>'.JText::_('J2STORE_OPTION_REQUIRED').'</th>';
				$html .='<th>'.JText::_('J2STORE_OPTION_REMOVE').'</th>';
				$html .='</thead>';
				if($product_id ) {
					$html .= $this->_getCurrentOptions($product_id);
				}
				$html .='<tbody>';
				$html .='<tr class="a_options"><td colspan="3">';
				$html .='<label class="attribute_option_label">';
				$html .=JText::_('J2STORE_OPTIONFIELD_ADD_OPTIONS');
				$html .='</label>';
				$html .='<input id="optionselector" type="text" value="" />';
				$html .='</td></tr>';
				$html.='<tr><td colspan="3">';
				$html .='<div class="alert alert-block alert-info">';
				$html .=JText::_('J2STORE_OPTIONFIELD_ADD_OPTIONS_HELP_TEXT');
				$html .='</div>';
				$html .='</td></tr>';
				$html .='</tbody></table>';


			return $html;
	}

	protected function _getCurrentOptions($product_id) {

		$html = '';
		$db = JFactory::getDBO();
		$query = $db->getQuery(true);
		$query->select('po.product_option_id, po.option_id, po.required, o.option_unique_name, o.option_name, o.type');
		$query->from('#__j2store_product_options AS po');
		$query->join('LEFT', '#__j2store_options AS o ON po.option_id = o.option_id');
		$query->where('po.product_id='.$product_id);
		$query->order('po.product_option_id');
		$db->setQuery( $query );
		$pa_options = $db->loadObjectList();
		if(count($pa_options)) {

			foreach($pa_options as $pa_option) {
				$html .='<tr id="pao_current_option_'.$pa_option->product_option_id.'">';
				$html .='<td>';
				$html .='<strong>'.$pa_option->option_name.'</strong>';
				$html .='&nbsp;&nbsp;<small>('.$pa_option->option_unique_name.')</small>';
				$html .= '&nbsp;&nbsp;<br />';
				$html .= '<small>'.JText::_('J2STORE_OPTION_TYPE').':&nbsp;'.JText::_('J2STORE_'.JString::strtoupper($pa_option->type)).'</small>';

				if($pa_option->type == 'select' || $pa_option->type == 'radio' || $pa_option->type == 'checkbox') {
					$html .= '&nbsp;&nbsp;<br />';
					$html .= J2StorePopup::popup( "index.php?option=com_j2store&view=products&task=setproductoptionvalues&product_option_id=".$pa_option->product_option_id."&tmpl=component", JText::_( "J2STORE_OPTION_SET_VALUES" ), array());
				}
				$html .='</td>';
				$html .='<td>';
				$html .= $this->_getOptionRequired($pa_option->product_option_id, $pa_option->required);
				$html .='</td>';
				$html .='<td>';
				$html .='<span class="optionRemove" onClick="removePAOption('.$pa_option->product_option_id.')">X</span>';
				$html .='</td>';
			}

		}


		return $html;

	}

	function _getOptionRequired($product_option_id, $required=0) {

		$html = "";
		$html .= "<fieldset id='product_option_required' class='radio'>";
		$html .="<label class=''>";
		$html .="<input type='radio' class='radio' name='jform[attribs][item_options][product_option_required_save][{$product_option_id}]' value='1'";
		if($required == 1) $html .="checked='checked'";
		$html .="/>";
		$html .=JText::_('J2STORE_YES')."</label>";
		$html .="<label class=''>";
		$html .="<input type='radio' class='radio' name='jform[attribs][item_options][product_option_required_save][{$product_option_id}]' value='0'";
		if($required == 0) $html .="checked='checked'";
		$html .="/>";
		$html .=JText::_('J2STORE_NO')."</label>";
		$html .= "</fieldset>";
		return $html;
	}

}