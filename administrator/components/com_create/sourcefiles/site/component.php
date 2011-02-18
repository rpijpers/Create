<?php 
// Check if Koowa is active
if(!defined('KOOWA')) {
    JError::raiseWarning(0, JText::_("Koowa wasn't found. Please install the Koowa plugin and enable it."));
    return;
}

KInflector::addWord('items', 'items');
KInflector::addWord('item', 'items');
echo KFactory::get('site::com.component.dispatcher')->dispatch(KRequest::get('get.view', 'cmd', 'items'));