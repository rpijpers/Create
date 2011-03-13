<?php
/**
 * Microsoft Excel BIFF5-8 workbook reader
 * Based on:
 * http://sc.openoffice.org/excelfileformat.pdf
 * http://download.microsoft.com/download/0/B/E/0BE8BDD7-E5E8-422A-ABFD-4342ED7AD886/Excel97-2007BinaryFileFormat(xls)Specification.pdf
 *
 * @version 0.5.3
 * @author Ruslan V. Us <unclerus at gmail.com>
 * @todo use proper currency sign
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

require_once JPATH_COMPONENT_ADMINISTRATOR.DS.'helper'.DS.'BiffWorkbookSheet.inc.php';
require_once JPATH_COMPONENT_ADMINISTRATOR.DS.'helper'.DS.'CompoundDocument.inc.php';


/**
 * Excel workbook
 */
class BiffWorkbook
{
	static private $_defaultPalette = array (
		0x00 => '000000', 0x01 => 'FFFFFF', 0x02 => 'FF0000', 0x03 => '00FF00', 0x04 => '0000FF', 0x05 => 'FFFF00',
		0x06 => 'FF00FF', 0x07 => '00FFFF', 0x08 => '000000', 0x09 => 'FFFFFF', 0x0a => 'FF0000', 0x0b => '00FF00',
		0x0c => '0000FF', 0x0d => 'FFFF00', 0x0e => 'FF00FF', 0x0f => '00FFFF', 0x10 => '800000', 0x11 => '008000',
		0x12 => '000080', 0x13 => '808000', 0x14 => '800080', 0x15 => '008080', 0x16 => 'C0C0C0', 0x17 => '808080',
		0x40 => '000000', 0x41 => 'FFFFFF', 0x43 => 'D4D0C8', 0x4d => '000000', 0x4e => 'FFFFFF', 0x4f => '000000',
		0x50 => 'FFFFCE', 0x51 => '000000', 0x7FFF => '000000'
	);

	static private $_biff5Palette = array (
		0x18 => '8080FF', 0x19 => '802060', 0x1a => 'FFFFC0', 0x1b => 'A0E0F0', 0x1c => '600080', 0x1d => 'FF8080',
		0x1e => '0080C0', 0x1f => 'C0C0FF', 0x20 => '000080', 0x21 => 'FF00FF', 0x22 => 'FFFF00', 0x23 => '00FFFF',
		0x24 => '800080', 0x25 => '800000', 0x26 => '008080', 0x27 => '0000FF', 0x28 => '00CFFF', 0x29 => '69FFFF',
		0x2a => 'E0FFE0', 0x2b => 'FFFF80', 0x2c => 'A6CAF0', 0x2d => 'DD9CB3', 0x2e => 'B38FEE', 0x2f => 'E3E3E3',
		0x30 => '2A6FF9', 0x31 => '3FB8CD', 0x32 => '488436', 0x33 => '958C41', 0x34 => '8E5E42', 0x35 => 'A0627A',
		0x36 => '624FAC', 0x37 => '969696', 0x38 => '1D2FBE', 0x39 => '286676', 0x3a => '004500', 0x3b => '453E01',
		0x3c => '6A2813', 0x3d => '85396A', 0x3e => '4A3285', 0x3f => '424242'
	);

