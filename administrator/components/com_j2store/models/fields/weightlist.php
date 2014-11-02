<?php
// Check to ensure this file is included in Joomla!
defined('_JEXEC') or die('Restricted access');

jimport('joomla.html.html');
jimport('joomla.form.formfield');
jimport('joomla.form.helper');
JFormHelper::loadFieldClass('list');


class JFormFieldWeightList extends JFormFieldList {

	protected $type = 'WeightList';

	public function getInput() {

		require_once(JPATH_COMPONENT_ADMINISTRATOR.DS.'models'.DS.'weights.php');
		$model = new J2StoreModelWeights;
		$lengths = $model->getWeights();
		//generate country filter list
		$length_options = array();
		foreach($lengths as $row) {
			$length_options[] =  JHTML::_('select.option', $row->weight_class_id, $row->weight_title);
		}

		return JHTML::_('select.genericlist', $length_options, $this->name, 'onchange=', 'value', 'text', $this->value);
	}

}
