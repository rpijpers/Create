<?php
/**
 * Microsoft Excel BIFF5-8 workbook reader
 * Based on:
 * http://sc.openoffice.org/excelfileformat.pdf
 * http://download.microsoft.com/download/0/B/E/0BE8BDD7-E5E8-422A-ABFD-4342ED7AD886/Excel97-2007BinaryFileFormat(xls)Specification.pdf
 *
 * @version 0.5.1
 * @author Ruslan V. Us <unclerus at gmail.com>
 * @package BiffReader
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

require_once JPATH_COMPONENT_ADMINISTRATOR.DS.'helper'.DS.'BiffWorkbookCell.inc.php';


/**
 * Excel workbook sheet
 */
class BiffWorkbookSheet
{
	const stateVisible = 0;
	const stateHidden = 1;
	const stateVeryHidden = 2;

	const typeWorksheet = 0;
	const typeChart = 2;
	const typeVBModule = 6;

	/**
	 * Sheet visibility state (stateVisible, stateHidden, stateVeryHidden)
	 * @var int
	 */
	public $state;

	/**
	 * Sheet type (typeWorksheet, typeChart, typeVBModule)
	 * @var int
	 */
	public $type;

	/**
	 * Worksheet substream offset
	 * @var int
	 */
	public $offset;

	/**
	 * Worksheet name
	 * @var string
	 */
	public $name;

	/**
	 * Two-dimensional array of BiffWorksheetCell. [row][cell]
	 * @var array
	 */
	public $cells = array ();

	/**
	 * Workbook
	 * @var BiffWorkbook
	 */
	public $workboook;

	private $_cols = 0;
	private $_rows = 0;

	public function __construct ($workbook, $name, $raw)
	{
		$this->workboook = $workbook;
		$this->name = $name;
		$this->state = $raw ['state'];
		$this->type = $raw ['type'];
		$this->offset = $raw ['offset'];
	}

	/**
	 * Columns count of worksheet
	 */
	public function cols ()
	{
		return $this->_cols;
	}

	/**
	 * Rows count of worksheet
	 */
	public function rows ()
	{
		return $this->_rows;
	}

	public function addCell ($row, $col, $value, $rawValue, $type, $style)
	{
		if ($col >= $this->_cols) $this->_cols = $col + 1;
		if ($row >= $this->_rows) $this->_rows = $row + 1;

		$this->cells [$row][$col] = new BiffWorkbookCell ($this, $row, $col, $value, $rawValue, $type, $style);
	}
}
?>