	static private $_biff8Palette = array (
		0x18 => '9999FF', 0x19 => '993366', 0x1a => 'FFFFCC', 0x1b => 'CCFFFF', 0x1c => '660066', 0x1d => 'FF8080',
		0x1e => '0066CC', 0x1f => 'CCCCFF', 0x20 => '000080', 0x21 => 'FF00FF', 0x22 => 'FFFF00', 0x23 => '00FFFF',
		0x24 => '800080', 0x25 => '800000', 0x26 => '008080', 0x27 => '0000FF', 0x28 => '00CCFF', 0x29 => 'CCFFFF',
		0x2a => 'CCFFCC', 0x2b => 'FFFF99', 0x2c => '99CCFF', 0x2d => 'FF99CC', 0x2e => 'CC99FF', 0x2f => 'FFCC99',
		0x30 => '3366FF', 0x31 => '33CCCC', 0x32 => '99CC00', 0x33 => 'FFCC00', 0x34 => 'FF9900', 0x35 => 'FF6600',
		0x36 => '666699', 0x37 => '969696', 0x38 => '003366', 0x39 => '339966', 0x3a => '003300', 0x3b => '333300',
		0x3c => '993300', 0x3d => '993366', 0x3e => '333399', 0x3f => '333333'
	);

	static private $_themeColors = array (
		'FFFFFF', // Text/Background - Dark 1  ???
		'000000', // Text/Background - Light 1 ???
		'1F497D', // Dark 2
		'EEECE1', // Light 2
		'4F81BD', // Accent 1
		'C0504D', // Accent 2
		'9BBB59', // Accent 3
		'8064A2', // Accent 4
		'4BACC6', // Accent 5
		'F79646', // Accent 6
		'0000FF', // Hyperlink
		'800080'  // Followed Hyperlink
	);

	static private $_errorCodes = array (
		0x00 => '#NULL!',
		0x07 => '#DIV/0!',
		0x0f => '#VALUE!',
		0x17 => '#REF!',
		0x1d => '#NAME?',
		0x24 => '#NUM!',
		0x2a => '#N/A',
	);

	public $document;
	public $biffVersion;
	public $sheets = array ();
	public $fonts = array ();
	public $palette = array ();
	public $sst = array ();
	public $styles = array ();
	public $formats = array ();

	private $_id = null;
	private $_offset = 0;
	/**
	 *
	 * @var BiffWorkbookSheet
	 */
	private $_sheet = null;
	private $_sheetsIndex = array ();
	private $_datemode = 1;

	static private $_records = array (
		// Globals
		0x0031 => 'Font',
		0x00e0 => 'Xf',
		0x087d => 'XfExt',
		0x0085 => 'Sheet',
		0x041e => 'Format',
		0x0022 => 'Datemode',
		0x00fc => 'Sst',
		0x0092 => 'Palette',
		// Sheet
		0x0204 => 'Label',
		0x00d6 => 'Label',
		0x0205 => 'BoolErr',
		0x0006 => 'Formula',
		0x00fd => 'LabelSst',
		0x00e5 => 'MergedCells',
		0x0203 => 'Number',
		0x027e => 'Rk',
		0x00bd => 'MulRk',
		0x0201 => 'Blank',
		0x00be => 'MulBlank'
	);

	static public function ieee754 ($value)
	{
		if (($value & 0x7fffffff) == 0) return 0;
		if (($value & 0x02) != 0) $result = $value >> 2;
		else
		{
			$exp = ($value & 0x7ff00000) >> 20;
			$mantissa = (0x100000 | ($value & 0x000ffffc));
			$result = $mantissa / (1 << (20 - ($exp - 1023)));
			if (($value & 0x80000000) >> 31) $result = -$result;
		}
		if (($value & 0x01) != 0) $result /= 100;
		return $result;
	}

	private function _data ($size, $subOffset = 0)
	{
		return substr (
			$this->document->directory [$this->_id]['data'],
			$this->_offset + $subOffset,
			$size
		);
	}

	private function _decode ($data)
	{
		return @iconv ('utf-16le', $this->document->charset . '//IGNORE', $data);
	}

	private function _value ($subOffset = 0, $size = 4, $format = 'V')
	{
		if ($size <= 0) throw new BiffWorkbookException ('Invalid data size');
		$result = unpack ($format, $this->_data ($size, $subOffset));
		return $result [1];
	}

