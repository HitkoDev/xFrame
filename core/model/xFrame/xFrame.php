<?php

class xFrame {
	
	private $apiQuery;
	private $context;
	private $models = array();
	private $query;
	private $user;
	
	function __construct($context = ''){
		$GLOBALS['xFrame'] = $this;
		
		ob_start();
		
		$this->getModel('database');
		$this->getModel('session');
		
		// load user if it's attached to this session
		$this->user = $this->loadResource('access', array(
			'_id' => $this->getSessionData('user'),
		));
		// otherwise load default user
		if(!$this->user) $this->user = $this->loadResource('access', array(
			'identifier' => 'default',
			'type' => 'defaultUser',
		));
		
		if($context) $this->loadContext($context);
		
		// parse query string 
		// query format: /value1/value2[0]:value2[1]:value2[2]/value3 ... 
		// API format: /ignored/name1:value1[0]:value1[1]/ignored/name2:value2[0]:value2[1] ...
		$query = array();
		$apiQuery = array();
		if(isset($_REQUEST['req'])){
			$req = array_map('trim', explode('/', $_REQUEST['req']));
			foreach($req as $val){
				$val = array_filter(array_map('trim', explode(':', $val)));
				if(count($val) > 1){
					$query[] = $val;
					if(count($val) > 1) $apiQuery[ array_shift($val) ] = $val;
				} elseif(count($val) > 0){
					$query[] = $val[0];
				}
			}
		}
		$this->query = $query;
		$this->apiQuery = $apiQuery;
		
		switch($this->getQuerySegment(0)){
			
			case 'execute': 
				$this->executeAPICall();
				break;
				
			default :
				
		}
	}
	
	// run requested model->action() (such as user->login()) and print results in JSON array
	// this is an endpoint function, calling it will end execution
	private function executeAPICall(){
		$results = $this->executeModelAction($this->getAPIParamter('model'), $this->getAPIParamter('action'));
		while(ob_get_level() > 0) ob_end_clean();
		header('Content-Type: application/json');
		echo json_encode($results, JSON_UNESCAPED_UNICODE);
		exit();		// prevent additional actions
	}
	
	function isLoggedIn(){
		return $this->user->type == 'user';
	}
	
	function executeModelAction($models = array(), $actions = array()){
		$return = array();
		if($models && $actions){
			for($i = 0; $i < count($models) && $i < count($actions); $i++){
				$model = $this->getModel($models[$i]);
				$action = $actions[$i];
				$return[$i] = $model && method_exists($model, $action) ? $model->$action() : false;
			}
		}
		return $return;
	}
	
	// access query data
	function getQuerySegment($index){
		if(isset($this->query[$index])) return $this->query[$index];
		return false;
	}
	
	// access API parameters
	function getAPIParamter($name){
		if(isset($this->apiQuery[$name])) return $this->apiQuery[$name];
		return false;
	}
	
	function getSessionData($name){
		return $this->getModel('session')->get($name);
	}
	
	function setSessionData($name, $value){
		return $this->getModel('session')->set($name, $value);
	}
	
	function loadContext($name){
		$context = $this->loadResource('propertySet', array(
			'type' => 'context',
			'identifier' => $name,
		));
		if($context){
			$this->context = $context;
			echo $this->parseTemplate($this->context->getProperty('template'));
		}
	}
	
	function getModel($name){
		if(!isset($this->models[$name])){
			include_once(CORE_PATH . '/model/' . $name . '/'. $name . '.php');
			if(class_exists($name)){
				$this->models[$name] = new $name();
			} else {
				return false;
			}
		}
		return $this->models[$name];
	}
	
	function parseTemplate($template = 'default', $element = 'default', $properties = array()){
		$output = file_get_contents(ASSETS_PATH . '/templates/' . $template . '/' . $element . '.html');
		
		$output = $this->parseTags($output, $properties);
		
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
						} elseif($this->context->hasProperty($key)){
							$value = $this->context->getProperty($key);
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
	
	function getDBTable($name){
		return $this->getModel('database')->getTable($name);
	}
	
	function loadResource($class, $conditions){
		$database = $this->getDBTable($class);
		$resource = $database->find($conditions);
		if($resource->hasNext()){
			return $this->getClass($class, $resource->getNext());
		}
		return false;
	}
	
	function getClass($class, $data){
		include_once(CORE_PATH . '/class/' . $class . '/'. $class . '.php');
		if(class_exists($class)){
			return new $class($data);
		}
		return false;
	}
	
	function saveResource($class, $resource){
		$database = $this->getDBTable($class);
		if($resource['_id']){
			return $database->update(array('_id' => $resource['_id']), $resource);
		} else {
			return $database->insert($resource);
		}
	}
	
	function loadProperties($id, $type = 'resource', $key = 'default'){
		return array();
	}
	
}