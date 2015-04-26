<?php

class Database extends MongoClient {
	
	function __construct(){
		parent::__construct(MONGO_STRING);
	}
	
}