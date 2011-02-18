<?php
/**
 * Microsoft compound document reader
 * Based on http://sc.openoffice.org/compdocfileformat.pdf
 *
 * @version 0.5.1
 * @author Ruslan V. Uss <unclerus at gmail.com>
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

class CompoundDocumentException extends Exception {}

class CompoundDocument
{
	/**
	 * Output character set. Can be any of iconv encodings.
	 * @var string
	 */
	public $charset;

	/**
	 * Low-level compound document header
	 * @var array
	 */
	public $header = array ();

	/**
	 * Master sector allocation table. This is an array of the SecIDs of all sectors used by
	 * SAT (sector allocation table)
	 * @var array
	 */
	public $msat = array ();

	/**
	 * Sector allocation table. Contains SecIDs of all sectors used by user streams
	 * @var array
	 */
	public $sat = array ();

	/**
	 * Short sector allocation table. Contains SecIDs of all short streams
	 * @var array
	 */
	public $ssat = array ();

	/**
	 * Parsed document.
	 * Each entry refers to a storage or stream.
	 * Data can be found only in streams.
	 *
	 * Structure of the array:
	 * array (
	 *    DirID => array (
	 *         'desc' => array (
	 *	           'name' => <name of the entry>,
	 *             'type' => <type of the entry, 0 = empty, 1 = storage, 2 = stream, 5 = root storage>,
	 *             'color' => <node color of the entry, for red-black tree>,
	 *             'leftDirId' => <DirID of the left child node, -1 if there is no child>,
	 *             'rightDirId' => <DirID of the right child node, -1 if there is no child>,
	 *             'rootDirId' => <DirID of the root node, if this entry is a storage, -1 otherwise>,
	 *             ... see http://sc.openoffice.org/compdocfileformat.pdf 7.2
	 *         ),
	 *         'data' => <binary stream data>
	 *    )
	 *    ...
	 * )
	 * @var array
	 */
	public $directory = array ();
	
	private $_shortData;
	private $_data;

	/**
	 * Constructor
	 * @param string $charset Output character set
	 */
	public function __construct ($charset)
	{
		$this->charset = $charset;
	}

	/**
	 * Offset of the given sector in file
	 * @param int $secId Sector ID
	 * @return int sector offset in file
	 */
	public function sectorOffset ($secId)
	{
		return $this->header ['secSize'] * $secId + 0x200;
	}

	private function _value ($offset, $size = 4, $format = 'l')
	{
		$result = @unpack ($format, substr ($this->_data, $offset, $size));
		return $result [1];
	}

	private function _appendSat ($secId)
	{
		$sectorOffset = $this->sectorOffset ($secId);
		for ($i = 0; $i < $this->header ['secSize']; $i += 4)
		{
			$value = $this->_value ($sectorOffset + $i);
			$this->sat [] = $value;
		}
	}

	private function _buildMsat ($offset, $size, $first = false)
	{
		for ($i = 0; $i < $size - 4; $i += 4)
		{
			$value = $this->_value ($offset + $i);
			if ($value < 0) break;
			$this->msat [] = $value;
			$this->_appendSat ($value);
		}

		$next = $first ? $this->header ['msatSecId'] : $this->_value ($offset + $size - 4);
		if ($next == -2) return;
		$this->_buildMsat ($this->sectorOffset ($next), $this->header ['secSize']);
	}

	private function _buildSsat ()
	{
		for ($sector = $this->header ['ssatSecId']; $sector >= 0; $sector = $this->sat [$sector])
		{
			$sectorOffset = $this->sectorOffset ($sector);
			for ($i = 0; $i < $this->header ['secSize']; $i += 4)
			{
				$value = $this->_value ($sectorOffset + $i);
				$this->ssat [] = $value;
			}
		}
	}

	private function _getShortStream ($ssecId)
	{
		$result = '';
		for ($sector = $ssecId; $sector >= 0; $sector = $this->ssat [$sector])
			$result .= substr ($this->_shortData, $this->header ['ssecSize'] * $sector, $this->header ['ssecSize']);
		return $result;
	}

	private function _getNormalStream ($secId)
	{
		$result = '';
		for ($sector = $secId; $sector >= 0; $sector = $this->sat [$sector])
			$result .= substr ($this->_data, $this->sectorOffset ($sector), $this->header ['secSize']);
		return $result;
	}

	private function _getStream ($id)
	{
		return $this->directory [$id]['desc']['size'] < $this->header ['minStreamSize']
			? $this->_getShortStream ($this->directory [$id]['desc']['secId'])
			: $this->_getNormalStream ($this->directory [$id]['desc']['secId']);
	}

	private function _readDirectory ()
	{
		$dirCount = $this->header ['secSize'] / 0x80;
		$id = 0;
		for ($sector = $this->header ['dirSecId']; $sector >= 0; $sector = $this->sat [$sector])
		{
			$sectorOffset = $this->sectorOffset ($sector);
			for ($i = 0; $i < $dirCount; $i ++, $id ++)
			{
				$desc = @unpack (
					'A64name/vnameLength/Ctype/Ccolor/lleftDirId/lrightDirId/lrootDirId/A16uid/VuserFlags/A8createTime/A8modifTime/lsecId/lsize',
					substr ($this->_data, $sectorOffset + $i * 0x80, 0x80)
				);
				if ($desc ['type'] == 0) break;
				if ($desc ['type'] == 5)
				{
					$this->header ['sstreamSecId'] = $desc ['secId'];
					$this->_shortData = $this->_getNormalStream ($desc ['secId']);
				}
				$desc ['name'] = iconv ('utf-16le', $this->charset, substr ($desc ['name'], 0, $desc ['nameLength'] - 2));
				$this->directory [$id]['desc'] = $desc;
				if ($desc ['type'] == 2) $this->directory [$id]['data'] = $this->_getStream ($id);
			}
		}
	}

	/**
	 * Parse compound document
	 * @param string $data Compound document data
	 */
	public function parse ($data)
	{
		$this->directory = $this->msat = $this->sat = $this->ssat = array ();
		$this->header = @unpack (
			'A8ident/h32uid/vrevision/vversion/vbyteOrder/vssz/vsssz/x10/VsatSize/VdirSecId/x4/VminStreamSize/lssatSecId/VssatSize/lmsatSecId/VmsatSize',
			substr ($data, 0, 0x200)
		);
		if ($this->header ['ident'] != "\xD0\xCF\x11\xE0\xA1\xB1\x1A\xE1")
			throw new compoundDocumentException ('Invalid file format');
		if ($this->header ['byteOrder'] != 0xfffe)
			throw new compoundDocumentException ('Invalid byte order');
		$this->header ['secSize'] = 1 << $this->header ['ssz'];
		$this->header ['ssecSize'] = 1 << $this->header ['sssz'];

		$this->_data = &$data;

		$this->_buildMsat (0x4c, 0x1b4, true);
		$this->_buildSsat ();
		$this->_readDirectory ();

		$this->_data = null;
	}
}
?>