	private function _getDate (BiffWorkbookStyle $style, $value)
	{
		if ($value > 1)
		{
			//$utcValue = round (($value - ($this->_datemode ? 24107 : 25569) + 1) * 86400);
			$utcValue = round (($value - ($this->_datemode ? 24107 : 25569)) * 86400);
			$value = date ($style->format, $utcValue);
			$raw = $utcValue;
			return array ($value, $raw);
		}
		$raw = $value;
		$hours = floor ($value * 24);
		$mins = floor ($value * 24 * 60) - $hours * 60;
		$secs = floor ($value * 86400) - $hours * 60 * 60 - $mins * 60;
		$value = date ($style->format, mktime ($hours, $mins, $secs));
		return array ($value, $raw);
	}

	private function _decodeRk ($row, $col, BiffWorkbookStyle $style, $raw)
	{
		$number = self::ieee754 ($raw);
		if ($style->formatType == BiffWorkbookStyle::ftDate)
			list ($value, $raw) = $this->_getDate ($style, $number);
		else $value = $number;
		//elseif ($style->format != '')
		//	$value = sprintf ($style->format, $value);
		$this->_sheet->addCell ($row, $col, $value, $number, BiffWorkbookCell::typeNumber, $style);
	}

	private function _string ($subOffset, $lenBytes = 1, $recordSize = null)
	{
		if ($this->biffVersion == 5)
		{
			$length = $this->_value ($subOffset, $lenBytes, $lenBytes == 2 ? 'v' : 'C');
			return array (
				$this->_data ($length, $subOffset + $lenBytes),
				$lenBytes + $length
			);
		}
		$str = $lenBytes == 2
			? unpack ('vlen/Cflags', $this->_data (3, $subOffset))
			: unpack ('Clen/Cflags', $this->_data (2, $subOffset));
		$subOffset += $lenBytes + 1;
		$size = $lenBytes + 1;
		if (($str ['flags'] & 0x08) > 0)
		{
			$size += $this->_value ($subOffset, 2, 'v') * ($this->biffVersion == 5 ? 2 : 4) + 2;
			$subOffset += 2;
		}
		if (($str ['flags'] & 0x04) > 0)
		{
			$size += $this->_value ($subOffset, 2, 'v') + 2;
			$subOffset += 2;
		}
		if (($str ['flags'] & 0x01) > 0)
		{
			$length = $str ['len'] * 2;
			$realLen = $recordSize - $subOffset < $length
				? $recordSize - $subOffset
				: $length;
			$string = $this->_decode ($this->_data ($realLen, $subOffset));
		}
		else
		{
			$length = $str ['len'];
			$realLen = $recordSize - $subOffset < $length
				? $recordSize - $subOffset
				: $length;
			$string = $this->_data ($realLen, $subOffset);
		}
		$size += $length;
		$subOffset += $length;
		return array ($string, $size);
	}

	static private function _setBrightness ($color, $tint_shade)
	{
		$delta = round (255 * ($tint_shade / 32766));
		$color = hexdec ($color);
		$r = max (min ((($color >> 16) & 0xff) + $delta, 0xff), 0);
		$g = max (min ((($color >> 8) & 0xff) + $delta, 0xff), 0);
		$b = max (min (($color & 0xff) + $delta, 0xff), 0);
		return dechex (($r << 16) + ($g << 8) + $b);
	}

	private function _xfExtColor ($subOffset)
	{
		$data = unpack ('vtype/stint_shade', $this->_data (4, $subOffset));
		switch ($data ['type'])
		{
			case 0:
				// automatic
				return '000000';
			case 1:
				// palette
				return $this->_palette [$this->_value ($subOffset + 4, 2, 'v')];
			case 2:
				// rgb
				return $this->_value ($subOffset + 4, 3, 'H6');
			case 3:
				return self::_setBrightness (
					self::$_themeColors [$this->_value ($subOffset + 4, 1, 'C')],
					$data ['tint_shade']
				);
		}
	}

