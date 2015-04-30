<?php

class PropertySet {
	
	private $object;
	private $properties;
	
	function __construct($data = array()){
		global $xFrame;
		$this->object = $data;
		
		$this->properties = $data['properties'];
		
		// merge with parent propertySet
		// properties in this set override parent properties
		if($data['parent']){
			$parent = $xFrame->loadResource('propertySet', array(
				'identifier' => $data['parent'],
				'type' => $data['type']
			));
			if($parent) $this->properties = array_merge($parent->properties, $this->properties);
		}
	}
	
	function getProperty($name){
		if(isset($this->properties[$name])) return $this->properties[$name];
		return false;
	}
	
	public function __get($name){
		if(isset($this->object[$name])) return $this->object[$name];
		return null;
	}
	
	public function __set($name, $value){
		$this->object[$name] = $value;
		return null;
	}
	
	public function __isset($name){
		return isset($this->object[$name]);
	}
	
    public function __unset($name){
        unset($this->object[$name]);
    }
	
}