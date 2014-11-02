<?php
// no direct access
defined('_JEXEC') or die('Restricted access');
?>
<div class="j2store-modal">
<div class="modal">
	<div class="j2store">
		<div class="modal-header">
		<a class="close" data-dismiss="modal">&times;</a>
		</div>
		<div class="modal-body">
		<?php echo $this->html; ?>
		</div>
		<div class="modal-footer">
		<a class="btn" data-dismiss="modal"><?php echo JText::_('Close')?></a>
		</div>
	</div>
</div>
</div>