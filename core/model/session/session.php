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
			'data.id' => $this->id
		));
		
		if($res->hasNext()){
			// load session from storage
			$this->resource = $res->getNext();
			$this->resource['lastActive'] = new MongoDate();
			
			// update active time
			$this->database->update(array(
				'_id' => $this->resource['_id'],
			), $this->resource);
		} else {
			// add new session to storage
			$this->started = $this->lastActive = new MongoDate();
			$this->data = array(
				'id' => $this->id,
				'user' => '',
			);
			$this->resource = array(
				'_id' => new MongoId(),
				'started' => $this->started,
				'lastActive' => $this->lastActive,
				'data' => $this->data,
			);
			$this->database->insert($this->resource);
		}
		
		// load user if it's attached to this session
		$this->user = false;
		if($this->resource['data']['user']){
			$user = $xFrame->loadResource(array(
				'_id' => $this->resource['data']['user'],
			));
			if($user) $this->user = $user;
		}
	}
	
	function get($name){
		// get session data
		if(isset($this->resource['data'][$name])) return $this->resource['data'][$name];
		return null;
	}
	
	function set($name, $value){
		// set session data
		$this->resource['data'][$name] = $value;
		$this->database->update(array(
			'_id' => $this->resource['_id'],
		), $this->resource);
	}
	
}