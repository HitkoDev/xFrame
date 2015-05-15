<?php

class Editor {
		
	private $loaded = false;
	
	function __construct(){
		global $xFrame;
		
		$id = $xFrame->getAPIParamter('id');
		if($id){
			$id = $id[0];
			$drafts = $xFrame->getDBTable('draft');
			$draft = $drafts->find(array(
				'targetID' => $id,
			));
			if($draft->hasNext()){
				$this->draft = $draft->getNext();
				$this->loaded = true;
			} else {
				$class = $xFrame->getAPIParamter('class');
				if($class){
					$class = $class[0];
					$db = $xFrame->getDBTable($class);
					$target = $db->find(array(
						'_id' => new MongoId($id)
					));
					if($target->hasNext()){
						$target = $target->getNext();
						$target['targetID'] = $target['_id'];
						unset($target['_id']);
						$target['class'] = $class;
						$this->draft = $target;
						$drafts->insert($this->draft);
						$this->loaded = true;
					}
				}
			}
		}
		if($this->loaded){
			$this->loaded = false;
			$class = $this->draft['class'];
			$type = $this->draft['type'];
			$db = $xFrame->getDBTable('classType');
			$classDef = $db->find(array(
				'class' => $class,
				'type' => $type,
			));
			if($classDef->hasNext()){
				$this->editor = $classDef->getNext();
				$this->loaded = true;
			}
		}
		$this->fields = array();
	}
	
	function isLoaded(){
		return $this->loaded;
	}
	
	function getFields($tab){
		if(isset($this->fields[$tab])) return $this->fields[$tab];
		global $xFrame;
		if(isset($this->editor[$tab])){
			$fl = array();
			$fields = $this->editor[$tab];
			uasort($fields, "fieldComparator");
			foreach($fields as $key => $field){
				$req = '';
				if(isset($field['required']) && $field['required']) $req = 'required="required"';
				$type = '';
				if(isset($field['type'])) $type = $field['type'];
				$value = '';
				if($tab == 'main' && isset($this->draft[$key])) $value = $this->draft[$key];
				$id = (string) $this->draft['targetID'];
				$editor = $xFrame->parse($field['element'], array(
					'name' => $key,
					'key' => $key . '_' . $id,
					'required' => $req,
					'type' => $type,
					'value' => str_replace(array('[', ']', '<', '>'), array('&#91;', '&#93;', '&lt;', '&gt;'), $value),
					'_id' => $id,
				));
				if($editor) $fl[$key] = $editor;
			}
			$this->fields[$tab] = $fl;
			return $fl;
		}
		return false;
	}
	
}

function fieldComparator($a, $b){
	return $a["ordering"] - $b["ordering"];
}