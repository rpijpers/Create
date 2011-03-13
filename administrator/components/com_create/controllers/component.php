<?php
/**
 * @author 		Ronald Pijpers
 * @copyright	Copyright (C) 2011 Intertron. All rights reserved.
 * @license		GNU/GPL version 3, see gpl.txt
 */

class ComCreateControllerComponent extends ComDefaultControllerDefault 
{	
	function _actionApply(KCommandContext $context)
	{
		$this->uploadFile($context);
		return parent::_actionApply($context);
	}
	
	function _actionSave(KCommandContext $context)
	{
		$this->uploadFile($context);
		return parent::_actionSave($context);
	}
	
	/**
	 * Get filename from request and upload the file
	 * 
	 * @param   KCommandContext	A command context object
	 */
	function uploadFile($context)
	{
		$file = KRequest::get('files.filename', 'raw');
		if ($file['name']) {

			jimport('joomla.filesystem.file');		
			$baseDir = dirname(JPATH_COMPONENT_ADMINISTRATOR.DS.'uploads'.DS.$file['name']);
	
			$result = JFile::upload($file['tmp_name'], JPATH_COMPONENT_ADMINISTRATOR.DS.'uploads'.DS.$file['name']);
			if (!result) {
				$msg = JText::_('Upload failed');
				$app->enqueueMessage($msg);
				return false;
			}
			
			//add filename to context for saving
			$append = array('filename' => $file['name']);
			$context->data->append($append);
		}
	}
	
	/**
	 * Main action the generate the new Nooku component based on the uploaded file
	 * 
	 * @param   KCommandContext	A command context object
	 */
	function _actionGenerate(KCommandContext $context)
	{
		require_once JPATH_COMPONENT_ADMINISTRATOR.DS.'helper'.DS.'BiffWorkbook.inc.php';
		
		$app = JFactory::getApplication();		
		$component = $this->getModel()->getItem();
		
		// set up sanitize filter
		$config = array('separator' => '');
		$this->filter = KFilter::factory('slug', $config);
		
		//create mysql table and import data
		$result = $this->importExcel($component);
		if (!$result) {
			return false;
		}
		//show information message
		$msg = JText::_('Created table and imported data');
		$app->enqueueMessage($msg);

		$result = $this->copyfiles($component);
		if (!$result) {
			return false;
		}
		$msg = 'Copied files';
		$app->enqueueMessage($msg);
		
		$result = $this->createentry($component);
		if ($result == false) {
			return false;
		}
		$msg = 'Registered component';
		$app->enqueueMessage($msg);
		
		$app = JFactory::getApplication();
		$app->redirect('index.php?option=com_'.$this->filter->sanitize($component->name));
	}

	/**
	 * Create mysql table and import data
	 * 
	 * @param   object   The current item
	 * @return  boolean  Returns true on success, false on failure
	 */
	function importExcel ($component)
	{
		$app = JFactory::getApplication();
		
		try {
			$doc = new CompoundDocument ('utf-8');
			$doc->parse (file_get_contents (JPATH_COMPONENT_ADMINISTRATOR.DS.'uploads'.DS.$component->filename));
			$wb = new BiffWorkbook ($doc);
			$wb->parse ();
		} catch (Exception $e) {
			$app->enqueueMessage($e->getMessage());
			return false;
		}

		$componentname = $this->filter->sanitize($component->name);
		
		$itemsname = $component->itemsname ? $component->itemsname : 'items';
		$itemname  = $component->itemname ? $component->itemname : 'item';
		
		$itemsname = $this->filter->sanitize($itemsname);
		$itemname = $this->filter->sanitize($itemname);
		
		$tablename = '#__'.$componentname.'_'.$itemsname;
		
		$fields = array();			
		$db = JFactory::getDBO();		
		$query = 'CREATE TABLE '.$tablename.' (';
		$keyfield = $componentname.'_'.$itemname.'_id';
		$fields[] = $keyfield;
		$query .= '`'.$keyfield.'` SERIAL,';
		foreach ($wb->sheets as $sheetName => $sheet) {
			for ($col = 0; $col < $sheet->cols (); $col ++) {
				if (!isset ($sheet->cells[0][$col])) continue;
				$cell = $sheet->cells[0][$col];
				if (is_null ($cell->value)) {
					// skip column
				} else {
					$columnname = $this->filter->sanitize ($cell->value);
					$fields[] = $columnname;
					$query .= '`'.$columnname.'` VARCHAR( 255 ) NOT NULL ,';					
				}
			}

			$query .= 'PRIMARY KEY ( `'.$keyfield.'` ) ';
			$query .= ' );';
	
			$db->setQuery($query);
			$db->query();

			for ($row = 1; $row < $sheet->rows (); $row ++)
			{
				$query = 'INSERT INTO '.$tablename.' VALUES (';
				$query .= "'',";
				for ($col = 0; $col < $sheet->cols (); $col ++)
				{
					if (isset ($sheet->cells[$row][$col])) {
						$value = $sheet->cells[$row][$col]->value;
					} else {
						$value = '';
					}
					$query .= $db->Quote($value).', ';
				}
				//remove last comma
				$query = substr ($query, 0, strlen($query)-2 );
				$query .= ');';
				$db->setQuery($query);
				$db->query();
			}
		}
		
		return true;
	}
	
