<?php
/**
 * @author 		Ronald Pijpers
 * @copyright	Copyright (C) 2011 Intertron. All rights reserved.
 * @license		GNU/GPL version 3, see gpl.txt
 */

class ComCreateModelComponents extends ComDefaultModelDefault
{
	function getItem()
	{
		$item = parent::getItem();

		// Initial parsing of the Excel file to determine field types		
		if ($item->filename) {
			require_once JPATH_COMPONENT_ADMINISTRATOR.DS.'helper'.DS.'BiffWorkbook.inc.php';
			
			try {
				$doc = new CompoundDocument ('utf-8');
				$doc->parse (file_get_contents (JPATH_COMPONENT_ADMINISTRATOR.DS.'uploads'.DS.$item->filename));
				$wb = new BiffWorkbook ($doc);
				$wb->parse ();
			} catch (Exception $e) {
				$app = JFactory::getApplication();
				$app->enqueueMessage($e->getMessage());
				return false;
			}
			foreach ($wb->sheets as $sheetName => $sheet) {
				for ($col = 0; $col < $sheet->cols (); $col ++) {
					if (!isset ($sheet->cells[0][$col])) continue;
					$cell = $sheet->cells[0][$col];
					if (is_null ($cell->value)) {
						// skip column
					} else {
						$columnname = $this->clean ($cell->value);
						
						$isnumeric = true;
						$isint = true;
						$isdate = true;
						$strlen = 0;
						for ($row = 1; $row < $sheet->rows (); $row ++) {
							if (!isset ($sheet->cells[$row][$col])) continue;
							if (is_null ($sheet->cells[$row][$col]->value)) continue;
							
							$value = $sheet->cells[$row][$col]->value;
							if (is_numeric($value)) {
								if (is_int($value) == false) {
									$isint = false;
								}
							} else {
								$isnumeric = false;
								$strlen = max($strlen, strlen($value));
								// could it also be a date?
								$datearr = date_parse_from_format("j/n/y", $value); //needs PHP 5.3
								if ($datearr['year'] && $datearr['month'] && $datearr['day']) {
									// valid date
								} else {
									// no valid date
									$isdate = false;
								}
							}
						}
	
						if ($isnumeric) {
							$columns[$columnname] = 'numeric';
							if ($isint) {
								$columns[$columnname] = 'int';
							}
						} else {
							if ($isdate) {
								$columns[$columnname] = 'date';
							} else {
								$columns[$columnname] = 'varchar('.$strlen.')';
							}
						}
					}
				}
			}	
			$item->columns = $columns;
		}
				
		$this->_item = $item;
		return $item;
	}
	
	/**
	 * Clean input values into save values to use as file/component/view/databasetable
	 *  names
	 *  
	 * @param   string  The inputstring to clean  
	 * @return  string  The cleaned string
	 */
	function clean ($input)
	{
		$filter = KFilter::factory('slug');
		$result = $filter->sanitize($input);
		$result = str_replace('-', '', $result);
		return $result;		
	}
}