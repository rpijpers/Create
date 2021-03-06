<?php
/**
 * @author 		Ronald Pijpers
 * @copyright	Copyright (C) 2011 Intertron. All rights reserved.
 * @license		GNU/GPL version 3, see gpl.txt
 */

class ComCreateViewComponentHtml extends ComDefaultViewHtml
{
	public function display()
	{	
		$generatebutton = KFactory::get('admin::com.create.toolbar.button.generate');
		KFactory::get('admin::com.create.toolbar.component')
				->append($generatebutton);
		
		return parent::display();
	}
}