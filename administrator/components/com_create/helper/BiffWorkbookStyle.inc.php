<?php
/**
 * Microsoft Excel BIFF5-8 workbook reader
 * Based on:
 * http://sc.openoffice.org/excelfileformat.pdf
 * http://download.microsoft.com/download/0/B/E/0BE8BDD7-E5E8-422A-ABFD-4342ED7AD886/Excel97-2007BinaryFileFormat(xls)Specification.pdf
 *
 * @version 0.5.2
 * @author Ruslan V. Us <unclerus at gmail.com>
 * @todo excel custom formats parser
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
require_once JPATH_COMPONENT_ADMINISTRATOR.DS.'helper'.DS.'BiffWorkbookFont.inc.php';
require_once JPATH_COMPONENT_ADMINISTRATOR.DS.'helper'.DS.'BiffWorkbookBorder.inc.php';


/**
 * Excel cells format
 */
class BiffWorkbookStyle extends BiffWorkbookElement implements IBiffWorkbookCss
{
	const ftGeneral = 0;
	const ftNumber = 1;
	const ftDate = 2;
	
	static private $_horAlign = array (
		0 => 'left',
		1 => 'left',
		2 => 'center',
		3 => 'right',
		4 => 'justify',
		5 => 'justify',
		6 => 'center',
		7 => 'justify'
	);

	static private $_vertAlign = array (
		0 => 'top',
		1 => 'middle',
		2 => 'bottom',
		3 => 'middle',
		4 => 'middle'
	);

	static public $dateFormats = array (
		0x0e => 'd.m.Y',
		0x0f => 'd-M-Y',
		0x10 => 'd-M',
		0x11 => 'M-Y',
		0x12 => 'h:i a',
		0x13 => 'h:i:s a',
		0x14 => 'H:i',
		0x15 => 'H:i:s',
		0x16 => 'd.m.Y H:i',
		0x2d => 'i:s',
		0x2e => 'H:i:s',
		0x2f => 'i:s.S'
	);

	static public $numberFormats = array (
		0x01 => '%1.0f',	// '0'
		0x02 => '%1.2f',	// '0.00',
		0x03 => '%1.0f',	// '#,##0',
		0x04 => '%1.2f',	// '#,##0.00',
		0x05 => '$%1.0f',	// '$#,##0;($#,##0)',
		0x06 => '$%1.0f',	// '$#,##0;($#,##0)',
		0x07 => '$%1.2f',	// '$#,##0.00;($#,##0.00)',
		0x08 => '$%1.2f',	// '$#,##0.00;($#,##0.00)',
		0x09 => '%1.0f%%',	// '0%'
		0x0a => '%1.2f%%',	// '0.00%'
		0x0b => '%1.2f',	// '0.00E00',
		0x25 => '%1.0f',	// '#,##0;(#,##0)',
		0x26 => '%1.0f',	// '#,##0;(#,##0)',
		0x27 => '%1.2f',	// '#,##0.00;(#,##0.00)',
		0x28 => '%1.2f',	// '#,##0.00;(#,##0.00)',
		0x29 => '%1.0f',	// '#,##0;(#,##0)',
		0x2a => '$%1.0f',	// '$#,##0;($#,##0)',
		0x2b => '%1.2f',	// '#,##0.00;(#,##0.00)',
		0x2c => '$%1.2f',	// '$#,##0.00;($#,##0.00)',
		0x30 => '%1.0f'		// '##0.0E0';
	);

	/**
	 * Font of the style
	 * @var BiffWorkbookFont
	 */
	public $font = null;

	/**
	 * Horizontal text alignment
	 * @var string
	 */
	public $textAlign = 'left';

	/**
	 * Vertival text alignment
	 * @var string
	 */
	public $verticalAlign = 'bottom';

	/**
	 * Borders styles
	 * Array of BiffWorkbookBorder objects
	 * @var array
	 */
	public $borders = array ();

	/**
	 * Background color
	 * @var string
	 */
	public $backgroundColor = 'white';

	public $pattern;

	/**
	 * Format
	 * @var string
	 */
	public $format;

	/**
	 * Type of the format (ftGeneral, ftNumber, ftDate)
	 * @var int
	 */
	public $formatType;

