<?php

class xFrame {
	
	function __construct(){
		$GLOBALS['xFrame'] = $this;
		$query = array();
		if($_REQUEST['req']) $req = array_map('trim', explode('/', $_REQUEST['req']));
		foreach($req as $val){
			$val = array_filter(array_map('trim', explode(':', $val)));
			if(count($val) > 1) $query[array_shift($val)] = $val;
		}
		$this->query = $query;
	}
	
}