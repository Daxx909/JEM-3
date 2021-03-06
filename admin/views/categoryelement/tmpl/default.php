<?php
/**
 * @package JEM
 * @copyright (C) 2013-2015 joomlaeventmanager.net
 * @copyright (C) 2005-2009 Christoph Lukes
 * @license http://www.gnu.org/licenses/gpl-2.0.html GNU/GPL
 */
defined('_JEXEC') or die;

$function = JFactory::getApplication()->input->getCmd('function', 'jSelectCategory');
JHtml::_('bootstrap.tooltip');
JHtml::_('behavior.framework', true);
?>

<form action="index.php?option=com_jem&amp;view=categoryelement&amp;tmpl=component" method="post" name="adminForm" id="adminForm">
	<fieldset class="filter clearfix">
		<div class="btn-toolbar">
			<div class="btn-group pull-left input-append">
				<input type="text" name="filter_search" id="filter_search" placeholder="<?php echo JText::_('COM_JEM_SEARCH');?>" value="<?php echo $this->lists['search']; ?>" class="inputbox" onChange="this.form.submit();" />
				<button type="submit" class="btn"><i class="icon-search"></i></button>
				<button type="button" class="btn" onclick="document.getElementById('filter_search').value='';this.form.submit();"><i class="icon-remove"></i></button>
			</div>
			<div class="btn-group pull-right">
				<select name="filter_state" class="inputbox" onchange="this.form.submit()">
					<option value=""><?php echo JText::_('JOPTION_SELECT_PUBLISHED');?></option>
					<?php echo JHtml::_('select.options', JHtml::_('jgrid.publishedOptions',array('all' => 0, 'unpublished' => 0,'archived' => 0, 'trash' => 0)), 'value', 'text', $this->filter_state, true);?>
			</select>
			</div>
			<div class="clearfix"></div>
		</div>
	</fieldset>
	
	<table class="table table-striped" id="table-condensed">
		<thead>
			<tr>
				<th width="7" class="center"><?php echo JText::_('COM_JEM_NUM'); ?></th>
				<th align="left" class="title"><?php echo JHtml::_('grid.sort','COM_JEM_CATEGORY','c.catname',$this->lists['order_Dir'],$this->lists['order']); ?></th>
				<th width="1%" nowrap="nowrap"><?php echo JText::_('COM_JEM_ACCESS'); ?></th>
				<th width="1%" nowrap="nowrap"><?php echo JText::_('JSTATUS'); ?></th>
			</tr>
		</thead>
		<tfoot>
			<tr>
				<td colspan="4">
					<?php echo $this->pagination->getListFooter(); ?>
				</td>
			</tr>
		</tfoot>
		<tbody>
			<?php
			foreach ($this->rows as $i => $row) :
				$access = $row->groupname;
   			?>
		 	<tr class="row<?php echo $i % 2; ?>">
				<td class="center" width="7"><?php echo $this->pagination->getRowOffset( $i ); ?></td>
				<td align="left">
					<a href="javascript:void(0)" class="pointer" onclick="if (window.parent) window.parent.<?php echo $this->escape($function);?>('<?php echo $row->id; ?>', '<?php echo $this->escape(addslashes($row->catname)); ?>');"><?php echo htmlspecialchars_decode($this->escape($row->treename)); ?></a>
				</td>
				<td class="center"><?php echo $access; ?></td>
				<td class="center">
					<?php echo JHtml::_('jgrid.published', $row->published, $i,'',false); ?>
				</td>
			</tr>
			<?php endforeach; ?>
		</tbody>
	</table>

	<input type="hidden" name="task" value="">
	<input type="hidden" name="tmpl" value="component">
	<input type="hidden" name="function" value="<?php echo $this->escape($function); ?>" />
	<input type="hidden" name="filter_order" value="<?php echo $this->lists['order']; ?>" />
	<input type="hidden" name="filter_order_Dir" value="<?php echo $this->lists['order_Dir']; ?>" />
	<?php echo JHtml::_('form.token'); ?>
	
	<div class="poweredby">
		<?php echo JemAdmin::footer( ); ?>
	</div>
	
</form>