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

<?php $row = $this->_data['item'] ?>
<?php $data = $row->getData(); ?>

<div id="mainform">

<?php foreach ($data as $label => $value) : ?>
	<? if ($label != 'id') : ?>
	<label for="<?= $label ?>_field" class="mainlabel"><?= @text($label) ?></label>
	<span class="value"><?= $value ?></span><br />
	<? endif ?>
<?php endforeach ?>

</div>