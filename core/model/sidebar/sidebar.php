<?php

class Sidebar {
	
	function __construct(){
		
	}
	
	function showResources(){
		global $xFrame;
		$resources = $xFrame->loadResources('parsable', array(), array(
			'identifier' => true, 
			'type' => true, 
			'_id' => true, 
			'category' => true,
		), array('type' => 1));
		$return = array();
		foreach($resources as $res){
			$return[] = array(
				'identifier' => $res->getValue('identifier'), 
				'type' => $res->getValue('type'), 
				'_id' => (string)$res->getValue('_id'), 
				'category' => $res->getValue('category'),
			);
		}
		return $return;
	}
	
}