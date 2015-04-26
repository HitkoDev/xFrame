<?php

class Database extends MongoClient {
	
	function __construct(){
		parent::construct(MONGO_STRING);
	}
	
}