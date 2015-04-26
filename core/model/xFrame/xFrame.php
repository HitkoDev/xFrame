<?php

class xFrame {
	
	function __construct(){
		$GLOBALS['xFrame'] = $this;
		$query = array();
		if($_REQUEST['req']) $req = array_map('trim', explode('/', $_REQUEST['req']));
		foreach($req as $val){
			$val = array_filter(array_map('trim', explode(':', $val)));
			if(count($val) > 1) $query[ array_shift($val) ] = $val;
		}
		$this->query = $query;
	}
	
	function parseTemplate($template = 'default'){
		$output = file_get_contents(ASSETS_PATH . '/templates/' . $template . '/default.html');
		
		$output = $this->parseTags($output);
		
		return $output;
	}
	
	function parseTags($text){
		if(preg_match_all('/\[\[([^\?&]+)((.*?[\?&][[:alnum:]]+=`[^`]*`)*).*\]\]/u', $text, $tags) > 0){
			foreach($tags as $tag){
				$key = trim($tag[1]);
				$arguments = array();
				if(preg_match_all('/[\?&][[:alnum:]]+=`[^`]*`/u', trim($tag[2]), $args)){
					var_dump($args);
				}
			}
		}
		
		return $text;
	}
	
}