	/**
	 * FONT (0x0031)
	 * This record contains information about a used font, including character
	 * formatting. All FONT records occur together in a sequential list. Other
	 * records referencing a FONT record contain an index into this list.
	 * (!!!) The font with index 4 is omitted in all BIFF versions. This means
	 * the first four fonts have zero-based indexes, and the fifth font and all
	 * following fonts are referenced with one-based indexes.
	 */
	private function _onFont ($record)
	{
		$raw = unpack ('vsize/vflags/vcolor/vweight/vescapement/Cunderline/Cfamily/Ccharset', $this->_data (14));
		if (count ($this->fonts) == 4) $this->fonts [] = null;
		list ($raw ['family'], $size) = $this->_string (14, 1, $record ['size']);
		$this->fonts [] = $f = new BiffWorkbookFont ($this, $raw);
	}

	/**
	 * PALETTE (0x0092)
	 * This record contains the definition of all user-defined colours available for
	 * cell and object formatting. This record is optional. If it is omitted, a
	 * built-in default colour table will be used.
	 */
	private function _onPalette ($record)
	{
		$colors = $this->_value (0, 2, 'v');
		for ($i = 0; $i < $colors; $i ++)
			$this->palette [0x08 + $i] = $this->_value (2 + $i * 4, 3, 'H6');

		foreach ($this->styles as $style)
			$style->build ();
		foreach ($this->fonts as $font)
			if ($font) $font->build ();
	}

	/**
	 * SHEET (0x0085)
	 * This record is located in the Workbook Globals Substream and represents a sheet
	 * inside the workbook. One SHEET record is written for each sheet. It stores the
	 * sheet name and a stream offset to the BOF record of the respective Sheet Substream
	 * within the Workbook Stream.
	 */
	private function _onSheet ($record)
	{
		$raw = unpack ('Voffset/Cstate/Ctype', $this->_data (6));
		list ($name, $size) = $this->_string (6, 1, $record ['size']);
		$sheet = new BiffWorkbookSheet ($this, $name, $raw);
		$this->sheets [$name] = $this->_sheetsIndex [$sheet->offset] = $sheet;
	}

	/**
	 * FORMAT (0x041e)
	 * This record contains information about a number format. All FORMAT records
	 * occur together in a sequential list.
	 */
	private function _onFormat ($record)
	{
		$index = $this->_value (0, 2, 'v');
		if ($index > 0x30)
			list ($this->formats [$index], $size) = $this->_string (
				2,
				$this->biffVersion == 8 ? 2 : 1,
				$record ['size']
			);
	}

	/**
	 * DATEMODE (0x0022)
	 * This record specifies the base date for displaying date values.
	 * All dates are stored as count of days past this base date.
	 * 0 = Base date is 1899-Dec-31 (the cell value 1 represents 1900-Jan-01)
	 * 1 = Base date is 1904-Jan-01 (the cell value 1 represents 1904-Jan-02)
	 */
	private function _onDatemode ($record)
	{
		$this->_datemode = $this->_value (0, 2, 'v');
	}

