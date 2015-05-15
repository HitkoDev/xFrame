<?php

class Parser {
	
	function __construct(){
		
	}
	
	function parse(){
		global $xFrame;
		$item = $xFrame->getAPIParamter('item')[0];
		$success = false;
		$message = 'Item doesn\'t exist';
		if($item){
			$propertySets = array('default');
			if(isset($_POST['propertySet'])) if(is_array($_POST['propertySet'])){
				foreach($_POST['propertySet'] as $set) $propertySets[] = $set;
			} else {
				$propertySets[] = $_POST['propertySet'];
			}
			return $xFrame->parse($item, array(), $propertySets);
		}
		return array(
			'success' => $success,
			'message' => $message,
		);
	}
	
}