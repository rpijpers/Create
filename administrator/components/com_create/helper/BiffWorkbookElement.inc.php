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

class BiffWorkbookException extends Exception {}

/**
 * Base class for BIFF elements
 */
abstract class BiffWorkbookElement
{
	/**
	 *
	 * @var BiffWorkbook
	 */
	protected $_workbook = null;

	/**
	 * Raw value of element
	 * @var mixed
	 */
	public $raw = null;

	abstract public function build ();

	/**
	 * Constructor
	 * @param BiffWorkbook $workbook Workbook
	 * @param mixed $raw Raw data
	 */
	public function __construct ($workbook, $raw)
	{
		$this->raw = $raw;
		$this->_workbook = $workbook;
		$this->build ();
	}
}

/**
 * Interface for style elements
 */
interface IBiffWorkbookCss
{
	/**
	 * Build CSS properties string
	 * @return string CSS
	 */
	public function css ();
}
?>