	/**
	 * SST (0x00fc)
	 * This record contains a list of all strings used anywhere in the workbook.
	 * Each string occurs only once. The workbook uses indexes into the list to
	 * reference the strings.
	 */
	private function _onSst ($record)
	{
		$sst = unpack ('Vtotal/Vnumber', $this->_data (8));
		$offset = 8;
		for ($i = 0; $i < $sst ['number']; $i ++)
		{
			if ($offset == $record ['size'])
			{
				$this->_offset += $record ['size'];
				$record = unpack ('vid/vsize', $this->_data (4));
				if ($record ['id'] != 0x003c) return true;
				$this->_offset += 4;
				$offset = 0;
			}
			$str = unpack ('vlen/Cflags', $this->_data (3, $offset));
			$offset += 3;
			$ascii = ($str ['flags'] & 0x01) == 0;
			$asian = ($str ['flags'] & 0x04) != 0;
			$rich = ($str ['flags'] & 0x08) != 0;
			if ($rich)
			{
				$runs = $this->_value ($offset, 2, 'v');
				$offset += 2;
			}
			if ($asian)
			{
				$asianLen = $this->_value ($offset, 4, 'V');
				$offset += 4;
			}
			$length = $ascii ? $str ['len'] : $str ['len'] * 2;
			if ($offset + $length <= $record ['size'])
			{
				$string = $ascii
					? $this->_data ($length, $offset)
					: $this->_decode ($this->_data ($length, $offset));
				$offset += $length;
			}
			else
			{
				// CONTINUE
				$bytesRead = $record ['size'] - $offset;
				$charsLeft = $str ['len'] - ($ascii ? $bytesRead : $bytesRead / 2);
				$string = $ascii
					? $this->_data ($bytesRead, $offset)
					: $this->_decode ($this->_data ($bytesRead, $offset));

				if ($charsLeft == 0)
				{
					$offset = $record ['size'];
					$this->sst [] = $string;
					continue;
				}

				while ($charsLeft > 0)
				{
					$this->_offset += $record ['size'];
					$record = unpack ('vid/vsize', $this->_data (4));
					if ($record ['id'] != 0x003c) return true;
					$this->_offset += 4;
					$option = $this->_value (0, 1, 'C');
					$offset = 1;
					if ($option == 0)
					{
						$length = min ($charsLeft, $record ['size'] - $offset);
						$string .= $this->_data ($length, $offset);
						$charsLeft -= $length;
					}
					else
					{
						$length = min ($charsLeft * 2, $record ['size'] - $offset);
						$string .= $this->_decode ($this->_data ($length, $offset));
						$charsLeft -= $length / 2;
					}
					$offset += $length;
				}
			}
			if ($rich) $offset += $runs * 4;
			if ($asian) $offset += $asianLen;
			$this->sst [] = $string;
		}
		$this->_offset += $record ['size'];
		return true;
	}

	/**
	 * XF (0x00e0)
	 * This record contains formatting information for cells, rows, columns or styles.
	 */
	private function _onXf ($record)
	{
		$this->styles [] = new BiffWorkbookStyle (
			$this,
			unpack (
				$this->biffVersion == 5
					? 'vfont/vformat/vtype_prot/Calign/Corientation_attr/Vborder_bg1/Vborder_bg2'
					: 'vfont/vformat/vtype_prot/Calign/Crotation/Cident/Cused_attribs/Vborder_bg1/Vborder_bg2/vpattern',
				$this->_data ($record ['size'])
			)
		);
	}

	/**
	 * XFEXT (0x087d)
	 * When writing XF records to BIFF8 format from Office Excel 2007 or later, if
	 * the XF record uses new formatting properties then a BIFF8 compatible XF
	 * record will be written followed by an XFEXT record that references that XF
	 * and contains additional information that can be used to restore the new
	 * properties when the document is opened again in Office Excel 2007 or later.
	 * @todo More XFEXT extTypes processing
	 */
	private function _onXfExt ($record)
	{
		$data = unpack ('vrt/vflags/x8/vversion/vxf/x2/vnum', $this->_data (20));
		$offset = 20;
		for ($i = 0; $i < $data ['num']; $i ++)
		{
			$ext = unpack ('vtype/vsize', $this->_data (4, $offset));
			switch ($ext ['type'])
			{
				case 4:
					if ($this->styles [$data ['xf']]->pattern > 0)
						$this->styles [$data ['xf']]->backgroundColor = $this->_xfExtColor ($offset + 4);
					break;
				case 5:
					if ($this->styles [$data ['xf']]->pattern == 0)
						$this->styles [$data ['xf']]->backgroundColor = $this->_xfExtColor ($offset + 4);
					break;
				case 7:
					$this->styles [$data ['xf']]->borders ['top']->color = $this->_xfExtColor ($offset + 4);
					break;
				case 8:
					$this->styles [$data ['xf']]->borders ['bottom']->color = $this->_xfExtColor ($offset + 4);
					break;
				case 9:
					$this->styles [$data ['xf']]->borders ['left']->color = $this->_xfExtColor ($offset + 4);
					break;
				case 10:
					$this->styles [$data ['xf']]->borders ['right']->color = $this->_xfExtColor ($offset + 4);
					break;
				case 13:
					$this->styles [$data ['xf']]->font->color = $this->_xfExtColor ($offset + 4);
					break;
			}
			$offset += $ext ['size'];
		}
	}

