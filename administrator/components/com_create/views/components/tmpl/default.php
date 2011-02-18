<?
/**
 * @author 		Ronald Pijpers
 * @copyright	Copyright (C) 2011 Intertron. All rights reserved.
 * @license		GNU/GPL version 3, see gpl.txt
 */
?>
<? defined('KOOWA') or die('Restricted access');?>

<?= @helper('behavior.tooltip');?>

<style src="media://com_default/css/admin.css" />
<script src="media://lib_koowa/js/koowa.js" />

<form action="<?= @route()?>" method="get" name="adminForm">
<input type="hidden" name="boxchecked" value="" />

<table class="adminlist" style="clear: both;">
	<thead>
		<tr>
			<th width="20"><input type="checkbox" name="toggle" value="" onclick="checkAll(<?= count($components); ?>);" /></th>
			<th width="200"><?= @helper('grid.sort', array('column' => 'name')) ?></th>
			<th width="100"><?= @helper('grid.sort', array('column' => 'itemsname')) ?></th>
			<th width="100"><?= @helper('grid.sort', array('column' => 'itemname')) ?></th>
			<th></th>
			<th width="20"><?= @helper('grid.sort', array('column' => 'id')) ?></th>
		</tr>
	</thead>
	
	<tfoot>
		<tr>
			<td colspan="6">
			<?= @helper('paginator.pagination', array('total' => $total)) ?>
			</td>
		</tr>
	</tfoot>
	
	<tbody>
	<? $m = 0; ?>
	<?php foreach ($components as $component) : ?>
		<tr class="<?= 'row'.$m; ?>">
			<td align="center">
			<?= @helper('grid.checkbox', array('row' => $component))?>
			</td>
			<?php $href = @route('index.php?option=com_create&view=component&id='.$component->id) ?>
			<td>
				<a href="<?= $href?>"><?= $component->name ?></a>
			</td>
			<td><?= $component->itemsname ?></td>
			<td><?= $component->itemname ?></td>
			<td></td>
			<td align="center"><?= $component->id ?></td>
	</tr>
	<? $m = (1 - $m);?>
	<? endforeach ?>
	<? if (!count($components)) : ?>
		<tr>
			<td colspan="6" align="center">
			<?= @text('No Items Found'); ?>
			</td>
		</tr>
	<? endif; ?>
	</tbody>
</table>