<?php
/**
 * @author 		Ronald Pijpers
 * @copyright	Copyright (C) 2011 Intertron. All rights reserved.
 * @license		GNU/GPL version 3, see gpl.txt
 */

class ComCreateToolbarButtonExamine extends KToolbarButtonAbstract
{	
	public function getOnClick()
	{
		$id		= KRequest::get('get.id', 'int');
		$token 	= JUtility::getToken();
		$json 	= "{method:'post', url:'index.php?option=com_create&view=component&id=$id', formelem:'adminForm', params:{action:'examine', _token:'$token'}}";

		return 'new KForm('.$json.').submit();';
	}
}