	/**
	 * Copy the sourcefiles to the new backend/frontend/media directories and customize them
	 * 
	 * @param   object   The current item
	 * @return  boolean  Returns true on success, false on failure
	 */
	function copyfiles ($component)
	{
		jimport('joomla.filesystem.file');
		$app = JFactory::getApplication();
		$componentname = $this->filter->sanitize($component->name);
		
		// backend files
		$src = JPATH_COMPONENT_ADMINISTRATOR.DS.'sourcefiles'.DS.'administrator';
		$dest = JPATH_ADMINISTRATOR.DS.'components'.DS.'com_'.$componentname;
		$result = JFolder::copy($src, $dest);
		if (!$result) {
			$msg = 'not copied';
			$app->enqueueMessage($msg);
			return false;
		}

		//rename the views to the actual names
		JFolder::move('items', $component->itemsname, $dest.DS.'views');
		JFolder::move('item', $component->itemname, $dest.DS.'views');
		JFile::move('component.php', $componentname.'.php', $dest);
				
		// Change general words into specific words in source files
		$componentphp = $dest.DS.$componentname.'.php';
		$result = $this->changeWords($componentphp, $component);
		if (!$result) {
			$app->enqueueMessage('Cant write to file'.$componentphp);
			return false;
		}
			
		$defaultphp = $dest.DS.'views'.DS.$component->itemsname.DS.'tmpl'.DS.'default.php';
		$result = $this->changeWords($defaultphp, $component);
		if (!$result) {
			$app->enqueueMessage('Cant write to file'.$result);
			return false;
		}
		
		$formphp = $dest.DS.'views'.DS.$component->itemname.DS.'tmpl'.DS.'form.php';
		$result = $this->changeWords($formphp, $component);
		if (!$result) {
			$app->enqueueMessage('Cant write to file'.$formphp);
			return false;
		}
		
		// media files
		$src = JPATH_COMPONENT_ADMINISTRATOR.DS.'sourcefiles'.DS.'media';
		$dest = JPATH_ROOT.DS.'media'.DS.'com_'.$componentname;
		$result = JFolder::copy($src, $dest);
		if (!$result) {
			$msg = 'not copied 2';
			$app->enqueueMessage($msg);
			return false;
		}
			
		// frontend files
		$src = JPATH_COMPONENT_ADMINISTRATOR.DS.'sourcefiles'.DS.'site';
		$dest = JPATH_SITE.DS.'components'.DS.'com_'.$componentname;
		$result = JFolder::copy($src, $dest);
		if (!$result) {
			$msg = 'not copied 3';
			$app->enqueueMessage($msg);
			return false;
		}
		
		//rename the views to the actual names
		JFolder::move('items', $component->itemsname, $dest.DS.'views');
		JFolder::move('item', $component->itemname, $dest.DS.'views');
		JFile::move('component.php', $componentname.'.php', $dest);			

		// Change general words into specific words in source files
		$componentphp = $dest.DS.$componentname.'.php';
		$result = $this->changeWords($componentphp, $component);
		if (!$result) {
			$app->enqueueMessage('Cant write to file'.$componentphp);
			return false;
		}
		
		$defaultphp = $dest.DS.'views'.DS.$component->itemsname.DS.'tmpl'.DS.'default.php';
		$result = $this->changeWords($defaultphp, $component);
		if (!$result) {
			$app->enqueueMessage('Cant write to file'.$result);
			return false;
		}
		
		$formphp = $dest.DS.'views'.DS.$component->itemname.DS.'tmpl'.DS.'default.php';
		$result = $this->changeWords($formphp, $component);
		if (!$result) {
			$app->enqueueMessage('Cant write to file'.$formphp);
			return false;
		}
		
		return true;
	}
	
	/**
	 * Create new entry in the component table to register the new component
	 * 
	 * @param object  The current item
	 */
	function createentry ($component)
	{
		$componentname = $this->filter->sanitize($component->name);
		
		$query = "INSERT INTO `#__components` VALUES('', '".$component->name."', 'option=com_".$componentname."', 0, 0, 'option=com_".$componentname."', '".$component->name."', 'com_".$componentname."', 0, 'js/ThemeOffice/component.png', 0, '', 1);";
		$db = JFactory::getDBO();		
		$db->setQuery($query);
		return $db->query();
	}
	
	/**
	 * Change the general words into specific words in copied sourcefiles
	 * 
	 * @string  string   Filename of source file
	 * @return  boolean  Returns true on success, false on failure
	 */
	function changeWords ($file, $component)
	{
		if (!JFile::exists($file)) {
			$msg = JText::_('File not found').': '.$file;
			$app = JFactory::getApplication();
			$app->enqueueMessage($msg);
			return false;
		}
		$body = JFile::read($file);
		$body = str_replace('component', $this->filter->sanitize($component->name), $body);	
		$body = str_replace('items', $this->filter->sanitize($component->itemsname), $body);
		$body = str_replace('item', $this->filter->sanitize($component->itemname), $body);

		return JFile::write($file, $body);
	}
}