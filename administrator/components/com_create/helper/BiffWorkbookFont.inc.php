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

require_once JPATH_COMPONENT_ADMINISTRATOR.DS.'helper'.DS.'BiffWorkbookElement.inc.php';

/**
 * Represents font in workbook
 */
class BiffWorkbookFont extends BiffWorkbookElement implements IBiffWorkbookCss
{
	/**
	 * Font family
	 * @var string
	 */
	public $family;

	/**
	 * Font size, pt
	 * @var int
	 */
	public $size;

	/**
	 * Font color
	 * @var string
	 */
	public $color;

	/**
	 * Font weight
	 * @var int
	 */
	public $weight;

	/**
	 * Underline
	 * @var bool
	 */
	public $underline;

	/**
	 * Italic
	 * @var bool
	 */
	public $italic;

	public function build ()
	{
		$this->family = $this->raw ['family'];
		$this->size = $this->raw ['size'] / 20;
		$this->color = $this->_workbook->palette [$this->raw ['color']];
		$this->weight = $this->raw ['weight'];
		$this->underline = $this->raw ['underline'] > 0;
		$this->italic = ($this->raw ['flags'] & 2) > 0;
	}

	public function css ()
	{
		return implode (
			'; ',
			array (
				'font-family: ' . $this->family,
				'font-size: ' . $this->size . 'pt',
				'color: #' . $this->color,
				'font-weight: ' . $this->weight,
				'text-decoration: ' . ($this->underline ? 'underline' : 'none'),
				'font-style: '. ($this->italic ? 'italic' : 'normal')
			)
		) . ';';
	}
}
?>