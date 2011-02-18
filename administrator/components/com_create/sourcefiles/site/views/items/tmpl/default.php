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

<? $columns = $this->_data['items']->getTable()->getColumns(); ?>

<form action="<?= @route()?>" method="get" name="adminForm">

<table class="adminlist"  style="clear: both;">
	<thead>
		<tr>
			<?
			$i = 0;
			foreach ($columns as $key => $value) :
				$i++;
				if ($i > 1 && $i < 11) :
			?>
			<th><?= @helper('grid.sort', array('column' => $key)) ?></th>
			<? 
				endif;
			endforeach ?>
		</tr>
	</thead>

	<tfoot>
		<tr>
			<td colspan="10">
			<?= @helper('paginator.pagination', array('total' => $total)) ?>
			</td>
		</tr>
	</tfoot>

	<tbody>
	<? $m = 0; ?>
	<? foreach ($items as $item) : ?>
		<tr class="<?= 'row'.$m; ?>">
			<? 
			$i = 0;
			foreach ($columns as $key => $value) :
				$i++;
				if ($i > 1 && $i < 11) :
			?>
			<td>
				<? if ($i == 2) : ?>
				<a href="<?= @route('view=item&id='.$item->id) ?>"><?= $item->$key ?></a>
				<? else: ?>
				<?= $item->$key ?>
				<? endif ?>
			</td>
			<?
				endif;
			endforeach
			?>
			</tr>
			<? $m = (1 - $m);?>
			<? endforeach ?>
			<? if (!count($items)) : ?>
			<tr>
			<td colspan="10" align="center">
			<?= @text('No Items Found'); ?>
			</td>
		</tr>
	<? endif; ?>
	</tbody>
</table>
</form>