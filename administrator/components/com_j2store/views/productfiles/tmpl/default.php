<?php
/**
 * @version		$Id: default.php 14.07.2011 07:52:07 Sasi varna kumar.S $
 * @package		Joomla.Administrator
 * @subpackage	com_j2store
 * @copyright	Copyright (C) 2005 - 2010 Open Source Matters, Inc. All rights reserved.
 * @license		WeblogicxIndia - GNU General Public License version 2 or later; see LICENSE.txt
 */

// no direct access
defined('_JEXEC') or die;
require_once (JPATH_ADMINISTRATOR.'/components/com_j2store/library/popup.php');
JHtml::_('behavior.tooltip');
$listOrder	= $this->state->get('list.ordering');
//if(empty($listOrder))  $listOrder = 'a.ordering';
$listDirn	= $this->state->get('list.direction');
?>
<div class="j2store">
<h3>
	<?php echo JText::_( 'J2STORE_PFILE_SET_FILES_FOR' ); ?>
	:
	<?php echo $this->row->title; ?>
</h3>
<form action="<?php echo $this->_action;  ?>" method="post"
	name="adminForm" id="adminForm" enctype="multipart/form-data">
	<div class="note row-fluid">
		<h4>
			<?php echo JText::_('J2STORE_PFILE_ADD_NEW_FILE'); ?>
		</h4>
		<table class="adminlist table table-striped">
			<thead>
				<tr>
					<th><?php echo JText::_( "J2STORE_PFILE_FNAME" ); ?></th>
					<th><?php echo JText::_( "J2STORE_PFILE_PURECHASE_REQUIRED" ); ?></th>
					<th><?php echo JText::_( 'J2STORE_PFILE_ENABLED' ); ?></th>
					<th><?php echo JText::_( 'J2STORE_PFILE_FILE_LOCATION' ); ?></th>
					<th><?php echo JText::_( 'J2STORE_PFILE_MAX_DL_LIMIT' ); ?></th>
					<th>&nbsp;</th>
				</tr>
			</thead>
			<tbody>
				<tr>
					<td style="text-align: center;"><input type="text" id="displayname"
						name="displayname" value="" />
					</td>
					<td><input type="radio" id="purchase_required"
						name="purchase_required" value="1"> <?php echo JText::_('J2STORE_YES'); ?></input>
						<input type="radio" id="purchase_required"
						name="purchase_required" value="0"> <?php echo JText::_('J2STORE_NO'); ?></input>
					</td>
					<td><input type="radio" id="state" name="state" value="1"> <?php echo JText::_('J2STORE_YES'); ?></input>
						<input type="radio" id="state" name="state" value="0"> <?php echo JText::_('J2STORE_NO'); ?></input>
					</td>
					<td><input type="file" id="savename" name="savename" value="" />
					</td>
					<td><input type="text" id="download_limit" name="download_limit"
						value="-1" />
					</td>
					<td>
						<button class="btn btn-primary"
							onclick="document.getElementById('task').value='createfile'; document.adminForm.submit();">
							<?php echo JText::_('J2STORE_PFILE_UPLOAD'); ?>
						</button>

					</td>
				</tr>
			</tbody>
		</table>
		<div class="pull-right">
			<button class="btn btn-info"
				onclick="document.getElementById('task').value='savefiles'; document.getElementById('checkall-toggle').checked=true; j2storeCheckAll(document.adminForm); document.adminForm.submit();">
				<?php echo JText::_('J2STORE_SAVE_CHANGES'); ?>
			</button>
		</div>
		<div class="reset"></div>
		<h4>
			<?php echo JText::_('J2STORE_PFILE_CURRENT_FILES'); ?>
		</h4>
		<table class="adminlist table table-striped">
			<thead>
				<tr>
					<th width="1%"><input type="checkbox" id="checkall-toggle"
						name="checkall-toggle" value=""
						title="<?php echo JText::_('JGLOBAL_CHECK_ALL'); ?>"
						onclick="Joomla.checkAll(this)" />
					</th>
					<th width="20%"><?php echo JHtml::_('grid.sort',  'J2STORE_PFILE_FDISP_NAME', 'a.productfile_name', $listDirn, $listOrder); ?>
					</th>
					<th width="7%"><?php echo JHtml::_('grid.sort',  'J2STORE_PFILE_PURECHASE_REQUIRED', 'a.purchase_required', $listDirn, $listOrder); ?>
					</th>
					<th width="7%"><?php echo JHtml::_('grid.sort',  'J2STORE_PFILE_ENABLED', 'a.state', $listDirn, $listOrder); ?>
					</th>
					<th width="7%"><?php echo JHtml::_('grid.sort',  'J2STORE_PFILE_MAX_DL_LIMIT2', 'a.download_limit', $listDirn, $listOrder); ?>
					</th>
					<th width="5%"><?php echo JHtml::_('grid.sort',  'J2STORE_ORDERING', 'a.ordering', $listDirn, $listOrder); ?>
					</th>
				</tr>
			</thead>
			<tfoot>
				<tr>
					<td colspan="10"><?php echo $this->pagination->getListFooter(); ?>
					</td>
				</tr>
			</tfoot>


			<tbody>
				<?php
				//check if items exist
				if(count($this->items) > 0):
				foreach ($this->items as $i => $item) :
				$ordering	= ($listOrder == 'a.ordering');
				$user = JFactory::getUser();
				?>
				<tr class="row<?php echo $i % 2; ?>">
					<td class="center"><?php echo JHtml::_('grid.id', $i, $item->productfile_id); ?>
					</td>
					<td style="text-align: left;"><input type="text"
						name="product_file_display_name[<?php echo $item->productfile_id; ?>]"
						value="<?php echo $item->product_file_display_name; ?>" /><br />
						filename : <?php echo $item->product_file_save_name; ?>
					</td>
					<td style="text-align: left;"><input type="radio"
						id="product_file_purchase_required[<?php echo $item->productfile_id; ?>]"
						name="product_file_purchase_required[<?php echo $item->productfile_id; ?>]"
						value="1"
						<?php if($item->purchase_required) echo 'checked="checked"'; ?>> <?php echo JText::_('J2STORE_YES'); ?></input>
						<input type="radio"
						id="product_file_purchase_required[<?php echo $item->productfile_id; ?>]"
						name="product_file_purchase_required[<?php echo $item->productfile_id; ?>]"
						value="0"
						<?php if(!$item->purchase_required) echo 'checked="checked"'; ?>>
						<?php echo JText::_('J2STORE_NO'); ?></input>
					</td>
					<td style="text-align: left;"><input type="radio"
						id="product_file_state[<?php echo $item->productfile_id; ?>]"
						name="product_file_state[<?php echo $item->productfile_id; ?>]"
						value="1" <?php if($item->state) echo 'checked="checked"'; ?>> <?php echo JText::_('J2STORE_YES'); ?></input>
						<input type="radio"
						id="product_file_state[<?php echo $item->productfile_id; ?>]"
						name="product_file_state[<?php echo $item->productfile_id; ?>]"
						value="0" <?php if(!$item->state) echo 'checked="checked"'; ?>> <?php echo JText::_('J2STORE_NO'); ?></input>
					</td>
					<td><input type="text"
						id="product_file_download_limit[<?php echo $item->productfile_id; ?>]"
						name="product_file_download_limit[<?php echo $item->productfile_id; ?>]"
						value="<?php echo $item->download_limit; ?>" />
					</td>
					<td style="text-align: center;"><input type="text"
						name="product_file_ordering[<?php echo $item->productfile_id; ?>]"
						value="<?php echo $item->ordering; ?>" size="10" />
					</td>
					<td style="text-align: center;">[<a
						href="index.php?option=com_j2store&view=products&task=deletefile&id=<?php echo $this->product_id; ?>&cid[]=<?php echo $item->productfile_id; ?>&return=<?php echo base64_encode("index.php?option=com_j2store&view=products&task=setfiles&id={$this->product_id}&tmpl=component"); ?>">
							<?php echo JText::_( "J2STORE_PFILE_DELETE_FILE" ); ?>
					</a> ]
					</td>

				</tr>
				<?php endforeach; ?>
				<?php endif; ?>
			</tbody>
		</table>

		<div>
			<input type="hidden" name="id"
				value="<?php echo $this->product_id; ?>" /> <input type="hidden"
				name="product_id" value="<?php echo $this->product_id; ?>" /> <input
				type="hidden" name="option"
				value="<?php echo JRequest::getCmd('option'); ?>"> <input
				type="hidden" name="task" id="task" value="setfiles" /> <input
				type="hidden" name="boxchecked" value="0" /> <input type="hidden"
				name="filter_order" value="<?php echo $listOrder; ?>" /> <input
				type="hidden" name="filter_order_Dir"
				value="<?php echo $listDirn; ?>" />
			<?php echo JHtml::_('form.token'); ?>
		</div>
	</div>
</form>
</div>