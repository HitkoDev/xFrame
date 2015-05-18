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
		
		if($context) $this->context = $this->loadContext($context);
		
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
				if($this->context) echo $this->parse($this->context->getProperty('template'));
				break;
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
		return $context;
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
	
	function parse($item, $properties = array(), $propertySets = array('default'), $clean = false){
		$item = array_map('trim', explode('.', $item, 2));
		$output = '';
		if(count($item) > 0){
			if(count($item) == 1 || $item[0] == 'property'){		// item without a type is interpreted as a property
				if(count($item) == 2) $item[0] = $item[1];
				if(isset($properties[ $item[0] ])){
					$output = $properties[ $item[0] ];
				} elseif($this->context){
					$output = $this->context->getProperty($item[0], 'XFRAME_UNPARSABLE_PROPERTY');
				} else {
					$output = 'XFRAME_UNPARSABLE_PROPERTY';
				}
			} else {
				$item = $this->loadResource('parsable', array(
					'type' => $item[0],
					'identifier' => $item[1],
				));
				if($item) $output = $item->parse($properties, $propertySets);
			}
		}
		if($clean) $output = $this->clean($output);
		return $output;
	}
	
	function clean($text){
		$text = $this->parseText($text);
		if(preg_match_all('/\[\[([^\[\]\?&]+)(([^\[\]]*?[\?&][[:alnum:]]+=`[^`\[\]]*`)*)[^\[\]]*\]\]/u', $text, $tags, PREG_SET_ORDER) > 0){	// extract tags
			foreach($tags as $tag){
				$text = str_replace($tag[0], '', $text);
			}
		}
		return $text;
	}
	
	// receives text and parses any tags that may be contained within
	function parseText($text, $properties = array()){
		
		$cacheManager = $this->getModel('cacheManager');
		ksort($properties);
		$hash = md5($text . '_' . serialize($properties));
		$value = $cacheManager->load($hash);
		
		if(!$value){
			// only parse cachable tags and cache the output
			$value = $this->parseTags($text, $properties, true);
			$cacheManager->store($hash, $value);
		}
		
		// parse any remaining tags
		$value = $this->parseTags($value, $properties, false);
		
		return $value;
	}
	
	// parses tags; if $cachedOnly == true, this function will leave uncached tags intact
	private function parseTags($text, $properties = array(), $cachedOnly = false){
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
						
				// check cache
				$cachable = true;
				if(substr($key, 0, 1) == '!'){
					$key = substr($key, 1);
					$cachable = false;
					if($cachedOnly) continue;	// skip uncached tags when cachedOnly
				}
				
				// generate tag hash
				ksort($arguments);
				$tagHash = md5($key . '_' . serialize($arguments));
				
				$value = '';
				if($cachable) $value = $cacheManager->load($tagHash);
				
				if(!$value){
						
					// extract property sets from key
					$propertySets = array_filter(array_map('trim', explode(':', $key)));
					$key = array_shift($propertySets);
					
					$value = $this->parse($key, array_merge($properties, $arguments), $propertySets);
					if($value == 'XFRAME_UNPARSABLE_PROPERTY') continue;		// key is an unrecognised property, which may be recognised later on
					$value = $this->parseTags($value, $properties, true);
					$cacheManager->store($tagHash, $value);
					
				}
					
				if(!$cachedOnly) $value = $this->parseTags($value, $properties, false);
				
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
	
	function loadResources($class, $conditions, $fields = array(), $sort = array()){
		$database = $this->getDBTable($class);
		$resource = $database->find($conditions, $fields);
		$resource->sort($sort);
		$resources = array();
		while($resource->hasNext()){
			$res = $this->getClass($class, $resource->getNext());
			if($res) $resources[] = $res;
		}
		return $resources;
	}
	
	function getClass($class, $data){
		include_once(CORE_PATH . '/class/' . $class . '/'. $class . '.php');
		if(class_exists($class)){
			return new $class($data);
		}
		return false;
	}
	
	function getClassDefinitions(){
		
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