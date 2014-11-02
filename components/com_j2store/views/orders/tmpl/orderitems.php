<?php
/*------------------------------------------------------------------------
 # com_j2store - K2 Store
# ------------------------------------------------------------------------
# author    Ramesh Elamathi - Weblogicx India http://www.weblogicxindia.com
# copyright Copyright (C) 2012 Weblogicxindia.com. All Rights Reserved.
# @license - http://www.gnu.org/licenses/gpl-2.0.html GNU/GPL
# Websites: http://j2store.org
# Technical Support:  Forum - http://j2store.org/forum/index.html
-------------------------------------------------------------------------*/



//no direct access
defined('_JEXEC') or die('Restricted access');
require_once (JPATH_ADMINISTRATOR.'/components/com_j2store/library/prices.php');
require_once (JPATH_SITE.'/components/com_j2store/helpers/orders.php');
//$row = @$this->row;
$order = $this->order->order;
$items = $this->orderitems;
?>
<div class="j2store">
	<h3>
		<?php echo JText::_("J2STORE_ITEMS_IN_ORDER"); ?>
	</h3>
	<table width="80%" class="cart_order table table-stripped table-bordered">
		<thead>
			<tr>
				<th style="text-align: left;"><?php echo JText::_("J2STORE_CART_ITEM"); ?></th>
				<th style="width: 150px; text-align: center;"><?php echo JText::_("J2STORE_CART_ITEM_QUANTITY"); ?>
				</th>
				<th style="width: 150px; text-align: right;"><?php echo JText::_("J2STORE_ITEM_PRICE"); ?>
				</th>
			</tr>
		</thead>
		<tbody>
			<?php $i=0; $k=0; ?>
			<?php foreach (@$items as $item) : ?>

			<tr class='row<?php echo $k; ?>'>
				<td> <?php echo JText::_( $item->orderitem_name ); ?> <br />

				<!-- start of orderitem attributes -->

						<!-- backward compatibility -->
						<?php if(!J2StoreOrdersHelper::isJSON($item->orderitem_attribute_names)): ?>

							<?php if (!empty($item->orderitem_attribute_names)) : ?>
								<span><?php echo $item->orderitem_attribute_names; ?></span>
							<?php endif; ?>
						<br />
						<?php else: ?>
						<!-- since 3.1.0. Parse attributes that are saved in JSON format -->
						<?php if (!empty($item->orderitem_attribute_names)) : ?>
                            <?php
                            	//first convert from JSON to array
                            	$registry = new JRegistry;
                            	$registry->loadString($item->orderitem_attribute_names, 'JSON');
                            	$product_options = $registry->toObject();
                            ?>
                            	<?php foreach ($product_options as $option) : ?>
             				   - <small><?php echo $option->name; ?>: <?php echo $option->value; ?></small><br />
            				   <?php endforeach; ?>
                            <br/>
                        <?php endif; ?>
					<?php endif; ?>
					<!-- end of orderitem attributes -->

					<?php if (!empty($item->orderitem_sku)) : ?> <b><?php echo JText::_( "J2STORE_SKU" ); ?>:</b>
					<?php echo $item->orderitem_sku; ?> <br /> <?php endif; ?> <b><?php echo JText::_( "J2STORE_CART_ITEM_UNIT_PRICE" ); ?>:</b>
					<?php echo J2StorePrices::number( $item->orderitem_price); ?>
				</td>
				<td style="text-align: center;"><?php echo $item->orderitem_quantity; ?>
				</td>
				<td style="text-align: right;"><?php echo J2StorePrices::number( $item->orderitem_final_price ); ?>
				</td>
			</tr>
			<?php $i=$i+1; $k = (1 - $k); ?>
			<?php endforeach; ?>

			<?php if (empty($items)) : ?>
			<tr>
				<td colspan="10" align="center"><?php echo JText::_('J2STORE_NO_ITEMS'); ?>
				</td>
			</tr>
			<?php endif; ?>
		</tbody>
		<tfoot>
			<tr>
				<th colspan="2" style="text-align: right;"><?php echo JText::_( "J2STORE_CART_SUBTOTAL" ); ?>
				</th>
				<th style="text-align: right;"><?php echo J2StorePrices::number($order->order_subtotal); ?>
				</th>
			</tr>

			<?php if($order->order_shipping > 0):?>
			<tr>
				<th colspan="2" style="text-align: right;">
				<?php echo "(+)";?>
				<?php echo JText::_( "J2STORE_SHIPPING" ); ?>
				</th>
				<th style="text-align: right;"><?php echo J2StorePrices::number($order->order_shipping); ?>
				</th>
			</tr>
			<?php endif; ?>

			<?php if($order->order_shipping_tax > 0):?>
			<tr>
				<th colspan="2" style="text-align: right;">
				<?php echo "(+)";?>
				<?php echo JText::_( "J2STORE_CART_SHIPPING_TAX" ); ?>
				</th>
				<th style="text-align: right;"><?php echo J2StorePrices::number($order->order_shipping_tax); ?>
				</th>
			</tr>
			<?php endif; ?>

			<?php if($order->order_discount > 0): 	?>
			<tr>
				<th colspan="2" style="text-align: right;">
				<?php
				if (!empty($order->order_discount ))
                    	{
                            echo "(-)";
                            echo JText::_("J2STORE_CART_DISCOUNT");
                    	}
                   ?>
				</th>

				<th style="text-align: right;">
				<?php
				if (!empty($order->order_discount )) {
					echo J2StorePrices::number($order->order_discount);
				}
				?>
				</th>
			</tr>
			<?php endif; ?>

			<?php if($order->order_tax > 0):?>
			<tr>
				<th colspan="2" style="text-align: right;"><?php
				if (!empty($this->show_tax)) {
					echo JText::_("J2STORE_CART_PRODUCT_TAX_INCLUDED");
				}
				else { echo JText::_("J2STORE_CART_PRODUCT_TAX");
				}
				?>
				</th>
				<th style="text-align: right;"><?php echo J2StorePrices::number($order->order_tax); ?>
				</th>
			</tr>
			<?php endif; ?>

			<tr>
				<th colspan="2" style="font-size: 120%; text-align: right;"><?php echo JText::_( "J2STORE_CART_GRANDTOTAL" ); ?>
				</th>
				<th style="font-size: 120%; text-align: right;"><?php echo J2StorePrices::number($order->order_total); ?>
				</th>

			</tr>
		</tfoot>
	</table>
	</div>
