<?php

class Parsable {
	
	private $object;
	private $property;
	
	function __construct($data = array()){
		global $xFrame;
		
		$this->property = array();
		if(isset($data['property'])) foreach($data['property'] as $property => $set){
			$props = $xFrame->getClass('propertySet', $set);
			if($props) $this->property[$property] = $props;
		}
		
		$this->object = $data;
		
	}
	
	function parse($properties = array(), $propertySets = array()){
		global $xFrame;
		return $xFrame->parseText($this->object['content']);
	}
	
}