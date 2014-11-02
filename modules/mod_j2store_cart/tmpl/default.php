<?php
/*------------------------------------------------------------------------
# mod_j2store_cart - J2 Store Cart
# ------------------------------------------------------------------------
# author    Sasi varna kumar - Weblogicx India http://www.weblogicxindia.com
# copyright Copyright (C) 2012 Weblogicxindia.com. All Rights Reserved.
# @license - http://www.gnu.org/licenses/gpl-2.0.html GNU/GPL
# Websites: http://j2store.org
# Technical Support:  Forum - http://j2store.org/forum/index.html
-------------------------------------------------------------------------*/


// no direct access
defined('_JEXEC') or die('Restricted access');
$mainframe=JFactory::getApplication();
$document =JFactory::getDocument();
if (!version_compare(JVERSION, '3.0', 'ge'))
{
	$document->addScript(JURI::root(true).'/media/j2store/js/j2storejq.js');
} else {
	JHtml::_('jquery.framework');
}

?>
<script type="text/javascript">
<!--
if(typeof(j2store) == 'undefined') {
	var j2store = {};
}
if(typeof(j2store.jQuery) == 'undefined') {
	j2store.jQuery = jQuery.noConflict();
}

if(typeof(j2storeURL) == 'undefined') {
	var j2storeURL = '';
}

(function($) {
	$(document).ready(function(){
		var container = '#miniJ2StoreCart';
		var murl = j2storeURL
				+ 'index.php?option=com_j2store&view=mycart&task=ajaxmini';

		if ($('#miniJ2StoreCart').length > 0) {
		$.ajax({
			url : murl,
			type: 'post',
			success: function(response){
				if ($('#miniJ2StoreCart').length > 0) {
					$('#miniJ2StoreCart').html(response);
				}
			}

		});
		}
	});
})(j2store.jQuery);

//-->
</script>
<div id="miniJ2StoreCart">

</div>
<br />
<?php if($link_type =='link'):?>
<a class="link" href="<?php echo JRoute::_('index.php?option=com_j2store&view=mycart');?>">
<?php echo JText::_('J2STORE_VIEW_CART');?>
</a>
<?php else: ?>
<input type="button" class="btn btn-primary button" onClick="window.location='<?php echo JRoute::_('index.php?option=com_j2store&view=mycart');?>'"
value="<?php echo JText::_('J2STORE_VIEW_CART');?>"
/>
<?php endif;?>