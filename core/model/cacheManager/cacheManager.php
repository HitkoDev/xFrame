<?php

class CacheManager {
	
	function store($key, $data, $time = 0){
		$file = $this->getFile($key);
		if($file && file_put_contents($file, serialize(array('time' => time() + $time, 'data' => $data)))){
			return true;
		}
		return false;
	}
	
	function load($key){
		$file = $this->getFile($key);
		if($file && is_file($file)){
			$file = file_get_contents($file);
			if($file){
				$data = unserialize($file);
				if($data['time'] == 0 || $data['time'] > time()) return $data['data'];
			}
		}
		return null;
	}
	
	function getFile($key){
		$key = md5($key);
		if(is_dir(CORE_PATH . '/cache') || mkdir(CORE_PATH . '/cache')){
			$dir = substr($key, 0, 16);
			$file = substr($key, 16);
			if(is_dir(CORE_PATH . '/cache/' . $dir) || mkdir(CORE_PATH . '/cache/' . $dir)){
				return CORE_PATH . '/cache/' . $dir . '/' . $file;
			}
		}
		return false;
	}
	
}