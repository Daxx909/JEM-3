<?php
/**
 * @package JEM
 * @copyright (C) 2013-2015 joomlaeventmanager.net
 * @copyright (C) 2005-2009 Christoph Lukes
 * @license http://www.gnu.org/licenses/gpl-2.0.html GNU/GPL
 */
defined ('_JEXEC') or die;
?>
<div class="editform_content">
<fieldset>
	<legend><?php echo JText::_('COM_JEM_ATTACHMENTS_LEGEND'); ?></legend>

	<table class="adminform" id="el-attachments">
		<tbody>
			<?php foreach ($this->item->attachments as $file): ?>
			<tr>
				<td>
					<div>
						<div class="title"><?php echo JText::_('COM_JEM_ATTACHMENT_FILE');?></div>
						<input class="readonly" type="text" readonly="readonly" value="<?php echo $file->file; ?>" />
						<input type="hidden" name="attached-id[]" value="<?php echo $file->id; ?>" />
					</div>
					<div>
						<div class="title"><?php echo JText::_('COM_JEM_ATTACHMENT_NAME'); ?></div>
						<input type="text" name="attached-name[]" style="width: 100%" value="<?php echo $file->name; ?>" />
					</div>
					<div>
						<div class="title"><?php echo JText::_('COM_JEM_ATTACHMENT_DESCRIPTION'); ?></div>
						<input type="text" name="attached-desc[]" style="width: 100%" value="<?php echo $file->description; ?>" />
					</div>
				</td>
				<td>
					<div>
						<div class="title"><?php echo JText::_('COM_JEM_ATTACHMENT_ACCESS'); ?></div>
						<?php echo JHtml::_('select.genericlist', $this->access, 'attached-access[]', array('class'=>'inputbox', 'size'=>'1'), 'value', 'text', $file->access); ?>
					</div>
				</td>
				<td>
					<?php echo JHtml::image('media/com_jem/images/publish_r.png', JText::_('COM_JEM_GLOBAL_REMOVE_ATTACHEMENT'), array('id' => 'attach-remove'.$file->id,'class' => 'attach-remove pointer','title'=>JText::_('COM_JEM_GLOBAL_REMOVE_ATTACHEMENT'))); ?>
				</td>
			</tr>
			<?php endforeach; ?>
			<tr>
				<td width="100%">
					<div>
						<div class="title"><?php echo JText::_('COM_JEM_ATTACHMENT_FILE'); ?></div>
						<input type="file" name="attach[]" class="attach-field" />
						<?php /* see attachments.js for button's onclick function */ ?>
						<button type="button" class="clear-attach-field btn"><?php echo JText::_('JSEARCH_FILTER_CLEAR') ?></button>
					</div>
					<div>
						<div class="title"><?php echo JText::_('COM_JEM_ATTACHMENT_NAME'); ?></div>
						<input type="text" name="attach-name[]" class="attach-name" value="" />
					</div>
					<div>
						<div class="title"><?php echo JText::_('COM_JEM_ATTACHMENT_DESCRIPTION'); ?></div>
						<input type="text" name="attach-desc[]" class="attach-desc" value="" />
					</div>
				</td>
				<td>
					<div>
						<div class="title"><?php echo JText::_('COM_JEM_ATTACHMENT_ACCESS'); ?></div>
						<?php echo JHtml::_('select.genericlist', $this->access, 'attach-access[]', array('class'=>'inputbox', 'size'=>'1'), 'value', 'text', 0); ?>
					</div>
				</td>
				<td>&nbsp;</td>
			</tr>
		</tbody>
	</table>
</fieldset>
</div>