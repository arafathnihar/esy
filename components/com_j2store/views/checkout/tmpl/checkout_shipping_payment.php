
	<!-- SHIPPING METHOD -->
			<?php if($this->showShipping):?>
				<div class="j2store-shipping" id="shippingcost-pane">
					<div id="onCheckoutShipping_wrapper">
						<?php echo $this->shipping_method_form;?>
					</div>
				</div>
			<?php endif;?>

	<!-- SHIPPING METHOD END -->


<?php if($this->showPayment): ?>
<div id='onCheckoutPayment_wrapper'>
	<h3>
		<?php echo JText::_('J2STORE_SELECT_A_PAYMENT_METHOD'); ?>
	</h3>
	<?php
	if ($this->plugins)
	{
		foreach ($this->plugins as $plugin)
		{


			?>
	<input value="<?php echo $plugin->element; ?>"
		class="payment_plugin" name="payment_plugin" type="radio"
		onclick="j2storeGetPaymentForm('<?php echo $plugin->element; ?>', 'payment_form_div');"
		<?php echo (!empty($plugin->checked)) ? "checked" : ""; ?>
		title="<?php echo JText::_('J2STORE_SELECT_A_PAYMENT_METHOD'); ?>"
		/>

	<?php
	$params= new JRegistry;
	$params->loadString($plugin->params);
	$title = $params->get('display_name', '');
	if(!empty($title)) {
		echo $title;
	} else {
		echo JText::_($plugin->name );
	}
	?>
	<br />
	<?php
		}
	}
	?>

</div>
<div class="j2error"></div>
<div id='payment_form_div' style="padding-top: 10px;">
	<?php
	if (!empty($this->payment_form_div))
	{
		echo $this->payment_form_div;
	}
	?>

</div>
<?php endif; ?>
<h3>
	<?php echo JText::_('J2STORE_CUSTOMER_NOTE'); ?>
</h3>
<textarea name="customer_note" rows="3" cols="40"></textarea>
<?php if($this->params->get('show_terms', 1)):?>
<?php
$tos_link = 'index.php?option=com_j2store&view=checkout&task=getTerms&article_id='.$this->params->get('termsid', '');
?>
	<div id="checkbox_tos">
		<?php if($this->params->get('terms_display_type', 'link') =='checkbox' ):?>
			<label for="tos_check">
			<input type="checkbox" class="required" name="tos_check" title="<?php echo JText::_('J2STORE_AGREE_TO_TERMS_VALIDATION'); ?>" />
			 <div class="j2error"></div>
				<?php echo JText::_('J2STORE_TERMS_AND_CONDITIONS_LABEL'); ?>

				<?php if($this->params->get('termsid', '')): ?>
					<a href="<?php echo $tos_link; ?>" class="j2store-toggle-modal"><?php echo JText::_('J2STORE_TERMS_AND_CONDITIONS'); ?></a>
				<?php else: ?>
					<?php echo JText::_('J2STORE_TERMS_AND_CONDITIONS'); ?>
				<?php endif; ?>

			</label>

		<?php else: ?>

			<?php echo JText::_('J2STORE_TERMS_AND_CONDITION_PRETEXT'); ?>

				<?php if($this->params->get('termsid', '')): ?>
					<a href="<?php echo $tos_link; ?>" class="j2store-toggle-modal"><?php echo JText::_('J2STORE_TERMS_AND_CONDITIONS'); ?></a>
				<?php else: ?>
					<?php echo JText::_('J2STORE_TERMS_AND_CONDITIONS'); ?>
				<?php endif; ?>
	<?php endif;?>
	</div>
<?php endif; ?>

<div class="buttons">
  <div class="right">
    <input type="button" value="<?php echo JText::_('J2STORE_CHECKOUT_CONTINUE'); ?>" id="button-payment-method" class="button btn btn-primary" />
  </div>
</div>
 <input type="hidden" name="task" value="shipping_payment_method_validate" />
  <input type="hidden" name="option" value="com_j2store" />
  <input type="hidden" name="view" value="checkout" />

  <script type="text/javascript">
  <!--
//bootstrap modal code
(function($) {
	$(document).ready(function() {
		// Support for AJAX loaded modal window.
		$('a.j2store-toggle-modal').click(function(e) {
			e.preventDefault();
			var url = $(this).attr('href');
			if (url.indexOf('#') == 0) {
				$(url).modal('open');
			} else {
				$.get(url, function(data) {
					  $(data).modal().on('hidden', function(){
							 	$(this).data('modal', null);
					           $('.modal-backdrop.in').each(function(i) {
					               $(this).remove();
					           });
					           $('.j2store-modal').each(function(i) {
					               $(this).remove();
					           });
					}); //close hidden function
				});
			}
		});
	});
})(j2store.jQuery);
-->
 </script>