	/**
	 * LABEL (0x0204)
	 * RSTRING (0x00d6)
	 * This record represents a cell that contains a string. In BIFF8 it is usually
	 * replaced by the LABELSST record.	Excel still uses this record, if it copies
	 * unformatted text cells to the clipboard.
	 */
	private function _onLabel ($record)
	{
		$raw = unpack ('vrow/vcol/vxf', $this->_data (6));
		list ($value, $size) = $this->_string (6, 2, $record ['size']);
		$this->_sheet->addCell (
			$raw ['row'],
			$raw ['col'],
			$value,
			$value,
			BiffWorkbookCell::typeString,
			$this->styles [$raw ['xf']]
		);
	}

	/**
	 * BOOLERR (0x0205)
	 * This record represents a Boolean value or error value cell.
	 */
	private function _onBoolErr ($record)
	{
		$raw = unpack ('vrow/vcol/vxf/Cvalue/Ctype', $this->_data (8));
		if ($raw ['type'] > 0) $value = self::$_errorCodes [$raw ['value']];
			else $value = $raw ['value'] > 0;
		$this->_sheet->addCell (
			$raw ['row'],
			$raw ['col'],
			$value,
			$raw ['value'],
			$raw ['type'] > 0 ? BiffWorkbookCell::typeError : BiffWorkbookCell::typeBool,
			$this->styles [$raw ['xf']]
		);
	}

	/**
	 * LABELSST (0x00fd)
	 * This record represents a cell that contains a string. It replaces the LABEL
	 * record and RSTRING record used in BIFF2-BIFF5.
	 */
	private function _onLabelSst ($record)
	{
		$raw = unpack ('vrow/vcol/vxf/Vsst', $this->_data (10));
		$this->_sheet->addCell (
			$raw ['row'],
			$raw ['col'],
			$this->sst [$raw ['sst']],
			$raw ['sst'],
			BiffWorkbookCell::typeString,
			$this->styles [$raw ['xf']]
		);
	}

	/**
	 * FORMULA (0x0006)
	 * This record contains the token array and the result of a formula cell.
	 */
	private function _onFormula ($record)
	{
		$raw = unpack ('vrow/vcol/vxf/C8result', $this->_data (14));
		$move = false;
		$style = $this->styles [$raw ['xf']];
		if ($raw ['result7'] == 0xff && $raw ['result8'] == 0xff)
			switch ($raw ['result1'])
			{
				case 0x00:
					// string
					$type = BiffWorkbookCell::typeString;
					$this->_offset += $record ['size'];
					$record = unpack ('vid/vsize', $this->_data (4));
					if ($record ['id'] != 0x0207) return true;
					$this->_offset += 4;
					list ($value, $size) = $this->_string (0, 2, $record ['size']);
					$rawValue = $value;
					$this->_offset += $size;
					$move = true;
					break;
				case 0x01:
					// boolean
					$type = BiffWorkbookCell::typeBool;
					$rawValue = $raw ['result3'];
					$value = $rawValue > 0;
					$move = false;
					break;
				case 0x02:
					// error
					$type = BiffWorkbookCell::typeError;
					$rawValue = $raw ['result3'];
					$value = self::$_errorCodes [$rawValue];
					$move = false;
					break;
				case 0x03:
					// empty
					$type = BiffWorkbookCell::typeString;
					$rawValue = $value = null;
					$move = false;
					break;
			}
		else
		{
			$rawValue = $this->_value (6, 8, 'd');
			if ($style->formatType == BiffWorkbookStyle::ftDate)
			{
				list ($value, $rawValue) = $this->_getDate ($style, $rawValue);
				$type = BiffWorkbookCell::typeDate;
			}
			else
			{
				$value = $rawValue;
				$type = BiffWorkbookCell::typeNumber;
			}
		}
		$this->_sheet->addCell (
			$raw ['row'],
			$raw ['col'],
			$value,
			$rawValue,
			$type,
			$style
		);
		return $move;
	}

