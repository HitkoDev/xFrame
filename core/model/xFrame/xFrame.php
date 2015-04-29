<?php

class xFrame {
	
	function __construct($context = ''){
		$GLOBALS['xFrame'] = $this;
		
		ob_start();
		
		$this->getModel('database');
		$this->getModel('session');
		if($context) $this->loadContext($context);
		
		// parse query string (query format: /value1/value2[0]:value2[1]:value2[2]/value3 ..., API format: /ignored/name:value[0]:value[1]/ignored ...)
		$query = array();
		$apiQuery = array();
		if($_REQUEST['req']) $req = array_map('trim', explode('/', $_REQUEST['req']));
		foreach($req as $val){
			$val = array_filter(array_map('trim', explode(':', $val)));
			if(count($val) > 1){
				$query[] = $val;
				if(count($val) > 1) $apiQuery[ array_shift($val) ] = $val;
			} elseif(count($val) > 0){
				$query[] = $val[0];
			}
		}
		$this->query = $query;
		$this->apiQuery = $apiQuery;
		
		if(count($this->query) > 0){
			switch($this->query[0]){
				
				case 'execute': // run requested actions (such as user->login()) and return result in JSON format
					$return = array();
					for($i = 0; $i < count($this->apiQuery['model']) && $i < count($this->apiQuery['action']); $i++){
						$model = $this->getModel($this->apiQuery['model'][$i]);
						$action = $this->apiQuery['action'][$i];
						$return[$i] = $model && method_exists($model, $action) ? $model->$action() : false;
					}
					while(ob_end_clean());
					header('Content-Type: application/json');
					echo json_encode($return, JSON_UNESCAPED_UNICODE);
					exit();		// prevent additional actions
					
				default :
					
			}
		}
	}
	
	function loadContext($name){
		$this->context = $this->loadProperties($name, 'context');
	}
	
	function getModel($name){
		if(!isset($this->$name)){
			if(include_once(CORE_PATH . '/model/' . $name . '/'. $name . '.php')){
				$this->$name = new $name();
			} else {
				return false;
			}
		}
		return $this->$name;
	}
	
	function parseTemplate($template = 'default'){
		$output = file_get_contents(ASSETS_PATH . '/templates/' . $template . '/default.html');
		
		$output = $this->parseTags($output, array('base_url' => '/manager/'));
		
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
				ksort($arguments);
				$tagHash = md5($key . '_' . serialize($arguments));
				
				$value = $cacheManager->load($tagHash);
				
				if(!$value){
				
					// determine tag type
					$type = 'function';
					if(isset($parseTypes[ substr($key, 0, 1) ])){
						$type = $parseTypes[ substr($key, 0, 1) ];
						$key = substr($key, 1);
					}
						
					// extract property sets from key
					$propertySets = array_filter(array_map('trim', explode(':', $key)));
					$key = array_shift($propertySets);
						
					// check cache
					$uncached = substr($key, 0, 1) == '!' ? true : false;
					if($uncached){
						$key = substr($key, 1);
						$cachable = false;
					}
					
					if($type == 'property'){
						
						if(isset($properties[$key])){
							$value = $properties[$key];
						} elseif(isset($this->context[$key])){
							$value = $this->context[$key];
						} else {
							$value = '';
						}
						
					} else {
						
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