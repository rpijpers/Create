<?php
// no direct access
defined('_JEXEC') or die('Restricted access');
jimport('joomla.installer.installer');
// load the component language file
$lang = JFactory::getLanguage();
$lang->load('com_create');
?>
<?php $rows = 0;?>
<h2><?php echo JText::_('Thank you for using Create. We hope you learned something ;)'); ?></h2>
<table class="adminlist">
	<thead>
		<tr>
			<th class="title" colspan="2"><?php echo JText::_('System'); ?></th>
			<th width="30%"><?php echo JText::_('Status'); ?></th>
		</tr>
	</thead>
	<tfoot>
		<tr>
			<td colspan="3"></td>
		</tr>
	</tfoot>
	<tbody>
		<tr class="row0">
			<td class="key" colspan="2"><?php echo 'Create '.JText::_('Component'); ?></td>
			<td><strong><?php echo JText::_('Removed'); ?></strong></td>
		</tr>
	</tbody>
</table>