	/**
	 * NUMBER (0x0203)
	 * This record represents a cell that contains a floating-point value.
	 */
	private function _onNumber ($record)
	{
		$raw = unpack ('vrow/vcol/vxf/dvalue', $this->_data ($record ['size']));
		$style = $this->styles [$raw ['xf']];
		if ($style->formatType == BiffWorkbookStyle::ftDate)
			list ($value, $rawValue) = $this->_getDate ($style, $raw ['value']);
		else
		{
			$value = $rawValue = $raw ['value'];
			//if ($style->format != '') $value = sprintf ($style->format, $value);
		}
		$this->_sheet->addCell (
			$raw ['row'],
			$raw ['col'],
			$value,
			$rawValue,
			BiffWorkbookCell::typeNumber,
			$style
		);
	}

	/**
	 * RK (0x027e)
	 * This record represents a cell that contains an RK value (encoded integer or
	 * floating-point value). If a floating-point value cannot be encoded to an RK
	 * value, a NUMBER record will be written.
	 */
	private function _onRk ($record)
	{
		$raw = unpack ('vrow/vcol/vxf/Vvalue', $this->_data (10));
		$this->_decodeRk ($raw ['row'], $raw ['col'], $this->styles [$raw ['xf']], $raw ['value']);
	}

	/**
	 * MULRK (0x00bd)
	 * This record represents a cell range containing RK value cells.
	 * All cells are located in the same row.
	 */
	private function _onMulRk ($record)
	{
		$raw = unpack ('vrow/vfirst_col', $this->_data (4));
		$lastCol = $this->_value ($record ['size'] - 2, 2, 'v');
		for ($col = $raw ['first_col'], $offset = 4; $col <= $lastCol; $col ++, $offset += 6)
		{
			$rk = unpack ('vxf/Vvalue', $this->_data (6, $offset));
			$this->_decodeRk ($raw ['row'], $col, $this->styles [$rk ['xf']], $rk ['value']);
		}
	}

	/**
	 * BLANK (0x0201)
	 * This record represents an empty cell. It contains the cell address and
	 * formatting information.
	 */
	private function _onBlank ($record)
	{
		$raw = unpack ('vrow/vcol/vxf', $this->_data (6));
		$this->_sheet->addCell (
			$raw ['row'],
			$raw ['col'],
			null,
			null,
			BiffWorkbookCell::typeString,
			$this->styles [$raw ['xf']]
		);
	}

	/**
	 * MULBLANK (0x00be)
	 * This record represents a cell range of empty cells. All cells are located
	 * in the same row.
	 */
	private function _onMulBlank ($record)
	{
		$data = unpack ('vrow/vfc', $this->_data (4));
		$lc = $this->_value ($record ['size'] - 2, 2, 'v');
		for ($i = 0; $i < $lc - $data ['fc'] + 1; $i ++)
			$this->_sheet->addCell (
				$data ['row'],
				$i + $data ['fc'],
				null,
				null,
				BiffWorkbookCell::typeString,
				$this->styles [$this->_value (4 + $i * 2, 2, 'v')]
			);
	}

