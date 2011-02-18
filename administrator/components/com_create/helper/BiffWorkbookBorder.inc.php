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
 * Excel cells border
 */
class BiffWorkbookBorder extends BiffWorkbookElement implements IBiffWorkbookCss
{
	static private $_border_styles = array (
		0x00 => array ('none', null),
		0x01 => array ('solid', 1),
		0x02 => array ('solid', 2),
		0x03 => array ('dashed', 1),
		0x04 => array ('dotted', 1),
		0x05 => array ('solid', 3),
		0x06 => array ('double', 1),
		0x07 => array ('dotted', 1),
		0x08 => array ('dashed', 2),
		0x09 => array ('dashed', 1),
		0x0a => array ('dashed', 2),
		0x0b => array ('dashed', 1),
		0x0c => array ('dashed', 2),
		0x0d => array ('dashed', 2)
	);

	/**
	 * Border style
	 * @var string
	 */
	public $style;

	/**
	 * Border width, px
	 * @var int
	 */
	public $width;

	/**
	 * Border color
	 * @var string
	 */
	public $color;

	/**
	 * Border position (left, right, top, bottom)
	 * @var string
	 */
	public $type;

	public function build ()
	{
		list ($this->style, $this->width) = self::$_border_styles [$this->raw ['style']];
		$this->color = is_null ($this->raw ['color']) ? null : $this->_workbook->palette [$this->raw ['color']];
		$this->type = $this->raw ['type'];
	}

	public function css ()
	{
		return 'border-' . $this->type . ': '
			. ($this->style == 'none'
				? 'none'
				: $this->width . 'px ' . $this->style . ' #' . $this->color
			)
			. ';';
	}
}
?>