<?php

class xFrame {
	
	function __construct($context = ''){
		$GLOBALS['xFrame'] = $this;
		$query = array();
		if($_REQUEST['req']) $req = array_map('trim', explode('/', $_REQUEST['req']));
		foreach($req as $val){
			$val = array_filter(array_map('trim', explode(':', $val)));
			if(count($val) > 1) $query[ array_shift($val) ] = $val;
		}
		$this->query = $query;
		$this->getModel('database');
		if($context) $this->loadContext($context);
	}
	
	function loadContext($name){
		$this->context = $this->loadProperties($name, 'context');
	}
	
	function getModel($name){
		if(!isset($this->$name)){
			include_once(CORE_PATH . '/model/' . $name . '/'. $name . '.php');
			$this->$name = new $name();
		}
		return $this->$name;
	}
	
	function parseTemplate($template = 'default'){
		$output = file_get_contents(ASSETS_PATH . '/templates/' . $template . '/default.html');
		
		$output = $this->parseTags($output);
		
		return $output;
	}
	
	function parseTags($text, $properties = array()){
		
		$cachable = true;
		
		// todo: obtain parseTypes from available tag types
		$parseTypes = array(
			'+' => 'field',
			'$' => 'property',
			'#' => 'text',
		);
		
		$cacheManager = $this->getModel('cacheManager');
				
		if(preg_match_all('/\[\[([^\[\]\?&]+)(([^\[\]]*?[\?&][[:alnum:]]+=`[^`\[\]]*`)*)[^\[\]]*\]\]/u', $text, $tags, PREG_SET_ORDER) > 0){	// extract tags
			foreach($tags as $tag){
				
				$key = trim($tag[1]);
				
				// parse tag arguments
				$arguments = array();
				if(preg_match_all('/[\?&]([[:alnum:]]+)=`([^`]*)`/u', trim($tag[2]), $args, PREG_SET_ORDER) > 0){
					foreach($args as $arg){
						$arguments[trim($arg[1])] = trim($arg[2]);
					}
				}
				
				// generate tag hash
				$arguments = ksort($arguments);
				$tagHash = md5($key . '_' . serialize($arguments));
				
				$value = $cacheManager->load($tagHash);
				
				if(!$value){
				
					// determine tag type
					$type = 'function';
					if(in_array(substr($key, 0, 1), $parseTypes)){
						$type = $parseTypes[ substr($key, 0, 1) ];
						$key = substr($key, 1);
					}
					
					if($type == 'property'){
						
						if(in_array($key, $properties)){
							$value = $properties[$key];
						} elseif(in_array($key, $this->context)){
							$value = $this->context[$key];
						} else {
							$value = '';
						}
						
					} else {
						
						// extract property sets from key
						$propertySets = array_filter(array_map('trim', explode(':', $key)));
						$key = array_shift($propertySets);
						
						// check cache
						$uncached = substr($key, 0, 1) == '!' ? true : false;
						if($uncached){
							$key = substr($key, 1);
							$cachable = false;
						}
						
						$props = $this->loadProperties($key, $type);	// load default property set
						foreach($propertySets as $propertySet) $props = array_merge($props, $this->loadProperties($key, $type, $propertySet));		// merge any additional property sets
						$arguments = array_merge($properties, $props, $arguments);	// merge property sets with tag arguments
						
					}
					
					$cacheManager->store($tagHash, $value);
					
				}
				
				$text = str_replace($tag[0], $value, $text);
				
			}
		}
		
		return $text;
	}
	
	function loadProperties($id, $type = 'resource', $key = 'default'){
		return array();
	}
	
}