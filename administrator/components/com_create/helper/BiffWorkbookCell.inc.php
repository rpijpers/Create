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

require_once JPATH_COMPONENT_ADMINISTRATOR.DS.'helper'.DS.'BiffWorkbookStyle.inc.php';


/**
 * Excel workbook cell
 */
class BiffWorkbookCell
{
	const typeString = 0;
	const typeDate = 1;
	const typeNumber = 2;
	const typeBool = 3;
	const typeError = 4;

	/**
	 *
	 * @var BiffWorkbookSheet
	 */
	public $worksheet;

	/**
	 *
	 * @var BiffWorkbookStyle
	 */
	public $style;

	/**
	 * Cell horizontal position
	 * @var int
	 */
	public $row;

	/**
	 * Cell vertical position
	 * @var int
	 */
	public $col;

	/**
	 * Cell value
	 * @var mixed
	 */
	public $value;

	/**
	 * Raw cell value
	 * @var mixed
	 */
	public $rawValue;

	/**
	 * Colspan of the cell
	 * @var int
	 */
	public $colspan = 1;

	/**
	 * Rowspan of the cell
	 * @var int
	 */
	public $rowspan = 1;

	/**
	 * Cell type
	 * @var int
	 */
	public $type;

	public function __construct ($worksheet, $row, $col, $value, $rawValue, $type, $style)
	{
		$this->worksheet = $worksheet;
		$this->row = $row;
		$this->col = $col;
		$this->rawValue = $rawValue;
		$this->value = $value;
		$this->type = $type;
		$this->style = $style;
	}
}
?>