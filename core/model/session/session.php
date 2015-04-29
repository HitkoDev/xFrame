<?php

class Session {
	
	function __construct(){
		global $xFrame;
		
		// start or resume session
		session_start();
		$this->id = session_id();
		
		// get session storage
		$this->database = $xFrame->database->getTable('session');
		$res = $this->database->find(array(
			'id' => $this->id
		));
		
		if($res->hasNext()){
			// load session from storage
			$this->resource = $res->getNext();
			$this->resource['lastActive'] = new MongoDate();
			
			// update active time
			$this->database->update(array(
				'id' => $this->id
			), $this->resource);
		} else {
			// add new session to storage
			$this->started = $this->lastActive = new MongoDate();
			$this->data = array();
			$this->resource = array(
				'id' => $this->id,
				'started' => $this->started,
				'lastActive' => $this->lastActive,
				'data' => $this->data,
			);
			$this->database->insert($this->resource);
		}
	}
	
	function get($name){
		// get session data
		if(isset($this->resource->data[$name])) return $this->resource->data[$name];
		return null;
	}
	
	function set($name, $value){
		// set session data
		$this->resource->data[$name] = $value;
		$this->database->update(array(
			'id' => $this->id
		), $this->resource);
	}
	
}