	public function build ()
	{
		$this->font = $this->_workbook->fonts [$this->raw ['font']];
		$this->textAlign = self::$_horAlign [$this->raw ['align'] & 0x07];
		$this->verticalAlign = self::$_vertAlign [($this->raw ['align'] >> 4) & 0x07];
		if ($this->_workbook->biffVersion == 5)
		{
			$raws = array (
				'left'		=> array (($this->raw ['border_bg2'] >> 3) & 0x07),
				'right'		=> array (($this->raw ['border_bg2'] >> 6) & 0x07),
				'top'		=> array ($this->raw ['border_bg2'] & 0x07),
				'bottom'	=> array (($this->raw ['border_bg1'] >> 22) & 0x07)
			);
			$raws ['left'][1]		= $raws ['left'][0] == 0 ? null : ($this->raw ['border_bg2'] >> 16) & 0x7f;
			$raws ['right'][1]		= $raws ['right'][0] == 0 ? null : ($this->raw ['border_bg2'] >> 23) & 0x7f;
			$raws ['top'][1]		= $raws ['top'][0] == 0 ? null : ($this->raw ['border_bg2'] >> 9) & 0x7f;
			$raws ['bottom'][1]		= $raws ['bottom'][0] == 0 ? null : ($this->raw ['border_bg1'] >> 25) & 0x7f;

			foreach ($raws as $type => $value)
				$this->borders [$type] = new BiffWorkbookBorder (
					$this->_workbook,
					array (
						'type' => $type,
						'style' => $value [0],
						'color' => $value [1]
					)
				);

			$this->pattern = ($this->raw ['border_bg1'] >> 16) & 0x3f;
			$this->backgroundColor = $this->pattern == 0
				? $this->_workbook->palette [($this->raw ['border_bg1'] >> 7) & 0x7f]
				: $this->_workbook->palette [$this->raw ['border_bg1'] & 0x7f];
		}
		else
		{
			$raws = array (
				'left' =>		array ($this->raw ['border_bg1'] & 0x0f),
				'right' =>		array (($this->raw ['border_bg1'] >> 4) & 0x0f),
				'top' =>		array (($this->raw ['border_bg1'] >> 8) & 0x0f),
				'bottom' =>		array (($this->raw ['border_bg1'] >> 12) & 0x0f)
			);
			$raws ['left'][1]		= $raws ['left'][0] == 0 ? null : ($this->raw ['border_bg1'] >> 16) & 0x7f;
			$raws ['right'][1]		= $raws ['right'][0] == 0 ? null : ($this->raw ['border_bg1'] >> 23) & 0x7f;
			$raws ['top'][1]		= $raws ['top'][0] == 0 ? null : $this->raw ['border_bg2'] & 0x7f;
			$raws ['bottom'][1]		= $raws ['bottom'][0] == 0 ? null : ($this->raw ['border_bg2'] >> 7) & 0x7f;

			foreach ($raws as $type => $value)
				$this->borders [$type] = new BiffWorkbookBorder (
					$this->_workbook,
					array (
						'type' => $type,
						'style' => $value [0],
						'color' => $value [1]
					)
				);

			$this->pattern = $this->raw ['border_bg2'] >> 26;
			$this->backgroundColor = $this->pattern == 0
				? $this->_workbook->palette [($this->raw ['pattern'] >> 7) & 0x7f]
				: $this->_workbook->palette [$this->raw ['pattern'] & 0x7f];
		}

		if (isset (self::$dateFormats [$this->raw ['format']]))
		{
			$this->formatType = self::ftDate;
			$this->format = self::$dateFormats [$this->raw ['format']];
		}
		elseif (isset (self::$numberFormats [$this->raw ['format']]))
		{
			$this->formatType = self::ftNumber;
			$this->format = self::$numberFormats [$this->raw ['format']];
		}
		elseif (isset ($this->_workbook->formats [$this->raw ['format']]) && $this->_workbook->formats [$this->raw ['format']] != 'GENERAL')
		{
			if (preg_match ('/[^hmsday\/\-:\s]/i', $this->_workbook->formats [$this->raw ['format']]) == 0)
			{
				$this->formatType = self::ftDate;
				$this->format = str_replace (
					array ('mm', 'h', 'DD', 'MM', 'YY', 'YYYY'),
					array ('i', 'H', 'd', 'm', 'y', 'Y'),
					$this->_workbook->formats [$this->raw ['format']]
				);
			}
			else
			{
				$this->formatType = self::ftGeneral;
				$this->format = $this->_workbook->formats [$this->raw ['format']];
			}
		}
		else
		{
			$this->formatType = self::ftGeneral;
			$this->format = null;
		}
	}

	public function css ()
	{
		$result = $this->font->css ();
		foreach ($this->borders as $border)
			$result .= ' ' . $border->css ();
		$result .= ' ' . implode (
			'; ',
			array (
				'text-align: ' . $this->textAlign,
				'vertical-align: ' . $this->verticalAlign,
				'background-color: ' . $this->backgroundColor
			)
		) . ';';

		return $result;
	}
}
?>