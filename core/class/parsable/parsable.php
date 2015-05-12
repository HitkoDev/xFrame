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
		
		if($this->object['executable']){
			$function = $this->getName();
			$this->includeExecutable();
			if(function_exists($function)) return $xFrame->parseText(call_user_func($function, $this->getProperties($properties, $propertySets)));
			return '';
		}
		
		return $xFrame->parseText($this->object['content'], $this->getProperties($properties, $propertySets));
	}
	
	private function getProperties($properties = array(), $propertySets = array()){
		$props = array();
		foreach($propertySets as $set){
			if(isset($this->property[$set])) $props = array_merge($props, $this->property[$set]->getProperties());
		}
		return array_merge($props, $properties);
	}
	
	private function getName(){
		return md5($this->object['type'] . '_' . $this->object['identifier']);
	}
	
	private function includeExecutable(){
		global $xFrame;
		
		$cacheManager = $xFrame->getModel('cacheManager');
		$file = $cacheManager->getFile($this->object['_id']);
		if($file){
			if(file_get_contents($file)){
				include_once($file);
			} else {
				file_put_contents($file, '<?php function ' . $this->getName() . '($properties = array()){' . $this->object['content'] . '}');
				include_once($file);
			}
		}
	}
	
}