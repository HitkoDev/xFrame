<?php

class Access {
	
	private $object;
	private $context;
	private $category;
	
	function __construct($data = array()){
		global $xFrame;
		
		$this->context = array();
		if(isset($data['context'])) foreach($data['context'] as $context => $set){
			$props = $xFrame->getClass('propertySet', $set);
			if($props) $this->context[$context] = $props;
		}
		
		$this->category = array();
		if(isset($data['category'])) foreach($data['category'] as $category => $set){
			$props = $xFrame->getClass('propertySet', $set);
			if($props) $this->category[$category] = $props;
		}
		
		$this->object = $data;
		
	}
	
	function checkAccess($action, $context = '', $category = ''){
		$contAcc = true;
		if($context){
			$contAcc = false;
			if(isset($this->context[$context])) $contAcc = $this->context[$context]->getProperty($action);
		}
		
		$catAcc = true;
		if($category){
			$catAcc = false;
			if(isset($this->category[$category])) $catAcc = $this->category[$category]->getProperty($action);
		}
		
		return $contAcc && $catAcc;
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