	/**
	 * MERGEDCELLS (0x00e5)
	 * This record contains the addresses of merged cell ranges in the current sheet.
	 */
	private function _onMergedCells ($record)
	{
		$ranges = $this->_value (0, 2, 'v');
		for ($i = 0; $i < $ranges; $i ++)
		{
			$range = unpack ('vfr/vlr/vfc/vlc', $this->_data (8, $i * 8 + 2));
			$rowspan = $range ['lr'] - $range ['fr'] + 1;
			$colspan = $range ['lc'] - $range ['fc'] + 1;
			if ($rowspan > 1)
			{
				$this->_sheet->cells [$range ['fr']][$range ['fc']]->rowspan = $rowspan;
				/*
				if (!isset ($this->sheets [$this->_sheet]['rowspan'][$range ['fr']]))
					$this->sheets [$this->_sheet]['rowspan'][$range ['fc']] = $rowspan;
					else $this->sheets [$this->_sheet]['rowspan'][$range ['fc']] += $rowspan;
				*/
			}
			if ($colspan > 1)
			{
				$this->_sheet->cells [$range ['fr']][$range ['fc']]->colspan = $colspan;
				/*
				if (!isset ($this->sheets [$this->_sheet]['colspan'][$range ['fr']]))
					$this->sheets [$this->_sheet]['colspan'][$range ['fr']] = $colspan;
					else $this->sheets [$this->_sheet]['colspan'][$range ['fr']] += $colspan;
				*/
			}
		}
	}

	private function _substream ()
	{
		if ($this->_offset >= $this->document->directory [$this->_id]['desc']['size']) return;

		$bof = unpack ('vbof/vsize/vversion/vtype/vid/vyear', $this->_data (12));
		if ($bof ['bof'] != 0x0809) return;
		switch ($bof ['type'])
		{
			case 5:
				if ($bof ['version'] != 0x0500 && $bof ['version'] != 0x0600)
					throw new BiffWorkbookException ('Unknown BIFF version: ' . $bof ['version']);
				$this->biffVersion = $bof ['version'] == 0x0500 ? 5 : 8;
				$this->palette = self::$_defaultPalette
					+ ($this->biffVersion == 5 ? self::$_biff5Palette : self::$_biff8Palette);
				$this->formats = BiffWorkbookStyle::$dateFormats + BiffWorkbookStyle::$numberFormats;
				$this->_datemode = 0;
				break;
			case 16:
				if (!isset ($this->_sheetsIndex [$this->_offset])) throw new BiffWorkbookException ('Undefined sheet');
				$this->_sheet = $this->_sheetsIndex [$this->_offset];
				break;
			default:
				return;
		}

		$this->_offset += $bof ['size'] + 4;
		while ($this->_offset < $this->document->directory [$this->_id]['desc']['size'])
		{
			$record = unpack ('vid/vsize', $this->_data (4));
			$this->_offset += 4;
			$move = false;
			if ($record ['id'] == 0x000a)
			{
				$this->_substream ();
				return;
			}
			if (isset (self::$_records [$record ['id']]))
			{
				$method = '_on' . self::$_records [$record ['id']];
				if (!method_exists ($this, $method))
					throw new BiffWorkbookException ('Record handler ' . $method . ' not exists');
				$move = $this->$method ($record);
			}
			if (!$move) $this->_offset += $record ['size'];
		}
	}

	/**
	 * Constructor
	 * @param CompoundDocument $document Document
	 */
	public function __construct (CompoundDocument $document)
	{
		$this->document = $document;
		foreach ($this->document->directory as $id => &$entry)
			if ($entry ['desc']['name'] == 'Workbook' || $entry ['desc']['name'] == 'Book')
			{
				$this->_id = $id;
				break;
			}
		if (!$this->_id) throw new Exception ('Not an Excel file');
	}

	/**
	 * Parsing.
	 */
	public function parse ()
	{
		$this->sheets = $this->fonts = $this->formats = $this->sst = $this->styles = array ();
		$this->_sheet = null;
		$this->_offset = 0;
		$this->_substream ();
	}
}
?>