<? 
/**
 * @author 		Ronald Pijpers
 * @copyright	Copyright (C) 2011 Intertron. All rights reserved.
 * @license		GNU/GPL version 3, see gpl.txt
 */
?>
<? defined('KOOWA') or die('Restricted access'); ?>
<script src="media://lib_koowa/js/koowa.js" />
<style src="media://com_create/css/backend.css" />
<style src="media://com_create/css/form.css" />
<style src="media://com_create/css/aloa_form.css" />

<div id="mainform">
<form action="<?= @route('id='.$component->id) ?>" method="post" class="adminform" name="adminForm" enctype="multipart/form-data">

<label for="name_field" class="mainlabel"><?= @text('Name'); ?></label>
<input id="name_field" type="text" class="text" name="name" value="<?= $component->name; ?>" /><br />
<label for="itemsname_field" class="mainlabel"><?= @text('Items name'); ?></label>
<input id="itemsname_field" type="text" class="text" name="itemsname" value="<?= $component->itemsname; ?>" /><br />
<label for="itemname_field" class="mainlabel"><?= @text('Item name'); ?></label>
<input id="itemname_field" type="text" class="text" name="itemname" value="<?= $component->itemname; ?>" /><br />

<label for="filename_field" class="mainlabel"><?php echo JText::_( 'Filename' ); ?></label>
<?php if ($component->filename): ?>
<span class="value"><?= $component->filename ?></span>
<?php else : ?>
<input id="filename_field" type="file" name="filename" size="50" value="" />
<?php endif ?>
<br /><br />

<?php if (isset($component->columns)) : ?>
<label class="mainlabel"><?= @text('Columns'); ?></label><br />
<?php foreach ($component->columns as $key => $value) : ?>
<label for="<?= $key ?>_field" class="mainlabel"><?= $key ?></label>
<input id="<?= $key ?>_field" type="text" class="text" name="<?= $key ?>" value="<?= $value ?>" /><br />
<?php endforeach ?>
<?php endif ?>
</form>
</div>