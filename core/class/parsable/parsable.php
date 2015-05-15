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
			$output = '';
			if(function_exists($function)) $output = call_user_func($function, $this->getProperties($properties, $propertySets));
			if(is_string($output)) return $xFrame->parseText($output);
			return $output;
		}
		
		return $xFrame->parseText($this->object['content'], $this->getProperties($properties, $propertySets));
	}
	
	public function getValue($name){
		$path = array_map('trim', explode('.', $name));
		$array = $this->object;
		$i = 0;
		while($i < count($path) - 1 && isset($array[$path[$i]])){
			$array = $array[$path[$i]];
			$i++;
		}
		if(isset($array[$path[$i]])) return $array[$path[$i]];
		return null;
	}
	
	private function getProperties($properties = array(), $propertySets = array()){
		$props = array();
		foreach($propertySets as $set){
			if(isset($this->property[$set])) $props = array_merge($props, $this->property[$set]->getProperties());
		}
		return array_merge($props, $properties);
	}
	
	private function getName(){
		return 'func_' . md5($this->object['type'] . '_' . $this->object['identifier']);
	}
	
	private function includeExecutable(){
		global $xFrame;
		
		$cacheManager = $xFrame->getModel('cacheManager');
		$file = $cacheManager->getFile($this->object['_id']);
		if(is_file($file)){
			return include_once($file);
		}
		file_put_contents($file, '<?php function ' . $this->getName() . '($properties = array()){global $xFrame;' . $this->object['content'] . '}');
		return include_once($file);
	}
	
}