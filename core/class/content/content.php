<?php

class Content {
	
	private $object;
	
	function __construct($data = array()){
		global $xFrame;
		
		$this->object = $data;
		
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
	
}