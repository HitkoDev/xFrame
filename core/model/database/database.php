<?php

class Database extends MongoDB {
	
	function __construct(){
		$client = new MongoClient(MONGO_STRING);
		parent::__construct($client, MONGO_DB);
	}
	
	function getTable($name){
		return new MongoCollection($this, MONGO_PREFIX . $name);
	}
	
}