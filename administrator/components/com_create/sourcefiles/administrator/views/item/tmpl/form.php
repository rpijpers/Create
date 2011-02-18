<?
/**
 * @author 		Ronald Pijpers
 * @copyright	Copyright (C) 2011 Intertron. All rights reserved.
 * @license		GNU/GPL version 3, see gpl.txt
 */
?>
<? defined('KOOWA') or die('Restricted access'); ?>

<?= @helper('behavior.tooltip'); ?>
<script src="media://lib_koowa/js/koowa.js" />
<style src="media://com_component/css/form.css" />
<style src="media://com_component/css/aloa_form.css" />

<? $row = $this->_data['item'] ?>
<? $data = $row->getData(); ?>

<div id="mainform">
<form action="<?= @route('id='.$item->id) ?>" method="post" class="adminform" name="adminForm">

<? foreach ($data as $label => $value) : ?>
	<label for="<?= $label ?>_field" class="mainlabel"><?= @text($label) ?></label>
	<? if ($label == 'id') : ?>
	<input id="<?= $label ?>_field" type="text" disabled="disabled" class="text" name="<?= $label ?>" value="<?= $value ?>" /><br />
	<? else: ?>
	<input id="<?= $label ?>_field" type="text" class="text" name="<?= $label ?>" value="<?= $value ?>" /><br />
	<? endif ?>		
<? endforeach ?>

</form>
</div>